<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class SSOAuthorizationException extends RuntimeException
{
    public function __construct(string $message = 'El acceso del usuario no ha sido autorizado por el administrador')
    {
        parent::__construct($message);
    }
}
