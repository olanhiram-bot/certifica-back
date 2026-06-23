<?php

namespace App\Exceptions\Auth;

use RuntimeException;

/**
 * Se lanza cuando no es posible establecer conexión con el servidor SSO.
 *
 * Esto ocurre ante errores de red, tiempo de espera agotado o servicio no disponible
 * al intentar comunicarse con la API del SSO.
 */
class SSOConnectionException extends RuntimeException
{
    public function __construct(
        string $message = 'No fue posible conectar con el servidor SSO',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
