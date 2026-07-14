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
     * El valor se castea con {@see self::cast()}: un `.env` solo contiene texto, pero
     * `APP_DEBUG=false` debe llegar al código como el booleano `false`, no como el string
     * `"false"` (que en PHP es *truthy*, y por lo tanto dejaría el flag encendido siempre).
     *
     * El valor por defecto se devuelve únicamente cuando la variable **no existe**. Un
     * valor presente pero vacío (`DB_PASSWORD=`) o falsy (`APP_DEBUG=0`) se respeta tal
     * cual: si el usuario lo escribió, es lo que quiso decir.
     *
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto si la variable no existe
     * @return mixed Valor de la variable ya casteado, o el valor por defecto
     */
    public static function get(string $key, mixed $default = null): mixed {
        $value = self::$env[$key] ?? $_ENV[$key] ?? getenv($key);

        // getenv() devuelve false cuando la variable no existe: ese es el único
        // caso en que corresponde el default.
        if ($value === false) {
            return $default;
        }

        return self::cast($value);
    }

    /**
     * Convierte un valor de texto del .env a su tipo nativo de PHP
     *
     * Un archivo .env solo puede contener texto, así que sin esta conversión todo llega
     * como string — y en PHP `"false"` es truthy. Se cubren los literales reconocidos por
     * convención; los números NO se castean a propósito, porque hay valores numéricos que
     * son semánticamente texto y perderían su forma (un `"007"` no es `7`, y un `"1.0"` de
     * versión no es el float `1.0`). Para esos, castea explícitamente en `config/`.
     *
     * @param mixed $value Valor crudo leído del entorno
     * @return mixed Valor con su tipo nativo, o el original si no hay conversión aplicable
     */
    private static function cast(mixed $value): mixed {
        if (!is_string($value)) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }

    /**
     * Obtiene el valor crudo, sin castear, tal como está escrito en el entorno
     *
     * Lo usa la expansión de variables: al interpolar `${VAR}` dentro de otro valor se
     * necesita el texto original. Si se usara {@see self::get()}, un `${APP_DEBUG}` se
     * expandiría al booleano `true` y PHP lo pegaría en la cadena como `"1"`.
     *
     * @param string $key Nombre de la variable
     * @param string $default Valor por defecto si la variable no existe
     * @return string Valor crudo de la variable
     */
    private static function getRaw(string $key, string $default = ''): string {
        $value = self::$env[$key] ?? $_ENV[$key] ?? getenv($key);

        return $value === false ? $default : (string)$value;
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
        // Se expande con el valor CRUDO: interpolar un valor ya casteado metería un
        // booleano en medio de una cadena, y PHP lo convertiría en "1" o en "".
        // Expandir ${VAR}
        /** @noinspection RegExpRedundantEscape */
        $value = preg_replace_callback('/\$\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($matches) {
            return self::getRaw($matches[1]);
        }, $value);

        // Expandir $VAR (sin llaves)
        return preg_replace_callback('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', function ($matches) {
            return self::getRaw($matches[1]);
        }, $value);
    }
}
