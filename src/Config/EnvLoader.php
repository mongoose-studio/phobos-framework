<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que va distribuido con el código fuente.
 *
 * @package     PhobosFramework
 * @subpackage  Config
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Config;

use RuntimeException;

/**
 * Cargador de variables de entorno
 *
 * Esta clase se encarga de cargar y gestionar las variables de entorno desde un archivo .env.
 * Permite cargar, acceder y manipular variables de entorno de forma segura y eficiente.
 * Soporta la expansión de variables y el manejo de valores con comillas.
 */
class EnvLoader {

    private static array $env = [];
    private static bool $loaded = false;

    /**
     * Carga las variables de entorno desde un archivo .env
     *
     * Lee el archivo línea por línea, procesa cada variable y la almacena en la memoria.
     * Soporta comentarios, valores entre comillas y expansión de variables.
     *
     * @param string $path Ruta al archivo .env
     * @throws RuntimeException Si el archivo no existe
     */
    public static function load(string $path): void {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($path)) {
            throw new RuntimeException("Environment file not found: $path");
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
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtiene el valor de una variable de entorno
     *
     * Busca el valor en el siguiente orden:
     * 1. Array interno de variables
     * 2. Array global $_ENV
     * 3. Variables de entorno del sistema
     *
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto si la variable no existe
     * @return mixed Valor de la variable o el valor por defecto
     */
    public static function get(string $key, mixed $default = null): mixed {
        return self::$env[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Verifica si existe una variable de entorno
     *
     * Comprueba la existencia de la variable en el array interno,
     * en $_ENV y en las variables de entorno del sistema.
     *
     * @param string $key Nombre de la variable a verificar
     * @return bool True si la variable existe, false en caso contrario
     */
    public static function has(string $key): bool {
        return isset(self::$env[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Obtiene todas las variables de entorno cargadas
     *
     * Devuelve un array con todas las variables almacenadas
     * en el array interno de la clase.
     *
     * @return array Array asociativo con todas las variables
     */
    public static function all(): array {
        return self::$env;
    }

    /**
     * Limpia todas las variables de entorno cargadas
     *
     * Reinicia el estado interno de la clase, eliminando
     * todas las variables almacenadas y marcando como no cargado.
     */
    public static function clear(): void {
        self::$env = [];
        self::$loaded = false;
    }

    /**
     * Quita las comillas simples o dobles de un valor
     *
     * Elimina las comillas del principio y final de una cadena
     * si estas están presentes.
     *
     * @param string $value Valor a procesar
     * @return string Valor sin comillas
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
     * Expande las referencias a variables en el valor
     *
     * Procesa y reemplaza las referencias del tipo ${VAR} o $VAR
     * con sus valores correspondientes.
     *
     * @param string $value Valor que contiene referencias a variables
     * @return string Valor con las variables expandidas
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
