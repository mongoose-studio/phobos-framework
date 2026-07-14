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
use PhobosFramework\Config\EnvLoader;

/**
 * Pruebas unitarias del cargador de variables de entorno.
 *
 * El foco está en el casteo de tipos: un archivo .env solo contiene texto, y sin conversión
 * todo llega como string. Como en PHP el string "false" es *truthy*, un APP_DEBUG=false sin
 * castear deja el modo debug encendido para siempre — sin fallar, sin avisar. Lo mismo emitía
 * cabeceras CORS de credenciales que debían estar apagadas.
 */
class EnvLoaderTest extends TestCase {

    private string $envPath;

    protected function setUp(): void {
        EnvLoader::clear();
        $this->envPath = sys_get_temp_dir() . '/phobos_test_env_' . uniqid();
    }

    protected function tearDown(): void {
        EnvLoader::clear();

        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }

        foreach (['APP_DEBUG', 'APP_ENV', 'DB_PASSWORD', 'EMPTY_VALUE', 'ZERO', 'HOST', 'URL', 'VERSION', 'CODE'] as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
    }

    private function writeEnv(string $contents): void {
        file_put_contents($this->envPath, $contents);
        EnvLoader::load($this->envPath);
    }

    // ---------------------------------------------------------------- casteo de booleanos

    public function testCasteaFalseABooleanoYNoAStringTruthy(): void {
        $this->writeEnv("APP_DEBUG=false\n");

        // El bug histórico: devolvía el string "false", que es truthy.
        $this->assertFalse(EnvLoader::get('APP_DEBUG'));
        $this->assertIsBool(EnvLoader::get('APP_DEBUG'));
    }

    public function testCasteaTrueABooleano(): void {
        $this->writeEnv("APP_DEBUG=true\n");

        $this->assertTrue(EnvLoader::get('APP_DEBUG'));
        $this->assertIsBool(EnvLoader::get('APP_DEBUG'));
    }

    public function testElCasteoEsInsensibleAMayusculas(): void {
        $this->writeEnv("APP_DEBUG=FALSE\nAPP_ENV=True\n");

        $this->assertFalse(EnvLoader::get('APP_DEBUG'));
        $this->assertTrue(EnvLoader::get('APP_ENV'));
    }

    public function testCasteaNullYEmpty(): void {
        $this->writeEnv("DB_PASSWORD=null\nEMPTY_VALUE=empty\n");

        $this->assertNull(EnvLoader::get('DB_PASSWORD'));
        $this->assertSame('', EnvLoader::get('EMPTY_VALUE'));
    }

    // ------------------------------------------------- el default solo aplica si NO existe

    public function testUnValorVacioNoCaeAlDefault(): void {
        $this->writeEnv("DB_PASSWORD=\n");

        // Antes: el ?: hacía que un valor falsy cayera al default. Una password vacía
        // es una decisión legítima del usuario, no una variable ausente.
        $this->assertSame('', EnvLoader::get('DB_PASSWORD', 'secreto'));
    }

    public function testUnCeroExplicitoNoCaeAlDefault(): void {
        $this->writeEnv("ZERO=0\n");

        $this->assertSame('0', EnvLoader::get('ZERO', 'default'));
    }

    public function testUnFalseExplicitoGanaAlDefaultTrue(): void {
        $this->writeEnv("APP_DEBUG=false\n");

        // El caso que abría el agujero de CORS: default true, .env dice false.
        $this->assertFalse(EnvLoader::get('APP_DEBUG', true));
    }

    public function testDevuelveElDefaultCuandoLaVariableNoExiste(): void {
        $this->writeEnv("APP_ENV=production\n");

        $this->assertSame('fallback', EnvLoader::get('NO_EXISTE', 'fallback'));
        $this->assertNull(EnvLoader::get('NO_EXISTE'));
    }

    // ------------------------------------------------------------------- lo que NO se castea

    public function testLosNumerosSeMantienenComoTextoParaNoPerderSuForma(): void {
        $this->writeEnv("CODE=007\nVERSION=1.0\n");

        // Castear números rompería un "007" (que no es 7) o un "1.0" de versión.
        // El casteo numérico va en config/, explícito.
        $this->assertSame('007', EnvLoader::get('CODE'));
        $this->assertSame('1.0', EnvLoader::get('VERSION'));
    }

    // ------------------------------------------------------------------------- expansión

    public function testLaExpansionDeVariablesUsaElValorCrudo(): void {
        $this->writeEnv("HOST=localhost\nURL=http://\${HOST}:8080\n");

        $this->assertSame('http://localhost:8080', EnvLoader::get('URL'));
    }

    public function testLaExpansionDeUnBooleanoNoSeConvierteEnUno(): void {
        $this->writeEnv("APP_DEBUG=true\nURL=modo-\${APP_DEBUG}\n");

        // Si la expansión usara el valor casteado, PHP pegaría el bool como "1".
        $this->assertSame('modo-true', EnvLoader::get('URL'));
    }

    // ------------------------------------------------------------------------ is_dev()

    public function testIsDevRespetaAppDebugFalse(): void {
        $this->writeEnv("APP_ENV=production\nAPP_DEBUG=false\n");

        $this->assertFalse(is_dev());
    }

    public function testIsDevRespetaAppDebugTrue(): void {
        $this->writeEnv("APP_ENV=production\nAPP_DEBUG=true\n");

        // Regresión: con el casteo, un in_array laxo hacía que true coincidiera con
        // 'false' (PHP convierte el string a bool al comparar contra un booleano).
        $this->assertTrue(is_dev());
    }

    public function testIsDevDetectaElEntornoDeDesarrollo(): void {
        $this->writeEnv("APP_ENV=development\nAPP_DEBUG=false\n");

        $this->assertTrue(is_dev());
    }
}