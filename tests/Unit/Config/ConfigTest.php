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

namespace PhobosFramework\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Config\Config;

/**
 * Pruebas unitarias para validar la funcionalidad y el comportamiento de la clase Config en el Framework Phobos.
 *
 * Esta suite de pruebas asegura que la clase Config carga, recupera, establece y gestiona correctamente los archivos
 * y valores de configuración. Además, valida escenarios como la carga diferida de archivos de configuración, el manejo
 * de claves faltantes y la limpieza de configuraciones en caché.
 */
class ConfigTest extends TestCase {
    private string $configPath;

    protected function setUp(): void {
        $this->configPath = sys_get_temp_dir() . '/phobos_test_config_' . uniqid();
        mkdir($this->configPath, 0777, true);

        // Create test config files
        file_put_contents($this->configPath . '/app.php', '<?php return [
            "name" => "Test App",
            "debug" => true,
            "version" => "1.0.0",
            "nested" => [
                "key" => "value",
                "deep" => [
                    "level" => "test"
                ]
            ]
        ];');

        file_put_contents($this->configPath . '/database.php', '<?php return [
            "default" => "mysql",
            "connections" => [
                "mysql" => [
                    "host" => "localhost",
                    "port" => 3306
                ]
            ]
        ];');

        Config::setPath($this->configPath);
    }

    protected function tearDown(): void {
        Config::clear();

        // Clean up test files
        if (is_dir($this->configPath)) {
            $files = glob($this->configPath . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->configPath);
        }
    }

    public function test_can_get_simple_config_value(): void {
        $name = Config::get('app.name');

        $this->assertEquals('Test App', $name);
    }

    public function test_can_get_nested_config_value(): void {
        $value = Config::get('app.nested.key');

        $this->assertEquals('value', $value);
    }

    public function test_can_get_deeply_nested_config_value(): void {
        $value = Config::get('app.nested.deep.level');

        $this->assertEquals('test', $value);
    }

    public function test_returns_default_for_missing_key(): void {
        $value = Config::get('app.nonexistent', 'default-value');

        $this->assertEquals('default-value', $value);
    }

    public function test_returns_default_for_missing_file(): void {
        $value = Config::get('nonexistent.key', 'default');

        $this->assertEquals('default', $value);
    }

    public function test_can_get_whole_config_file(): void {
        $config = Config::file('app');

        $this->assertIsArray($config);
        $this->assertEquals('Test App', $config['name']);
        $this->assertTrue($config['debug']);
    }

    public function test_can_set_config_value(): void {
        Config::set('app.new_key', 'new_value');
        $value = Config::get('app.new_key');

        $this->assertEquals('new_value', $value);
    }

    public function test_can_set_nested_config_value(): void {
        Config::set('app.nested.new', 'nested_value');
        $value = Config::get('app.nested.new');

        $this->assertEquals('nested_value', $value);
    }

    public function test_has_returns_true_for_existing_key(): void {
        $this->assertTrue(Config::has('app.name'));
    }

    public function test_has_returns_false_for_missing_key(): void {
        $this->assertFalse(Config::has('app.nonexistent'));
    }

    public function test_can_get_all_config(): void {
        // Force loading of both config files
        Config::get('app.name');
        Config::get('database.default');

        $all = Config::all();

        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('database', $all);
    }

    public function test_can_reload_config_file(): void {
        $original = Config::get('app.name');

        // Modify the file
        file_put_contents($this->configPath . '/app.php', '<?php return ["name" => "Modified"];');

        Config::reload('app');
        $modified = Config::get('app.name');

        $this->assertEquals('Test App', $original);
        $this->assertEquals('Modified', $modified);
    }

    public function test_can_clear_config_cache(): void {
        Config::get('app.name'); // Load config

        Config::clear();
        $all = Config::all();

        $this->assertEmpty($all);
    }

    public function test_get_loaded_files_returns_loaded_config_files(): void {
        Config::get('app.name');
        Config::get('database.default');

        $loaded = Config::getLoadedFiles();

        $this->assertContains('app', $loaded);
        $this->assertContains('database', $loaded);
    }

    public function test_config_files_are_lazy_loaded(): void {
        Config::clear();

        $loaded = Config::getLoadedFiles();
        $this->assertEmpty($loaded);

        Config::get('app.name');
        $loaded = Config::getLoadedFiles();

        $this->assertContains('app', $loaded);
        $this->assertNotContains('database', $loaded);
    }

    public function test_throws_exception_when_config_path_not_set(): void {
        Config::clear();
        $newConfigObject = new \ReflectionClass(Config::class);
        $property = $newConfigObject->getProperty('configPath');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config path not set');

        Config::get('app.name');
    }
}
