<?php

namespace PhobosFramework\Core;

/**
 * Interface base para Service Providers
 */
interface ServiceProviderInterface {

    /**
     * Registrar servicios en el container
     */
    public function register(Container $container): void;

    /**
     * Bootstrap de servicios (ejecutado después de todos los register)
     */
    public function boot(Container $container): void;
}
