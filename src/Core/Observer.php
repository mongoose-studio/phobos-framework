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

class Observer {

    private static array $stack = [];
    private static int $maxStackSize = 100;
    private static bool $enabled = true;

    /**
     * Registrar un evento en el stack
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
     * Obtener todo el stack
     */
    public static function dump(): array {
        return self::$stack;
    }

    /**
     * Obtener stack formateado con tiempos relativos
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
     * Obtener solo eventos de un tipo específico
     */
    public static function filter(string $eventPattern): array {
        return array_filter(self::$stack, function ($entry) use ($eventPattern) {
            return str_starts_with($entry['event'], $eventPattern);
        });
    }

    /**
     * Limpiar el stack
     */
    public static function clear(): void {
        self::$stack = [];
    }

    /**
     * Habilitar/deshabilitar observador
     */
    public static function enable(): void {
        self::$enabled = true;
    }

    public static function disable(): void {
        self::$enabled = false;
    }

    /**
     * Verificar si está habilitado
     */
    public static function isEnabled(): bool {
        return self::$enabled;
    }

    /**
     * Configurar tamaño máximo del stack
     */
    public static function setMaxStackSize(int $size): void {
        self::$maxStackSize = $size;
    }

    /**
     * Obtener resumen de performance
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
