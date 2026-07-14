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

namespace PhobosFramework\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Core\Phobos;
use ReflectionClass;
use RuntimeException;

/**
 * Pruebas de la resolución de rutas de Phobos::init() (raíz-primero).
 *
 * init() recibe la RAÍZ del proyecto; el directorio de la app se deriva (raíz/app) o
 * se pasa explícito como segundo argumento. Incluye el guard de migración que detecta
 * el patrón viejo (pasar el dir app/ como si fuera la raíz).
 */
class PhobosPathsTest extends TestCase {
    private string $root;

    protected function setUp(): void {
        $this->resetSingleton();

        $this->root = sys_get_temp_dir() . '/phobos_paths_' . uniqid();
        mkdir($this->root . '/app', 0777, true);
        mkdir($this->root . '/config', 0777, true);
    }

    protected function tearDown(): void {
        $this->resetSingleton();
        $this->rmrf($this->root);
    }

    public function test_single_arg_treats_it_as_root_and_derives_app(): void {
        Phobos::init($this->root);

        $this->assertSame($this->root, base_path());
        $this->assertSame($this->root . '/app', app_path());
        $this->assertSame($this->root . '/config', config_path());
        $this->assertSame($this->root . '/config/app.php', config_path('app.php'));
        $this->assertSame($this->root . '/storage', storage_path());
        $this->assertSame($this->root . '/public', public_path());
    }

    public function test_explicit_app_path_overrides_default(): void {
        Phobos::init($this->root, $this->root . '/src');

        $this->assertSame($this->root, base_path());
        $this->assertSame($this->root . '/src', app_path());
        $this->assertSame($this->root . '/src/Modules', app_path('Modules'));
    }

    public function test_trailing_slash_is_normalized(): void {
        Phobos::init($this->root . '/');

        $this->assertSame($this->root, base_path());
        $this->assertSame($this->root . '/app', app_path());
    }

    public function test_loads_env_from_root(): void {
        file_put_contents($this->root . '/.env', "PHOBOS_PATHS_TEST=hello\n");

        Phobos::init($this->root)->loadEnvironment();

        $this->assertSame('hello', env('PHOBOS_PATHS_TEST'));
    }

    public function test_migration_guard_throws_when_app_dir_passed_as_root(): void {
        // Patrón viejo: se pasa el dir app/ y el .env vive un nivel más arriba (la raíz).
        file_put_contents($this->root . '/.env', "X=1\n");

        Phobos::init($this->root . '/app');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('espera la raíz del proyecto');

        phobos()->loadEnvironment();
    }

    public function test_no_env_anywhere_does_not_throw(): void {
        // Sin .env ni en la app ni en su padre: comportamiento normal (silencioso).
        Phobos::init($this->root);

        $this->assertInstanceOf(Phobos::class, phobos()->loadEnvironment());
    }

    private function resetSingleton(): void {
        $ref = new ReflectionClass(Phobos::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    private function rmrf(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = "$dir/$item";
            is_dir($path) ? $this->rmrf($path) : unlink($path);
        }
        rmdir($dir);
    }
}
