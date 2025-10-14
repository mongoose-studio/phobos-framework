<?php

/**
 * # Phobos Framework
 *
 * Para obtener la información completa acerca del copyright y la licencia,
 * por favor consulte el archivo LICENSE que se distribuye con el código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Core;

/**
 * Clase base abstracta para proveedores de servicios (Service Providers)
 *
 * Esta clase proporciona la estructura base para registrar y configurar
 * servicios en el contenedor de dependencias de la aplicación.
 */
abstract class ServiceProvider implements ServiceProviderInterface {

    /**
     * Determina si el proveedor debe ser cargado de forma diferida
     *
     * Si es true, el proveedor solo se cargará cuando sea necesario,
     * en lugar de cargarse en cada solicitud.
     */
    protected bool $defer = false;

    /**
     * Lista de servicios que este proveedor registra en el contenedor
     *
     * Define los identificadores de los servicios que este proveedor
     * es capaz de registrar en el contenedor de dependencias.
     */
    protected array $provides = [];

    /**
     * Registra los servicios del proveedor en el contenedor
     *
     * Este método debe implementar la lógica necesaria para registrar
     * todos los servicios que proporciona este proveedor.
     */
    abstract public function register(Container $container): void;

    /**
     * Realiza la inicialización de los servicios registrados
     *
     * Este método se ejecuta después de que todos los proveedores
     * han sido registrados. Puede sobrescribirse para agregar
     * lógica de inicialización adicional.
     */
    public function boot(Container $container): void {
        // Sobrescribir si es necesario
    }

    /**
     * Verifica si el proveedor debe cargarse de forma diferida
     *
     * @return bool true si el proveedor es diferido, false en caso contrario
     */
    public function isDeferred(): bool {
        return $this->defer;
    }

    /**
     * Obtiene la lista de servicios que este proveedor puede registrar
     *
     * @return array Lista de identificadores de servicios
     */
    public function provides(): array {
        return $this->provides;
    }
}
