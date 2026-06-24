<?php

namespace App\Http\Controllers;

use App\Exceptions\Auth\DjangoConnectionException;
use App\Exceptions\Auth\LogoutException;
use App\Exceptions\Auth\SSOAuthorizationException;
use App\Exceptions\Auth\SSOConnectionException;
use App\Http\Requests\AuthLoginByEfirmaRequest;
use App\Http\Requests\AuthLoginBySSORequest;
use App\Http\Requests\AuthLoginByUsernameRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Resources\AuthResource;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    /**
     * @unauthenticated
     */
    public function register(AuthRegisterRequest $request): AuthResource
    {
        $authData = $this->authService->registerLocalUser(
            $request->input('name'),
            $request->input('email'),
            $request->input('password'),
        );

        return new AuthResource((object) $authData);
    }

    /**
     * @unauthenticated
     */
    public function loginByUsername(AuthLoginByUsernameRequest $request): AuthResource|JsonResponse
    {
        try {
            $authData = $this->authService->loginByUsername($request->input('username'), $request->input('password'));
        } catch (DjangoConnectionException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (Exception $e) {
            return response()->json(['title' => 'Usuario no susceptible', 'error' => $e->getMessage()], 401);
        }

        if ($authData) {
            return new AuthResource((object) $authData);
        }

        return response()->json(['title' => 'Credenciales incorrectas', 'error' => 'El nombre de usuario o contraseña son incorrectos.'], 401);
    }

    /**
     * @unauthenticated
     */
    public function loginByEfirma(AuthLoginByEfirmaRequest $request): AuthResource|JsonResponse
    {
        try {
            $authData = $this->authService->loginByEfirma(
                $request->file('cer'),
                $request->file('key'),
                $request->input('password'),
            );
        } catch (DjangoConnectionException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (Exception $e) {
            return response()->json(['title' => 'Usuario no susceptible', 'error' => $e->getMessage()], 401);
        }

        if ($authData) {
            return new AuthResource((object) $authData);
        }

        return response()->json(['title' => 'Credenciales incorrectas', 'error' => 'El nombre de usuario o contraseña son incorrectos.'], 401);
    }

    /**
     * @unauthenticated
     */
public function loginBySSO(AuthLoginBySSORequest $request): AuthResource|JsonResponse
    {
        try {
            $authData = $this->authService->loginBySSO($request->input('authorization_code'));
        } catch (SSOConnectionException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (SSOAuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }

        if ($authData) {
            return new AuthResource((object) $authData);
        }

return response()->json(['title' => 'Credenciales incorrectas', 'error' => 'El nombre de usuario o contraseña son incorrectos.'], 401);
}

/**
 * @unauthenticated
 */
public function forgotPassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => ['required', 'email', 'max:255'],
    ]);

    $resetUrl = $this->authService->sendPasswordResetLink($data['email']);

    $payload = [
        'message' => 'Si el correo está registrado, enviaremos un enlace para recuperar la cuenta.',
    ];

    if (app()->isLocal() && $resetUrl) {
        $payload['reset_url'] = $resetUrl;
    }

    return response()->json($payload);
}

/**
 * @unauthenticated
 */
public function resetPassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => ['required', 'email', 'max:255'],
        'token' => ['required', 'string'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    if (! $this->authService->resetPassword($data['email'], $data['token'], $data['password'])) {
        return response()->json([
            'message' => 'El enlace de recuperación no es válido o ya expiró.',
        ], 422);
    }

    return response()->json([
        'message' => 'Contraseña actualizada correctamente.',
    ]);
}

/**
 * @authenticated
 */
    public function logout(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        try {
            $this->authService->logout($user);
        } catch (LogoutException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }
}
