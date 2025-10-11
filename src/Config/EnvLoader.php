<?php

namespace PhobosFramework\Config;

use RuntimeException;

/**
 * Cargador de variables de entorno
 */
class EnvLoader {

    private static array $env = [];
    private static bool $loaded = false;

    /**
     * Cargar archivo .env
     */
    public static function load(string $path): void {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($path)) {
            throw new RuntimeException("Environment file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentarios
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parsear línea KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Quitar comillas
                $value = self::stripQuotes($value);

                // Expandir variables ${VAR}
                $value = self::expandVariables($value);

                // Guardar en array interno
                self::$env[$key] = $value;

                // Establecer en $_ENV y putenv
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtener valor de variable de entorno
     */
    public static function get(string $key, mixed $default = null): mixed {
        return self::$env[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Verificar si existe una variable
     */
    public static function has(string $key): bool {
        return isset(self::$env[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Obtener todas las variables
     */
    public static function all(): array {
        return self::$env;
    }

    /**
     * Limpiar variables cargadas
     */
    public static function clear(): void {
        self::$env = [];
        self::$loaded = false;
    }

    /**
     * Quitar comillas de un valor
     */
    private static function stripQuotes(string $value): string {
        $value = trim($value);

        // Quitar comillas dobles
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return substr($value, 1, -1);
        }

        // Quitar comillas simples
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Expandir variables ${VAR} o $VAR
     */
    private static function expandVariables(string $value): string {
        // Expandir ${VAR}
        /** @noinspection RegExpRedundantEscape */
        $value = preg_replace_callback('/\$\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function($matches) {
            return self::get($matches[1], '');
        }, $value);

        // Expandir $VAR (sin llaves)
        return preg_replace_callback('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', function($matches) {
            return self::get($matches[1], '');
        }, $value);
    }
}
