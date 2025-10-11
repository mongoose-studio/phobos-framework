<?php


namespace PhobosFramework\Core;

use PhobosFramework\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionException;
use Closure;

/**
 * Dependency Injection Container
 *
 * Features:
 * - Autowiring mediante Reflection
 * - Singleton vs Transient bindings
 * - Interface to Implementation binding
 * - Factory bindings
 * - Resolución recursiva de dependencias
 */
class Container {

    /**
     * Bindings registrados (clase/interface => resolver)
     */
    private array $bindings = [];

    /**
     * Instancias singleton
     */
    private array $instances = [];

    /**
     * Aliases (shortcuts)
     */
    private array $aliases = [];

    /**
     * Stack para detectar dependencias circulares
     */
    private array $buildStack = [];

    /**
     * Registrar un binding (transient - nueva instancia cada vez)
     */
    public function bind(string $abstract, mixed $concrete = null): void {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => false,
        ];

        Observer::record('container.bind', [
            'abstract' => $abstract,
            'concrete' => is_string($concrete) ? $concrete : gettype($concrete),
        ]);
    }

    /**
     * Registrar un singleton (una sola instancia compartida)
     */
    public function singleton(string $abstract, mixed $concrete = null): void {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => true,
        ];

        Observer::record('container.singleton', [
            'abstract' => $abstract,
            'concrete' => is_string($concrete) ? $concrete : gettype($concrete),
        ]);
    }

    /**
     * Registrar una instancia existente (siempre devuelve la misma)
     */
    public function instance(string $abstract, object $instance): void {
        $this->instances[$abstract] = $instance;

        Observer::record('container.instance', [
            'abstract' => $abstract,
            'class' => get_class($instance),
        ]);
    }

    /**
     * Crear un alias (shortcut)
     */
    public function alias(string $alias, string $abstract): void {
        $this->aliases[$alias] = $abstract;

        Observer::record('container.alias', [
            'alias' => $alias,
            'abstract' => $abstract,
        ]);
    }

    /**
     * Resolver una dependencia del container
     */
    public function make(string $abstract, array $parameters = []): mixed {
        Observer::record('container.resolving', [
            'abstract' => $abstract,
        ]);

        // Resolver alias si existe
        $abstract = $this->getAlias($abstract);

        // Si ya existe una instancia singleton, devolverla
        if (isset($this->instances[$abstract])) {
            Observer::record('container.resolved_from_instance', [
                'abstract' => $abstract,
            ]);
            return $this->instances[$abstract];
        }

        // Obtener el concrete del binding o usar el abstract
        $concrete = $this->getConcrete($abstract);

        // Construir la instancia
        $object = $this->build($concrete, $parameters);

        // Si es singleton, guardarlo
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        Observer::record('container.resolved', [
            'abstract' => $abstract,
            'class' => get_class($object),
        ]);

        return $object;
    }

    /**
     * Alias para make()
     */
    public function get(string $abstract, array $parameters = []): mixed {
        return $this->make($abstract, $parameters);
    }

    /**
     * Verificar si un binding existe
     */
    public function has(string $abstract): bool {
        $abstract = $this->getAlias($abstract);

        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || class_exists($abstract);
    }

    /**
     * Verificar si es singleton
     */
    public function isShared(string $abstract): bool {
        $abstract = $this->getAlias($abstract);

        return isset($this->instances[$abstract])
            || (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']);
    }

    /**
     * Construir una instancia con sus dependencias
     */
    private function build(mixed $concrete, array $parameters = []): object {
        // Si es un Closure, ejecutarlo
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        // Si no es una clase, lanzar error
        if (!is_string($concrete) || !class_exists($concrete)) {
            throw new ContainerException("Target [{$concrete}] is not instantiable.");
        }

        // Detectar dependencias circulares
        if (in_array($concrete, $this->buildStack)) {
            throw new ContainerException(
                "Circular dependency detected: " . implode(' -> ', $this->buildStack) . " -> {$concrete}"
            );
        }

        $this->buildStack[] = $concrete;

        try {
            $reflector = new ReflectionClass($concrete);

            // Verificar si es instanciable
            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Target [{$concrete}] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            // Si no tiene constructor, instanciar directamente
            if ($constructor === null) {
                array_pop($this->buildStack);
                return new $concrete();
            }

            // Resolver dependencias del constructor
            $dependencies = $this->resolveDependencies(
                $constructor->getParameters(),
                $parameters
            );

            array_pop($this->buildStack);

            return $reflector->newInstanceArgs($dependencies);

        } catch (ReflectionException $e) {
            array_pop($this->buildStack);
            throw new ContainerException("Error building [{$concrete}]: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolver dependencias de un constructor/método
     */
    private function resolveDependencies(array $parameters, array $primitives = []): array {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // Si se pasó explícitamente, usar ese valor
            if (array_key_exists($name, $primitives)) {
                $dependencies[] = $primitives[$name];
                continue;
            }

            // Obtener tipo del parámetro
            $type = $parameter->getType();

            // Si no tiene tipo y tiene valor por defecto, usarlo
            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new ContainerException(
                    "Unresolvable dependency [{$name}] in class " . $parameter->getDeclaringClass()->getName()
                );
            }

            $typeName = $type->getName();

            // Si es un tipo primitivo
            if ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new ContainerException(
                    "Unresolvable primitive dependency [{$typeName} \${$name}]"
                );
            }

            // Resolver clase/interface recursivamente
            try {
                $dependencies[] = $this->make($typeName);
            } catch (ContainerException $e) {
                // Si falla y tiene valor por defecto, usarlo
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull()) {
                    $dependencies[] = null;
                } else {
                    throw $e;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Obtener el concrete de un abstract
     */
    private function getConcrete(string $abstract): mixed {
        // Si no hay binding, usar el abstract como concrete
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * Resolver alias
     */
    private function getAlias(string $abstract): string {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Llamar un método con inyección de dependencias
     * @throws ReflectionException
     */
    public function call(callable|array $callback, array $parameters = []): mixed {
        Observer::record('container.calling', [
            'callback' => is_array($callback)
                ? (is_string($callback[0]) ? $callback[0] : get_class($callback[0])) . '::' . $callback[1]
                : 'Closure',
        ]);

        if ($callback instanceof Closure) {
            $reflector = new ReflectionFunction($callback);
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);
            return $callback(...$dependencies);
        }

        if (is_array($callback)) {
            [$class, $method] = $callback;

            // Resolver clase si es string
            if (is_string($class)) {
                $class = $this->make($class);
            }

            $reflector = new ReflectionMethod($class, $method);
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

            return $reflector->invokeArgs($class, $dependencies);
        }

        throw new ContainerException("Invalid callback provided");
    }

    /**
     * Limpiar el container
     */
    public function flush(): void {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->buildStack = [];

        Observer::record('container.flushed');
    }

    /**
     * Obtener todos los bindings
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Obtener todas las instancias singleton
     */
    public function getInstances(): array {
        return $this->instances;
    }
}
