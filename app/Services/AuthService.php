<?php

namespace App\Services;

use App\Exceptions\Auth\DjangoConnectionException;
use App\Exceptions\Auth\LogoutException;
use App\Exceptions\Auth\SSOAuthorizationException;
use App\Exceptions\Auth\SSOConnectionException;
use App\Models\AuthMethod;
use App\Models\User;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthService
{
    protected ?string $sslPemPath;
    protected string $provDjangoBaseUrl;
    protected bool $offlineMode;

    /** @var array<string, string> */
    protected array $offline = [
        'provider_username' => 'usuario',
        'provider_password' => 'demo',
        'admin_username' => 'admin',
        'admin_password' => 'demo',
    ];

    /** @var array<string, string> */
    protected array $sso = [
        'base_url' => '',
        'client_id' => '',
        'client_secret' => '',
    ];

    public function __construct()
    {
        $this->sslPemPath = Config::get('services.ssl_pem_path');
        $this->provDjangoBaseUrl = Config::get('services.provedores.django_base_url');
        $this->offlineMode = (bool) Config::get('services.auth_offline.enabled', false);
        $this->offline['provider_username'] = Config::get('services.auth_offline.provider_username', 'usuario');
        $this->offline['provider_password'] = Config::get('services.auth_offline.provider_password', 'demo');
        $this->offline['admin_username'] = Config::get('services.auth_offline.admin_username', 'admin');
        $this->offline['admin_password'] = Config::get('services.auth_offline.admin_password', 'demo');

        $this->sso['base_url'] = Config::get('services.sso.base_url');
        $this->sso['client_id'] = Config::get('services.sso.client_id');
        $this->sso['client_secret'] = Config::get('services.sso.client_secret');
    }

    /**
     * Intenta autenticar al usuario con Django usando username y password.
     *
     * @return array{token: string, user: User, permissions: array<mixed>}|null
     */
    public function loginByUsername(string $username, string $password): ?array
    {
        if ($this->offlineMode) {
            return $this->loginOfflineByUsername($username, $password);
        }

        $token = $this->provGetTokenByUsername($username, $password);

        if (! $token) {
            return null;
        }

        $accessToken = is_array($token) && isset($token['access']) ? $token['access'] : null;

        if (! $accessToken) {
            return null;
        }

        $susceptibilityInfo = $this->provGetSusceptibilityUser($accessToken);

        if ($susceptibilityInfo === null || ($susceptibilityInfo['data']['susceptible']) === false) {
            throw new Exception('El Usuario no es candidato para el Certifica, actualiza tu información en el registro para cumplir con los requisitos.');
        }

        $userInfo = $this->provGetUserInfo($accessToken);

        if (! $userInfo) {
            return null;
        }

        $localToken = $this->generateLocalToken('django', $userInfo['data']['username'], $userInfo['data']);

        return [
            'token' => $localToken['token'],
            'user' => $localToken['user'],
            'permissions' => [],
        ];
    }

    /**
     * Intenta autenticar al usuario con Django usando una e-firma.
     *
     * @return array{token: string, user: User, permissions: array<mixed>}|null
     */
    public function loginByEfirma(UploadedFile $cer, UploadedFile $key, string $password): ?array
    {
        if ($this->offlineMode) {
            return $this->loginOfflineProvider();
        }

        $token = $this->provGetTokenAndUserInfoByEfirma($cer, $key, $password);

        if (! $token) {
            return null;
        }

        $accessToken = is_array($token) && isset($token['access']) ? $token['access'] : null;

        $susceptibilityInfo = $this->provGetSusceptibilityUser($accessToken);

        if ($susceptibilityInfo === null || ($susceptibilityInfo['data']['susceptible']) === false) {
            throw new Exception('El Usuario no es candidato para el Certifica, actualiza tu información en el registro para cumplir con los requisitos.');
        }

        $userInfo = $this->provGetUserInfo($accessToken);

        if (! $userInfo) {
            return null;
        }

        $localToken = $this->generateLocalToken('django', $userInfo['data']['username'], $userInfo['data']);

        return [
            'token' => $localToken['token'],
            'user' => $localToken['user'],
            'permissions' => [],
        ];
    }

    /**
     * Intenta autenticar al usuario a través del SSO usando el código de autorización proporcionado por el cliente.
     *
     * @return array{token: string, user: User, permissions: array<mixed>}|null
     */
    public function loginBySSO(string $authorizationCode): ?array
    {
        if ($this->offlineMode) {
            return $this->loginOfflineAdmin();
        }

        $ssoData = $this->getTokenSSO($authorizationCode);

        if (! isset($ssoData['token'])) {
            throw new SSOAuthorizationException('La respuesta del SSO no contiene un token válido');
        }

        $localToken = $this->generateLocalToken('sso', $ssoData['user']['email'], $ssoData['user'], $ssoData['permissions'] ?? []);

        return [
            'token' => $localToken['token'],
            'user' => $localToken['user'],
            'permissions' => $ssoData['permissions'],
        ];
    }

    /**
     * Registra un usuario local para el modo reproducible.
     *
     * @return array{token: string, user: User, permissions: array<int, string>}
     */
    public function registerLocalUser(string $name, string $email, string $password): array
    {
        $userInfo = $this->buildLocalUserInfo($name, $email);

        $user = User::create([
            'auth_method_id' => AuthMethod::where('name', 'django')->value('id'),
            'username' => $email,
            'password' => Hash::make($password),
            'user' => $userInfo,
        ]);

        $token = $user->createToken('auth_token_local_user')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
            'permissions' => [],
        ];
    }

    /**
     * Cierra la sesión del usuario eliminando sus tokens de autenticación.
     *
     * @throws LogoutException Si ocurre un error al eliminar los tokens del usuario.
     */
    public function logout(User $user): void
    {
        try {
            $user->tokens()->delete();
        } catch (\Throwable $e) {
            throw new LogoutException(previous: $e);
        }
    }

    /**
     * Realiza la solicitud a Django para obtener un token de autenticación usando username y password.
     *
     * @return array<string, mixed>|null
     *
     * @throws DjangoConnectionException Si no es posible establecer conexión con Django.
     */
    private function provGetTokenByUsername(string $username, string $password): ?array
    {
        $url = $this->provDjangoBaseUrl.'/api/auth/token/';

        try {
            $response = Http::asForm()->post($url, [
                'username' => $username,
                'password' => $password,
            ]);
        } catch (ConnectionException $e) {
            throw new DjangoConnectionException(previous: $e);
        }

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * @return array{token: string, user: User, permissions: array<int, string>}|null
     */
    private function loginOfflineByUsername(string $username, string $password): ?array
    {
        $registeredUser = User::where('username', $username)->first();
        if ($registeredUser && $registeredUser->password && Hash::check($password, $registeredUser->password)) {
            $token = $registeredUser->createToken('auth_token_local_user')->plainTextToken;

            return [
                'token' => $token,
                'user' => $registeredUser,
                'permissions' => [],
            ];
        }

        if ($username === $this->offline['provider_username'] && $password === $this->offline['provider_password']) {
            return $this->loginOfflineProvider();
        }

        if ($username === $this->offline['admin_username'] && $password === $this->offline['admin_password']) {
            return $this->loginOfflineAdmin();
        }

        return null;
    }

    /**
     * @return array{token: string, user: User, permissions: array<int, string>}
     */
    private function loginOfflineProvider(): array
    {
        $userInfo = $this->buildLocalUserInfo('Usuario Demo', 'usuario.demo@example.test', $this->offline['provider_username']);

        $localToken = $this->generateLocalToken('django', $userInfo['username'], $userInfo);

        return [
            'token' => $localToken['token'],
            'user' => $localToken['user'],
            'permissions' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLocalUserInfo(string $name, string $email, ?string $username = null): array
    {
        return [
            'user_id' => crc32($email),
            'username' => $username ?? $email,
            'role' => 'user',
            'email' => $email,
            'rfc' => 'XAXX010101000',
            'provider_info' => [
                'nombre_completo' => $name,
                'razon_social' => $name,
                'rfc' => 'XAXX010101000',
            ],
        ];
    }

    /**
     * @return array{token: string, user: User, permissions: array<int, string>}
     */
    private function loginOfflineAdmin(): array
    {
        $permissions = [
            '2.admin.actividad.guardar',
            '2.admin.actividad.actualizar',
            '2.admin.actividad.eliminar',
            '2.admin.convocatoria.listar',
            '2.admin.convocatoria.crear',
            '2.admin.convocatoria.ver',
            '2.admin.convocatoria.actualizar',
            '2.admin.convocatoria.eliminar',
            '2.admin.landing.guardar',
            '2.admin.landing.actualizar',
            '2.admin.landing.eliminar',
            '2.admin.secciones.guardar',
            '2.admin.secciones.actualizar',
            '2.admin.secciones.eliminar',
            '2.validador.capacitacion.aceptar',
            '2.validador.capacitacion.concluir_expediente',
            '2.validador.dashboard.listar',
            '2.validador.dashboard.estatus.listar',
        ];

        $userInfo = [
            'id' => 2001,
            'username' => $this->offline['admin_username'],
            'name' => 'Administrador Demo',
            'role' => 'admin',
            'email' => 'admin.demo@example.test',
        ];

        $localToken = $this->generateLocalToken('sso', $userInfo['email'], $userInfo, $permissions);

        return [
            'token' => $localToken['token'],
            'user' => $localToken['user'],
            'permissions' => $permissions,
        ];
    }

    /**
     * Realiza la solicitud a Django para obtener un token de autenticación usando la e-firma.
     *
     * @return array<string, mixed>|null
     *
     * @throws DjangoConnectionException Si no es posible establecer conexión con Django.
     */
    private function provGetTokenAndUserInfoByEfirma(UploadedFile $cer, UploadedFile $key, string $password): ?array
    {
        $url = $this->provDjangoBaseUrl.'/api/auth/auth_fiel/';

        try {
            $cerContents = file_get_contents($cer->getRealPath());
            $keyContents = file_get_contents($key->getRealPath());

            if ($cerContents === false || $keyContents === false) {
                return null;
            }

            $response = Http::attach(
                'cer', $cerContents, $cer->getClientOriginalName(),
            )->attach(
                'key', $keyContents, $key->getClientOriginalName(),
            )->post($url, ['password' => $password]);
        } catch (ConnectionException $e) {
            throw new DjangoConnectionException(previous: $e);
        }

        if ($response->successful()) {
            $resp = $response->json();

            return $resp['data'] ?? null;
        }

        return null;
    }

    /**
     * Realiza la solicitud a Django para obtener información del usuario autenticado usando el token del usuario.
     *
     * @return array{data: array{user_id: int, username: string, role: string, email: string, rfc: string, provider_info: array<string, mixed>}}|null
     *
     * @throws DjangoConnectionException Si no es posible establecer conexión con Django.
     */
    private function provGetUserInfo(string $token): ?array
    {
        $url = $this->provDjangoBaseUrl.'/api/auth/user-info/';

        try {
            $response = Http::withToken($token)->get($url);
        } catch (ConnectionException $e) {
            throw new DjangoConnectionException(previous: $e);
        }

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Almacena o actualiza la información del usuario en la base de datos local.
     *
     * @param  array<string, mixed>  $userInfo
     */
    private function storeUserData(string $authType, string $username, array $userInfo): User
    {
        $authMethodId = AuthMethod::where('name', $authType)->value('id');

        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'auth_method_id' => $authMethodId,
                'user' => $userInfo,
            ],
        );

        return $user;
    }

    /**
     * Genera un token de autenticación local para el usuario autenticado a través del usuario.
     *
     * @param  array<string, mixed>  $userInfo
     * @param  array<int, string>  $permissions
     * @return array{token: string, user: User}
     */
    private function generateLocalToken(string $authType, string $username, array $userInfo, array $permissions = []): array
    {
        $storedUser = $this->storeUserData($authType, $username, $userInfo);

        $token = $storedUser->createToken('auth_token_prov_django', $permissions)->plainTextToken;

        return [
            'token' => $token,
            'user' => $storedUser,
        ];
    }

    /**
     * Realiza la autenticación a través del SSO usando el código de autorización proporcionado por el cliente.
     *
     * @return array<string, mixed>|null
     *
     * @throws SSOConnectionException Si no es posible establecer conexión con el servidor SSO.
     * @throws SSOAuthorizationException Si el SSO rechaza al usuario con un 403.
     */
    private function getTokenSSO(string $authorizationCode): ?array
    {
        $requestOptions = [];
        if ($this->sslPemPath) {
            // El certificado COMODIN del SSO lo solicita
            $requestOptions['verify'] = $this->sslPemPath;
        }

        $url = $this->sso['base_url'].'/api/clients/'.$this->sso['client_id'].'/token';

        try {
            $response = Http::withBasicAuth($this->sso['client_id'], $this->sso['client_secret'])
                ->withHeaders([
                    'Accept' => 'Application/json',
                ])
                ->withOptions($requestOptions)
                ->post($url, ['authorization_code' => $authorizationCode]);
        } catch (ConnectionException $e) {
            throw new SSOConnectionException(previous: $e);
        }

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->serverError()) {
            throw new SSOConnectionException('No fue posible conectar con el servidor SSO');
        }

        // Solo exponer el mensaje del SSO en errores explícitos de autorización (401/403)
        $body = $response->json();
        $ssoMessage = in_array($response->status(), [401, 403])
            ? ($body['error'] ?? $body['detail'] ?? null)
            : null;

        throw new SSOAuthorizationException(
            $ssoMessage ?? 'El acceso del usuario no ha sido autorizado por el administrador',
        );
    }

    /**
     * Realiza la solicitud a Django para validar la suceptibilidad del registro a certifica.

     *
     * @return array{
     *   code: int,
     *   message: string,
     *   data: array{
     *     motivo: string,
     *     susceptible: bool
     *   }
     * }
     *
     * @throws DjangoConnectionException Si no es posible establecer conexión con Django.
     */
    private function provGetSusceptibilityUser(string $token): ?array
    {
        $url = $this->provDjangoBaseUrl.'/api/provider/validacion-suceptibilidad';

        try {
            $response = Http::withToken($token)->get($url);
        } catch (ConnectionException $e) {
            throw new DjangoConnectionException(previous: $e);
        }

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
