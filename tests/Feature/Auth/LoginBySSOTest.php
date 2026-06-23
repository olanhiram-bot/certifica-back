<?php

namespace Tests\Feature\Auth;

use App\Models\AuthMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginBySSOTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/login/sso';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.sso.base_url', 'http://fake-sso.test');
        Config::set('services.sso.client_id', 'fake-client');
        Config::set('services.sso.client_secret', 'fake-secret');
        Config::set('services.sso.ssl_pem_path', null);

        AuthMethod::create(['id' => 1, 'name' => 'django']);
        AuthMethod::create(['id' => 2, 'name' => 'sso']);
    }

    /** @test */
    public function it_returns_token_on_successful_sso_login(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'token' => 'sso-access-token',
                'user' => ['email' => 'ssouser'],
                'permissions' => ['read', 'write'],
            ], 200),
        ]);

        $response = $this->postJson($this->endpoint, [
            'authorization_code' => 'valid-auth-code',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user', 'permissions'],
            ]);
    }

    /** @test */
    public function it_returns_correct_permissions_from_sso_provider(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'token' => 'sso-access-token',
                'user' => ['email' => 'ssouser'],
                'permissions' => ['admin', 'editor'],
            ], 200),
        ]);

        $response = $this->postJson($this->endpoint, [
            'authorization_code' => 'valid-auth-code',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.permissions', ['admin', 'editor']);
    }

    /** @test */
    public function it_persists_user_in_database_on_successful_login(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'token' => 'sso-access-token',
                'user' => ['email' => 'newuser'],
                'permissions' => [],
            ], 200),
        ]);

        $this->postJson($this->endpoint, [
            'authorization_code' => 'valid-auth-code',
        ]);

        $this->assertDatabaseHas('users', ['username' => 'newuser']);
    }

    /** @test */
    public function it_returns_a_sanctum_token_as_string(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'token' => 'sso-access-token',
                'user' => ['email' => 'ssouser'],
                'permissions' => [],
            ], 200),
        ]);

        $response = $this->postJson($this->endpoint, [
            'authorization_code' => 'valid-auth-code',
        ]);

        $response->assertStatus(200);
        $this->assertIsString($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    /** @test */
    public function it_returns_403_when_sso_provider_rejects_authorization_code(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([], 401),
        ]);

        $response = $this->postJson($this->endpoint, [
            'authorization_code' => 'invalid-auth-code',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['error' => 'El acceso del usuario no ha sido autorizado por el administrador']);
    }

    /** @test */
    public function it_returns_403_when_sso_provider_responds_without_token(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([
                'user' => ['email' => 'ssouser'],
                'permissions' => [],
            ], 200),
        ]);

        $response = $this->postJson($this->endpoint, [
            'authorization_code' => 'code-without-token',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['error' => 'La respuesta del SSO no contiene un token válido']);
    }

    /** @test */
    public function it_returns_503_when_sso_server_returns_500(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => Http::response([], 500),
        ]);

        $response = $this->postJson($this->endpoint, [
            'authorization_code' => 'any-code',
        ]);

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'No fue posible conectar con el servidor SSO']);
    }

    /** @test */
    public function it_returns_422_when_authorization_code_is_missing(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['authorization_code']);
    }

    /** @test */
    public function it_returns_422_when_authorization_code_exceeds_max_length(): void
    {
        $response = $this->postJson($this->endpoint, [
            'authorization_code' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['authorization_code']);
    }

    /** @test */
    public function it_returns_503_when_sso_server_is_unreachable(): void
    {
        Http::fake([
            'http://fake-sso.test/api/clients/fake-client/token' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $response = $this->postJson($this->endpoint, [
            'authorization_code' => 'any-code',
        ]);

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'No fue posible conectar con el servidor SSO']);
    }
}
