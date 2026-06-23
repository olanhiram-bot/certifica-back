<?php

namespace App\Exceptions\Auth;

use RuntimeException;

/**
 * Se lanza cuando no es posible establecer conexión con el servicio de autenticacion Django.
 *
 * Esto ocurre ante errores de red, tiempo de espera agotado o servicio no disponible
 * al intentar comunicarse con la API de Django.
 */
class DjangoConnectionException extends RuntimeException
{
    public function __construct(
        string $message = 'No fue posible conectar con el servicio de autenticacion Django',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
