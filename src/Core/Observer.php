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

namespace PhobosFramework\Core;

/**
 * Clase Observer para registrar y monitorear eventos del sistema
 *
 * Esta clase proporciona funcionalidades para registrar eventos,
 * monitorear el uso de memoria y el tiempo de ejecución
 */
class Observer {

    private static array $stack = [];
    private static int $maxStackSize = 100;
    private static bool $enabled = true;

    /**
     * Registra un evento en el stack de observación
     *
     * @param string $event Nombre o identificador del evento
     * @param array $context Datos adicionales del contexto del evento
     */
    public static function record(string $event, array $context = []): void {
        if (!self::$enabled) {
            return;
        }

        self::$stack[] = [
            'event' => $event,
            'context' => $context,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        // Limitar tamaño del stack
        if (count(self::$stack) > self::$maxStackSize) {
            array_shift(self::$stack);
        }
    }

    /**
     * Obtiene todo el stack de eventos registrados
     *
     * @return array Array con todos los eventos registrados
     */
    public static function dump(): array {
        return self::$stack;
    }

    /**
     * Obtiene el stack formateado con tiempos relativos y uso de memoria
     *
     * @return array Array con eventos formateados incluyendo tiempo transcurrido y memoria
     */
    public static function dumpFormatted(): array {
        if (empty(self::$stack)) {
            return [];
        }

        $startTime = self::$stack[0]['timestamp'];
        $formatted = [];

        foreach (self::$stack as $entry) {
            $formatted[] = [
                'event' => $entry['event'],
                'context' => $entry['context'],
                'elapsed_ms' => round(($entry['timestamp'] - $startTime) * 1000, 2),
                'memory_mb' => round($entry['memory'] / 1024 / 1024, 2),
                'peak_memory_mb' => round($entry['peak_memory'] / 1024 / 1024, 2),
            ];
        }

        return $formatted;
    }

    /**
     * Filtra y obtiene solo los eventos que coinciden con un patrón específico
     *
     * @param string $eventPattern Patrón para filtrar los eventos
     * @return array Array con los eventos filtrados
     */
    public static function filter(string $eventPattern): array {
        return array_filter(self::$stack, function ($entry) use ($eventPattern) {
            return str_starts_with($entry['event'], $eventPattern);
        });
    }

    /**
     * Limpia el stack eliminando todos los eventos registrados
     */
    public static function clear(): void {
        self::$stack = [];
    }

    /**
     * Habilita el registro de eventos
     */
    public static function enable(): void {
        self::$enabled = true;
    }

    public static function disable(): void {
        self::$enabled = false;
    }

    /**
     * Verifica si el observador está habilitado
     *
     * @return bool Estado actual del observador
     */
    public static function isEnabled(): bool {
        return self::$enabled;
    }

    /**
     * Configura el tamaño máximo del stack de eventos
     *
     * @param int $size Número máximo de eventos a mantener
     */
    public static function setMaxStackSize(int $size): void {
        self::$maxStackSize = $size;
    }

    /**
     * Obtiene un resumen del rendimiento incluyendo tiempo total y uso de memoria
     *
     * @return array Resumen con estadísticas de rendimiento
     */
    public static function summary(): array {
        if (empty(self::$stack)) {
            return [
                'total_events' => 0,
                'total_time_ms' => 0,
                'memory_used_mb' => 0,
            ];
        }

        $first = reset(self::$stack);
        $last = end(self::$stack);

        return [
            'total_events' => count(self::$stack),
            'total_time_ms' => round(($last['timestamp'] - $first['timestamp']) * 1000, 2),
            'memory_used_mb' => round($last['memory'] / 1024 / 1024, 2),
            'peak_memory_mb' => round($last['peak_memory'] / 1024 / 1024, 2),
        ];
    }
}
