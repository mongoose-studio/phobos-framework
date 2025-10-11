<?php

namespace PhobosFramework\Config;

use PhobosFramework\Core\Observer;

/**
 * Sistema de configuración con soporte para archivos PHP
 */
class Config {

    private static array $cache = [];
    private static ?string $configPath = null;

    /**
     * Establecer path de configuraciones
     */
    public static function setPath(string $path): void {
        self::$configPath = rtrim($path, '/');

        Observer::record('config.path_set', [
            'path' => self::$configPath,
        ]);
    }

    /**
     * Obtener valor de configuración usando dot notation
     *
     * Ejemplos:
     * - config('app.name')
     * - config('database.connections.mysql.host')
     * - config('app.debug', false)
     */
    public static function get(string $key, mixed $default = null): mixed {
        // Separar por punto: 'database.connections.mysql.host'
        $parts = explode('.', $key);
        $file = array_shift($parts); // 'database'

        // Cargar archivo si no está en cache
        if (!isset(self::$cache[$file])) {
            self::load($file);
        }

        // Si el archivo no existe, retornar default
        if (!isset(self::$cache[$file])) {
            return $default;
        }

        // Navegar por el array usando dot notation
        $value = self::$cache[$file];

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Establecer valor de configuración
     */
    public static function set(string $key, mixed $value): void {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        // Asegurar que el archivo está cargado
        if (!isset(self::$cache[$file])) {
            self::load($file);
        }

        // Si no hay más partes, establecer todo el archivo
        if (empty($parts)) {
            self::$cache[$file] = $value;
            return;
        }

        // Navegar y establecer el valor
        $current = &self::$cache[$file];

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $current[$part] = $value;
            } else {
                if (!isset($current[$part]) || !is_array($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }
    }

    /**
     * Verificar si existe una clave
     */
    public static function has(string $key): bool {
        return self::get($key) !== null;
    }

    /**
     * Obtener todo un archivo de configuración
     */
    public static function file(string $file): array {
        if (!isset(self::$cache[$file])) {
            self::load($file);
        }

        return self::$cache[$file] ?? [];
    }

    /**
     * Obtener toda la configuración
     */
    public static function all(): array {
        return self::$cache;
    }

    /**
     * Cargar un archivo de configuración
     */
    private static function load(string $file): void {
        if (self::$configPath === null) {
            throw new \RuntimeException('Config path not set. Call Config::setPath() first.');
        }

        $path = self::$configPath . '/' . $file . '.php';

        if (!file_exists($path)) {
            Observer::record('config.file_not_found', [
                'file' => $file,
                'path' => $path,
            ]);

            self::$cache[$file] = [];
            return;
        }

        Observer::record('config.loading', [
            'file' => $file,
            'path' => $path,
        ]);

        $config = require $path;

        if (!is_array($config)) {
            throw new \RuntimeException("Config file {$file} must return an array");
        }

        self::$cache[$file] = $config;

        Observer::record('config.loaded', [
            'file' => $file,
            'keys_count' => count($config),
        ]);
    }

    /**
     * Limpiar cache
     */
    public static function clear(): void {
        self::$cache = [];

        Observer::record('config.cleared');
    }

    /**
     * Recargar un archivo
     */
    public static function reload(string $file): void {
        unset(self::$cache[$file]);
        self::load($file);
    }

    /**
     * Obtener archivos cargados
     */
    public static function getLoadedFiles(): array {
        return array_keys(self::$cache);
    }
}
