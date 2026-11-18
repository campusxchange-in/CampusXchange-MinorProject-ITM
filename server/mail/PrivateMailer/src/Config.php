<?php

namespace PrivateMailer;

class Config
{
    public static function get(string $key): ?string
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: null;
    }
}

?>