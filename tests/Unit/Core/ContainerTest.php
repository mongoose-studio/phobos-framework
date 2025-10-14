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
use PhobosFramework\Core\Container;
use PhobosFramework\Exceptions\ContainerException;

/**
 * Pruebas unitarias para la clase Container.
 *
 * Esta clase proporciona pruebas de funcionalidad para los métodos clave del Contenedor,
 * incluyendo enlace (binding), resolución, gestión de singletons, autowiring, paso de parámetros,
 * inyección de dependencias, detección de dependencias circulares y más.
 *
 * Verifica el comportamiento tanto de enlaces transitorios como compartidos (singleton),
 * comprueba la resolución de servicios y asegura el manejo adecuado de varios casos límite
 * como primitivas no resolubles, valores por defecto, dependencias nulas y dependencias circulares.
 */
class ContainerTest extends TestCase {
    private Container $container;

    protected function setUp(): void {
        $this->container = new Container();
    }

    protected function tearDown(): void {
        $this->container->flush();
    }

    public function test_can_bind_and_resolve_concrete_class(): void {
        $this->container->bind(SimpleService::class);
        $service = $this->container->make(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $service);
    }

    public function test_bind_returns_new_instance_each_time(): void {
        $this->container->bind(SimpleService::class);
        $service1 = $this->container->make(SimpleService::class);
        $service2 = $this->container->make(SimpleService::class);

        $this->assertNotSame($service1, $service2);
    }

    public function test_singleton_returns_same_instance(): void {
        $this->container->singleton(SimpleService::class);
        $service1 = $this->container->make(SimpleService::class);
        $service2 = $this->container->make(SimpleService::class);

        $this->assertSame($service1, $service2);
    }

    public function test_can_register_instance(): void {
        $instance = new SimpleService();
        $this->container->instance(SimpleService::class, $instance);
        $resolved = $this->container->make(SimpleService::class);

        $this->assertSame($instance, $resolved);
    }

    public function test_autowiring_resolves_dependencies(): void {
        $this->container->bind(SimpleService::class);
        $this->container->bind(ServiceWithDependency::class);

        $service = $this->container->make(ServiceWithDependency::class);

        $this->assertInstanceOf(ServiceWithDependency::class, $service);
        $this->assertInstanceOf(SimpleService::class, $service->getService());
    }

    public function test_can_bind_interface_to_implementation(): void {
        $this->container->bind(ServiceInterface::class, ConcreteService::class);
        $service = $this->container->make(ServiceInterface::class);

        $this->assertInstanceOf(ConcreteService::class, $service);
    }

    public function test_can_bind_using_closure(): void {
        $this->container->bind(SimpleService::class, function (Container $c) {
            $service = new SimpleService();
            $service->setValue('from-closure');
            return $service;
        });

        $service = $this->container->make(SimpleService::class);

        $this->assertEquals('from-closure', $service->getValue());
    }

    public function test_can_pass_parameters_to_make(): void {
        $this->container->bind(ServiceWithParameter::class);
        $service = $this->container->make(ServiceWithParameter::class, ['name' => 'test']);

        $this->assertEquals('test', $service->getName());
    }

    public function test_throws_exception_for_unresolvable_primitive(): void {
        $this->expectException(ContainerException::class);
        $this->container->make(ServiceWithUnresolvablePrimitive::class);
    }

    public function test_resolves_default_values(): void {
        $service = $this->container->make(ServiceWithDefaultValue::class);

        $this->assertEquals('default', $service->getName());
    }

    public function test_detects_circular_dependencies(): void {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->make(CircularA::class);
    }

    public function test_can_call_closure_with_dependency_injection(): void {
        $this->container->bind(SimpleService::class);

        $result = $this->container->call(function (SimpleService $service, string $name) {
            return $service->getValue() . '-' . $name;
        }, ['name' => 'test']);

        $this->assertEquals('default-test', $result);
    }

    public function test_can_call_class_method_with_dependency_injection(): void {
        $this->container->bind(SimpleService::class);
        $controller = new TestController();

        $result = $this->container->call([$controller, 'action'], [
            'id' => 123,
        ]);

        $this->assertEquals('action-default-123', $result);
    }

    public function test_has_returns_true_for_registered_binding(): void {
        $this->container->bind(SimpleService::class);

        $this->assertTrue($this->container->has(SimpleService::class));
    }

    public function test_has_returns_true_for_existing_class(): void {
        $this->assertTrue($this->container->has(SimpleService::class));
    }

    public function test_has_returns_false_for_non_existent_class(): void {
        $this->assertFalse($this->container->has('NonExistentClass'));
    }

    public function test_is_shared_returns_true_for_singleton(): void {
        $this->container->singleton(SimpleService::class);

        $this->assertTrue($this->container->isShared(SimpleService::class));
    }

    public function test_is_shared_returns_false_for_transient(): void {
        $this->container->bind(SimpleService::class);

        $this->assertFalse($this->container->isShared(SimpleService::class));
    }

    public function test_alias_resolves_to_original_binding(): void {
        $this->container->bind(SimpleService::class);
        $this->container->alias('simple', SimpleService::class);

        $service = $this->container->make('simple');

        $this->assertInstanceOf(SimpleService::class, $service);
    }

    public function test_flush_clears_all_bindings(): void {
        $this->container->bind(SimpleService::class);
        $this->container->singleton(ConcreteService::class);

        $this->container->flush();

        $this->assertEmpty($this->container->getBindings());
        $this->assertEmpty($this->container->getInstances());
    }
}

// Test fixtures

class SimpleService {
    private string $value = 'default';

    public function getValue(): string {
        return $this->value;
    }

    public function setValue(string $value): void {
        $this->value = $value;
    }
}

class ServiceWithDependency {
    public function __construct(private SimpleService $service) {
    }

    public function getService(): SimpleService {
        return $this->service;
    }
}

interface ServiceInterface {
}

class ConcreteService implements ServiceInterface {
}

class ServiceWithParameter {
    public function __construct(private string $name) {
    }

    public function getName(): string {
        return $this->name;
    }
}

class ServiceWithUnresolvablePrimitive {
    public function __construct(string $name) {
    }
}

class ServiceWithDefaultValue {
    public function __construct(private string $name = 'default') {
    }

    public function getName(): string {
        return $this->name;
    }
}

class CircularA {
    public function __construct(CircularB $b) {
    }
}

class CircularB {
    public function __construct(CircularA $a) {
    }
}

class TestController {
    public function action(SimpleService $service, int $id): string {
        return 'action-' . $service->getValue() . '-' . $id;
    }
}

class ServiceWithNullableDependency {
    public function __construct(private ?NonExistentService $service = null) {
    }

    public function getService(): ?NonExistentService {
        return $this->service;
    }
}

class NonExistentService {
}
