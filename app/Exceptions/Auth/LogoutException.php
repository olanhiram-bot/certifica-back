<?php

namespace App\Exceptions\Auth;

use RuntimeException;

/**
 * Se lanza cuando ocurre un error al intentar cerrar la sesión del usuario.
 *
 * Esto ocurre ante fallos al eliminar los tokens de autenticación del usuario,
 * por ejemplo, ante errores de base de datos u otros problemas inesperados.
 */
class LogoutException extends RuntimeException
{
    public function __construct(
        string $message = 'No fue posible cerrar la sesión del usuario',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
