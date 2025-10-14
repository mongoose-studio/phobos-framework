<?php

/**
 * # Phobos Framework
 *
 * Para obtener información completa sobre el copyright y la licencia,
 * por favor consulte el archivo LICENSE que se distribuye con el código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */


namespace PhobosFramework\Core;

/**
 * Interface base para Service Providers (Proveedores de Servicios)
 *
 * Esta interfaz define los métodos necesarios para implementar un proveedor de servicios
 * en el framework Phobos. Los proveedores de servicios son responsables de registrar
 * y configurar servicios en el contenedor de dependencias.
 */
interface ServiceProviderInterface {

    /**
     * Registra los servicios en el contenedor de dependencias.
     *
     * Este método se utiliza para registrar los servicios, bindings y configuraciones
     * necesarias en el contenedor de dependencias del framework.
     *
     * @param Container $container El contenedor de dependencias
     */
    public function register(Container $container): void;

    /**
     * Inicializa los servicios registrados.
     *
     * Este método se ejecuta después de que todos los proveedores de servicios
     * han registrado sus servicios. Es útil para realizar configuraciones
     * que requieren que otros servicios ya estén registrados.
     *
     * @param Container $container El contenedor de dependencias
     */
    public function boot(Container $container): void;
}
