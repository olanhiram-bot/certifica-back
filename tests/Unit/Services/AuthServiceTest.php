<?php

namespace Tests\Unit\Services;

use App\Exceptions\Auth\DjangoConnectionException;
use App\Exceptions\Auth\SSOAuthorizationException;
use App\Exceptions\Auth\SSOConnectionException;
use App\Models\AuthMethod;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.provedores.django_base_url', 'http://fake-provider.test');

        AuthMethod::create(['id' => 1, 'name' => 'django']);

        $this->service = new AuthService;
    }

    protected function getFakerUrlEFirma(): void
    {
        Http::fake([
            'http://fake-provider.test/*' => function ($request) {
                $url = $request->url();

                if (str_ends_with($url, '/api/auth/auth_fiel/') || str_ends_with($url, '/api/auth/auth_fiel/')) {
                    return Http::response(['data' => ['access' => 'efirma-token']], 200);
                }

                if (str_ends_with($url, '/api/auth/user-info/') || str_ends_with($url, '/api/auth/user-info')) {
                    return Http::response(['data' => ['username' => 'efirmauser']], 200);
                }

                if (str_ends_with($url, '/api/provider/validacion-suceptibilidad/') || str_ends_with($url, '/api/provider/validacion-suceptibilidad')) {
                    return Http::response(['data' => ['motivo' => 'texto motivo', 'susceptible' => true]], 200);
                }

                return Http::response([], 404);
            },
        ]);
    }

    /** @test */
    public function login_returns_array_with_token_user_and_permissions_on_success(): void
    {
        Http::fake([
            'http://fake-provider.test/*' => function ($request) {
                $url = $request->url();
                if (str_ends_with($url, '/api/auth/token/') || str_ends_with($url, '/api/auth/token')) {
                    return Http::response(['access' => 'fake-prov-token'], 200);
                }
                if (str_ends_with($url, '/api/auth/user-info/') || str_ends_with($url, '/api/auth/user-info')) {
                    return Http::response(['data' => ['username' => 'testuser']], 200);
                }

                if (str_ends_with($url, '/api/provider/validacion-suceptibilidad/') || str_ends_with($url, '/api/provider/validacion-suceptibilidad')) {
                    return Http::response(['data' => ['motivo' => 'texto motivo', 'susceptible' => true]], 200);
                }
            },
        ]);

        $result = $this->service->loginByUsername('testuser', 'password123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertNotEmpty($result['token']);
    }

    /** @test */
    public function login_returns_null_when_provider_token_request_fails(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/token/' => Http::response([], 401),
        ]);

        $result = $this->service->loginByUsername('testuser', 'wrongpass');

        $this->assertNull($result);
    }

    /** @test */
    public function login_returns_null_when_provider_user_info_request_fails(): void
    {

        Http::fake([
            'http://fake-provider.test/*' => function ($request) {
                $url = $request->url();

                if (str_ends_with($url, '/api/auth/token/') || str_ends_with($url, '/api/auth/token')) {
                    return Http::response(['access' => 'fake-prov-token'], 200);
                }

                if (str_ends_with($url, '/api/auth/user-info/') || str_ends_with($url, '/api/auth/user-info')) {
                    return Http::response([], 500);
                }

                if (str_ends_with($url, '/api/provider/validacion-suceptibilidad/') || str_ends_with($url, '/api/provider/validacion-suceptibilidad')) {
                    return Http::response(['data' => ['motivo' => 'texto motivo', 'susceptible' => true]], 200);
                }

                return Http::response([], 404);
            },
        ]);

        $result = $this->service->loginByUsername('testuser', 'password123');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // loginByEfirma
    // -------------------------------------------------------------------------

    /** @test */
    public function login_by_efirma_returns_array_with_token_user_and_permissions_on_success(): void
    {
        $this->getFakerUrlEFirma();

        $cer = UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
        $key = UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');

        $result = $this->service->loginByEfirma($cer, $key, 'efirma-password');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertNotEmpty($result['token']);
    }

    /** @test */
    public function login_by_efirma_persists_user_in_database_on_success(): void
    {
        $this->getFakerUrlEFirma();

        $cer = UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
        $key = UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');

        $this->service->loginByEfirma($cer, $key, 'efirma-password');

        $this->assertDatabaseHas('users', ['username' => 'efirmauser']);
    }

    /** @test */
    public function login_by_efirma_returns_null_when_django_efirma_endpoint_rejects_credentials(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => Http::response([], 401),
        ]);

        $cer = UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
        $key = UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');

        $result = $this->service->loginByEfirma($cer, $key, 'wrong-password');

        $this->assertNull($result);
    }

    /** @test */
    public function login_by_efirma_returns_null_when_efirma_response_has_no_data_key(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => Http::response(['other_key' => 'value'], 200),
        ]);

        $cer = UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
        $key = UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');

        $result = $this->service->loginByEfirma($cer, $key, 'efirma-password');

        $this->assertNull($result);
    }

    /** @test */
    public function login_by_efirma_returns_null_when_user_info_request_fails(): void
    {
        Http::fake([
            'http://fake-provider.test/*' => function ($request) {
                $url = $request->url();

                if (str_ends_with($url, '/api/auth/auth_fiel/') || str_ends_with($url, '/api/auth/auth_fiel/')) {
                    return Http::response(['data' => ['access' => 'efirma-token']], 200);
                }

                if (str_ends_with($url, '/api/auth/user-info/') || str_ends_with($url, '/api/auth/user-info')) {
                    return Http::response([], 500);
                }

                if (str_ends_with($url, '/api/provider/validacion-suceptibilidad/') || str_ends_with($url, '/api/provider/validacion-suceptibilidad')) {
                    return Http::response(['data' => ['motivo' => 'texto motivo', 'susceptible' => true]], 200);
                }

                return Http::response([], 404);
            },
        ]);

        $cer = UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
        $key = UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');

        $result = $this->service->loginByEfirma($cer, $key, 'efirma-password');

        $this->assertNull($result);
    }

    /** @test */
    public function login_by_efirma_throws_django_connection_exception_when_efirma_request_fails(): void
    {
        $this->expectException(DjangoConnectionException::class);

        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $cer = UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
        $key = UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');

        $this->service->loginByEfirma($cer, $key, 'efirma-password');
    }

    /** @test */
    public function login_by_efirma_throws_django_connection_exception_when_user_info_request_fails(): void
    {
        $this->expectException(DjangoConnectionException::class);

        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => Http::response(['data' => ['access' => 'efirma-token']], 200),
            'http://fake-provider.test/api/auth/user-info/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $cer = UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
        $key = UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');

        $this->service->loginByEfirma($cer, $key, 'efirma-password');
    }

    // -------------------------------------------------------------------------
    // loginBySSO
    // -------------------------------------------------------------------------

    /** @test */
    public function login_by_sso_returns_array_with_token_user_and_permissions_on_success(): void
    {
        Config::set('services.sso.base_url', 'http://fake-sso.test');
        Config::set('services.sso.client_id', 'fake-client');
        Config::set('services.sso.client_secret', 'fake-secret');
        Config::set('services.sso.ssl_pem_path', null);

        AuthMethod::create(['id' => 2, 'name' => 'sso']);

        $service = new AuthService;

        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'token' => 'sso-access-token',
                'user' => ['email' => 'ssouser'],
                'permissions' => ['read', 'write'],
            ], 200),
        ]);

        $result = $service->loginBySSO('auth-code-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertNotEmpty($result['token']);
        $this->assertEquals(['read', 'write'], $result['permissions']);
        $this->assertEquals('ssouser', $result['user']->username);
    }

    /** @test */
    public function login_by_sso_persists_user_in_database_on_success(): void
    {
        Config::set('services.sso.base_url', 'http://fake-sso.test');
        Config::set('services.sso.client_id', 'fake-client');
        Config::set('services.sso.client_secret', 'fake-secret');
        Config::set('services.sso.ssl_pem_path', null);

        AuthMethod::create(['id' => 2, 'name' => 'sso']);

        $service = new AuthService;

        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'token' => 'sso-access-token',
                'user' => ['email' => 'ssouser'],
                'permissions' => [],
            ], 200),
        ]);

        $service->loginBySSO('auth-code-123');

        $this->assertDatabaseHas('users', ['username' => 'ssouser']);
    }

    /** @test */
    public function login_by_sso_throws_sso_authorization_exception_when_sso_request_fails(): void
    {
        Config::set('services.sso.base_url', 'http://fake-sso.test');
        Config::set('services.sso.client_id', 'fake-client');
        Config::set('services.sso.client_secret', 'fake-secret');
        Config::set('services.sso.ssl_pem_path', null);

        $service = new AuthService;

        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([], 401),
        ]);

        $this->expectException(SSOAuthorizationException::class);

        $service->loginBySSO('invalid-code');
    }

    /** @test */
    public function login_by_sso_throws_sso_authorization_exception_when_response_has_no_token(): void
    {
        Config::set('services.sso.base_url', 'http://fake-sso.test');
        Config::set('services.sso.client_id', 'fake-client');
        Config::set('services.sso.client_secret', 'fake-secret');
        Config::set('services.sso.ssl_pem_path', null);

        $service = new AuthService;

        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'user' => ['email' => 'ssouser'],
                'permissions' => [],
            ], 200),
        ]);

        $this->expectException(SSOAuthorizationException::class);
        $this->expectExceptionMessage('La respuesta del SSO no contiene un token válido');

        $service->loginBySSO('auth-code-123');
    }

    /** @test */
    public function login_by_sso_throws_sso_connection_exception_when_sso_server_errors(): void
    {
        Config::set('services.sso.base_url', 'http://fake-sso.test');
        Config::set('services.sso.client_id', 'fake-client');
        Config::set('services.sso.client_secret', 'fake-secret');
        Config::set('services.sso.ssl_pem_path', null);

        $service = new AuthService;

        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([], 500),
        ]);

        $this->expectException(SSOConnectionException::class);

        $service->loginBySSO('auth-code-123');
    }

    // -------------------------------------------------------------------------
    // Connection exceptions
    // -------------------------------------------------------------------------

    /** @test */
    public function login_throws_django_connection_exception_when_token_request_fails(): void
    {
        $this->expectException(DjangoConnectionException::class);

        Http::fake([
            'http://fake-provider.test/api/auth/token/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $this->service->loginByUsername('testuser', 'password123');
    }

    /** @test */
    public function login_throws_django_connection_exception_when_user_info_request_fails(): void
    {
        $this->expectException(DjangoConnectionException::class);

        Http::fake([
            'http://fake-provider.test/api/auth/token/' => Http::response(['access' => 'prov-token'], 200),
            'http://fake-provider.test/api/auth/user-info/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $this->service->loginByUsername('testuser', 'password123');
    }

    /** @test */
    public function login_by_sso_throws_sso_connection_exception_when_request_fails(): void
    {
        Config::set('services.sso.base_url', 'http://fake-sso.test');
        Config::set('services.sso.client_id', 'fake-client');
        Config::set('services.sso.client_secret', 'fake-secret');
        Config::set('services.sso.ssl_pem_path', null);

        $this->expectException(SSOConnectionException::class);

        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        (new AuthService)->loginBySSO('auth-code-123');
    }
}
