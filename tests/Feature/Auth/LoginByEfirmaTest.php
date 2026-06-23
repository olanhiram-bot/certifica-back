<?php

namespace Tests\Feature\Auth;

use App\Models\AuthMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginByEfirmaTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/login/efirma';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.provedores.django_base_url', 'http://fake-provider.test');

        AuthMethod::create(['id' => 1, 'name' => 'django']);
    }

    private function makeCerFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('certificate.cer', 'fake-cer-content');
    }

    private function makeKeyFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('private.key', 'fake-key-content');
    }

    private function postEfirma(array $data): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['Accept' => 'application/json'])
            ->post($this->endpoint, $data);
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    /** @test */
    public function it_returns_token_on_successful_login(): void
    {

        Http::fake([
            'http://fake-provider.test/*' => function ($request) {
                $url = $request->url();

                if (str_ends_with($url, '/api/auth/auth_fiel/') || str_ends_with($url, '/api/auth/auth_fiel/')) {
                    return Http::response(['data' => ['access' => 'fake-efirma-token']], 200);
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

        $response = $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'key' => $this->makeKeyFile(),
            'password' => 'efirma-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user', 'permissions'],
            ]);
    }

    /** @test */
    public function it_creates_user_in_database_on_successful_login(): void
    {
        Http::fake([
            'http://fake-provider.test/*' => function ($request) {
                $url = $request->url();

                if (str_ends_with($url, '/api/auth/auth_fiel/') || str_ends_with($url, '/api/auth/auth_fiel/')) {
                    return Http::response(['data' => ['access' => 'fake-efirma-token']], 200);
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

        $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'key' => $this->makeKeyFile(),
            'password' => 'efirma-password',
        ]);

        $this->assertDatabaseHas('users', ['username' => 'efirmauser']);
    }

    // -------------------------------------------------------------------------
    // Credenciales rechazadas / respuesta inválida
    // -------------------------------------------------------------------------

    /** @test */
    public function it_returns_401_when_provider_rejects_credentials(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => Http::response([], 401),
        ]);

        $response = $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'key' => $this->makeKeyFile(),
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['title' => 'Credenciales incorrectas', 'error' => 'El nombre de usuario o contraseña son incorrectos.']);
    }

    /** @test */
    public function it_returns_401_when_efirma_response_has_no_data_key(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => Http::response(['other_key' => 'value'], 200),
        ]);

        $response = $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'key' => $this->makeKeyFile(),
            'password' => 'efirma-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['title' => 'Credenciales incorrectas', 'error' => 'El nombre de usuario o contraseña son incorrectos.']);
    }

    // -------------------------------------------------------------------------
    // Validación de campos requeridos
    // -------------------------------------------------------------------------

    /** @test */
    public function it_returns_422_when_cer_file_is_missing(): void
    {
        $response = $this->postEfirma([
            'key' => $this->makeKeyFile(),
            'password' => 'efirma-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cer']);
    }

    /** @test */
    public function it_returns_422_when_key_file_is_missing(): void
    {
        $response = $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'password' => 'efirma-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    /** @test */
    public function it_returns_422_when_password_is_missing(): void
    {
        $response = $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'key' => $this->makeKeyFile(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // Errores de conexión con Django
    // -------------------------------------------------------------------------

    /** @test */
    public function it_returns_503_when_django_is_unreachable_on_efirma_request(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $response = $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'key' => $this->makeKeyFile(),
            'password' => 'efirma-password',
        ]);

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'No fue posible conectar con el proveedor de autenticación Django']);
    }

    /** @test */
    public function it_returns_503_when_django_is_unreachable_on_user_info_request(): void
    {
        Http::fake([
            'http://fake-provider.test/api/auth/auth_fiel/' => Http::response(['data' => ['access' => 'fake-efirma-token']], 200),
            'http://fake-provider.test/api/auth/user-info/' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $response = $this->postEfirma([
            'cer' => $this->makeCerFile(),
            'key' => $this->makeKeyFile(),
            'password' => 'efirma-password',
        ]);

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'No fue posible conectar con el proveedor de autenticación Django']);
    }
}
