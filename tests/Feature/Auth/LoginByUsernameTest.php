<?php

namespace Tests\Feature\Auth;

use App\Models\AuthMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginByUsernameTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/login/username';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.provedores.django_base_url', 'http://fake-provider.test');

        AuthMethod::create(['id' => 1, 'name' => 'django']);
    }

    /** @test */
    public function it_returns_token_on_successful_login(): void
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

                return Http::response([], 404);
            },
        ]);

        $response = $this->postJson($this->endpoint, [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user', 'permissions'],
            ]);
    }

    /** @test */
    public function it_returns_401_when_provider_rejects_credentials(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/token/' => Http::response([], 401),
        ]);

        $response = $this->postJson($this->endpoint, [
            'username' => 'wronguser',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['title' => 'Credenciales incorrectas', 'error' => 'El nombre de usuario o contraseña son incorrectos.']);
    }

    /** @test */
    public function it_returns_422_when_username_is_missing(): void
    {
        $response = $this->postJson($this->endpoint, [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /** @test */
    public function it_returns_503_when_django_is_unreachable_on_token_request(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/token/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $response = $this->postJson($this->endpoint, [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'No fue posible conectar con el proveedor de autenticación Django']);
    }

    /** @test */
    public function it_returns_503_when_django_is_unreachable_on_user_info_request(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/token/' => Http::response(['access' => 'fake-prov-token'], 200),
            'http://fake-provider.test/api/auth/user-info/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $response = $this->postJson($this->endpoint, [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'No fue posible conectar con el proveedor de autenticación Django']);
    }
}
