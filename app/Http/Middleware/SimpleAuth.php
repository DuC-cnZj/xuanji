<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Auth\Authenticatable;

class SimpleAuth
{
    public function handle($request, \Closure $next)
    {
        auth()->setUser($this->getAuthUser());

        return $next($request);
    }

    public function getAuthUser()
    {
        return new class() implements Authenticatable {
            public $id = 1;
            public $user_name = 'admin';

            public function logout()
            {
                return true;
            }

            public function getAuthIdentifierName()
            {
                return 'id';
            }

            public function getAuthIdentifier()
            {
                return $this->{$this->getAuthIdentifierName()};
            }

            public function getAuthPassword()
            {
                // TODO: Implement getAuthPassword() method.
            }

            public function getRememberToken()
            {
                // TODO: Implement getRememberToken() method.
            }

            public function setRememberToken($value)
            {
                // TODO: Implement setRememberToken() method.
            }

            public function getRememberTokenName()
            {
                // TODO: Implement getRememberTokenName() method.
            }
        };
    }
}
