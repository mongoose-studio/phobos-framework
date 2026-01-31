<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que va distribuido con el código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Config;

use PhobosFramework\Core\Observer;
use RuntimeException;

/**
 * Sistema de configuración con soporte para archivos PHP
 *
 * Esta clase proporciona una interfaz para gestionar la configuración de la aplicación
 * mediante archivos PHP. Permite cargar, leer y modificar configuraciones usando
 * notación de puntos (dot notation) para acceder a valores anidados.
 *
 * Características principales:
 * - Carga lazy de archivos de configuración
 * - Cache de configuraciones en memoria
 * - Soporte para dot notation (ej: database.connections.mysql.host)
 * - Valores por defecto cuando no existe una configuración
 */
class Config {

    private static array $cache = [];
    private static ?string $configPath = null;

    /**
     * Establece la ruta base donde se encuentran los archivos de configuración
     *
     * Este método debe ser llamado antes de intentar acceder a cualquier configuración.
     * La ruta especificada debe apuntar al directorio que contiene los archivos .php
     * de configuración.
     *
     * @param string $path Ruta absoluta al directorio de configuraciones
     */
    public static function setPath(string $path): void {
        self::$configPath = rtrim($path, '/');

        Observer::record('config.path_set', [
            'path' => self::$configPath,
        ]);
    }

    /**
     * Obtiene un valor de configuración utilizando notación de puntos
     *
     * Permite acceder a valores de configuración anidados utilizando la notación de puntos.
     * Si la clave no existe, retorna el valor por defecto especificado.
     *
     * Ejemplos:
     * - config('app.name')
     * - config('database.connections.mysql.host')
     * - config('app.debug', false)
     *
     * @param string $key Clave de configuración en notación de puntos
     * @param mixed $default Valor por defecto si la clave no existe
     * @return mixed El valor de configuración o el valor por defecto
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
     * Establece un valor de configuración
     *
     * Permite establecer un valor de configuración utilizando notación de puntos.
     * Si las claves intermedias no existen, serán creadas como arrays vacíos.
     *
     * @param string $key Clave de configuración en notación de puntos
     * @param mixed $value Valor a establecer
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
     * Verifica si existe una clave de configuración
     *
     * Comprueba si una clave de configuración existe y tiene un valor no nulo.
     *
     * @param string $key Clave de configuración en notación de puntos
     * @return bool true si la clave existe y tiene un valor, false en caso contrario
     */
    public static function has(string $key): bool {
        return self::get($key) !== null;
    }

    /**
     * Obtiene todo el contenido de un archivo de configuración
     *
     * Retorna todo el array de configuración de un archivo específico.
     * Si el archivo no existe, retorna un array vacío.
     *
     * @param string $file Nombre del archivo de configuración sin extensión
     * @return array Contenido del archivo de configuración
     */
    public static function file(string $file): array {
        if (!isset(self::$cache[$file])) {
            self::load($file);
        }

        return self::$cache[$file] ?? [];
    }

    /**
     * Obtiene toda la configuración cargada en memoria
     *
     * Retorna un array con toda la configuración que ha sido cargada
     * hasta el momento en la caché.
     *
     * @return array Array con todas las configuraciones cargadas
     */
    public static function all(): array {
        return self::$cache;
    }

    /**
     * Carga un archivo de configuración en la caché
     *
     * Lee y valida un archivo de configuración PHP y almacena su contenido
     * en la caché. Si el archivo no existe, se registra en el sistema de
     * observación y se almacena un array vacío.
     *
     * @param string $file Nombre del archivo de configuración sin extensión
     * @throws RuntimeException Si el archivo no retorna un array
     */
    private static function load(string $file): void {
        if (self::$configPath === null) {
            throw new RuntimeException('Config path not set. Call Config::setPath() first.');
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
            throw new RuntimeException("Config file $file must return an array");
        }

        self::$cache[$file] = $config;

        Observer::record('config.loaded', [
            'file' => $file,
            'keys_count' => count($config),
        ]);
    }

    /**
     * Limpia la caché de configuración
     *
     * Elimina todas las configuraciones cargadas en memoria,
     * forzando que se vuelvan a cargar en la próxima solicitud.
     */
    public static function clear(): void {
        self::$cache = [];

        Observer::record('config.cleared');
    }

    /**
     * Recarga un archivo específico de configuración
     *
     * Elimina el archivo de la caché y lo vuelve a cargar desde el disco.
     * Útil cuando se han realizado cambios en el archivo de configuración.
     *
     * @param string $file Nombre del archivo de configuración sin extensión
     */
    public static function reload(string $file): void {
        unset(self::$cache[$file]);
        self::load($file);
    }

    /**
     * Obtiene la lista de archivos de configuración cargados
     *
     * Retorna un array con los nombres de todos los archivos
     * de configuración que han sido cargados en la caché.
     *
     * @return array Lista de nombres de archivos cargados
     */
    public static function getLoadedFiles(): array {
        return array_keys(self::$cache);
    }
}
