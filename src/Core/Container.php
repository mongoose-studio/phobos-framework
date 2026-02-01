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

use PhobosFramework\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionException;
use ReflectionUnionType;
use ReflectionIntersectionType;
use Closure;

/**
 * Dependency Injection Container (DI Container)
 *
 * This class implements a dependency injection container that handles:
 * - Registration of bindings between interfaces and their implementations
 * - Automatic dependency resolution
 * - Singleton instance management
 * - Aliases for easy access
 * - Circular dependency detection
 *
 * Contenedor de Inyección de Dependencias (DI Container)
 *
 * Esta clase implementa un contenedor de inyección de dependencias que maneja:
 * - Registro de vinculaciones (bindings) entre interfaces y sus implementaciones
 * - Resolución automática de dependencias
 * - Gestión de instancias singleton
 * - Alias para facilitar el acceso
 * - Detección de dependencias circulares
 */
class Container {

    /**
     * Almacena las vinculaciones (bindings) registradas en el contenedor
     * Estructura: ['abstract' => ['concrete' => mixed, 'shared' => bool]]
     */
    private array $bindings = [];

    /**
     * Almacena las instancias singleton que serán reutilizadas
     * Estructura: ['abstract' => object]
     */
    private array $instances = [];

    /**
     * Almacena los alias (nombres alternativos) para las vinculaciones
     * Estructura: ['alias' => 'abstract']
     */
    private array $aliases = [];

    /**
     * Pila utilizada para detectar dependencias circulares durante la construcción
     * Almacena las clases que están siendo construidas actualmente
     */
    private array $buildStack = [];

    /**
     * Registra una vinculación en el contenedor.
     *
     * Este método permite registrar cómo debe resolverse una abstracción (interfaz o clase).
     * Si no se especifica una implementación concreta, se usará la misma abstracción.
     * La vinculación se creará como una nueva instancia cada vez que se solicite.
     *
     * @param string $abstract El identificador abstracto (interfaz o clase)
     * @param mixed|null $concrete La implementación concreta (clase, closure o null)
     * @return void
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
     * Registra un singleton en el contenedor.
     *
     * Similar a bind(), pero la instancia creada se reutilizará en toda la aplicación.
     * Solo se creará una instancia la primera vez que se solicite, y las siguientes
     * solicitudes recibirán la misma instancia.
     *
     * @param string $abstract El identificador abstracto (interfaz o clase)
     * @param mixed|null $concrete La implementación concreta (clase, closure o null)
     * @return void
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
     * Registra una instancia existente en el contenedor.
     *
     * Permite compartir una instancia ya creada a través del contenedor.
     * Esta instancia se comportará como un singleton, siendo la misma
     * retornada para todas las solicitudes del identificador abstracto.
     *
     * @param string $abstract El identificador abstracto para acceder a la instancia
     * @param object $instance La instancia del objeto a compartir
     * @return void
     */
    public function instance(string $abstract, object $instance): void {
        $this->instances[$abstract] = $instance;

        Observer::record('container.instance', [
            'abstract' => $abstract,
            'class' => get_class($instance),
        ]);
    }

    /**
     * Crea un alias para una vinculación existente.
     * Permite referenciar una vinculación usando un nombre alternativo.
     *
     * @param string $alias El nombre alternativo
     * @param string $abstract El identificador abstracto original
     * @return void
     */
    public function alias(string $alias, string $abstract): void {
        $this->aliases[$alias] = $abstract;

        Observer::record('container.alias', [
            'alias' => $alias,
            'abstract' => $abstract,
        ]);
    }

    /**
     * Resuelve y devuelve una instancia del contenedor.
     * Construye el objeto resolviendo todas sus dependencias recursivamente.
     *
     * @param string $abstract El identificador abstracto a resolver (interfaz o clase)
     * @param array $parameters Parámetros opcionales para la construcción del objeto
     * @return mixed La instancia construida y resuelta con todas sus dependencias
     * @throws ContainerException Si hay errores en la resolución de dependencias
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
     * Método alternativo para obtener una instancia del contenedor.
     *
     * Este método es un alias de make() y proporciona la misma funcionalidad
     * de resolución de dependencias y construcción de objetos.
     *
     * @param string $abstract El identificador abstracto a resolver
     * @param array $parameters Parámetros opcionales para la construcción
     * @return mixed La instancia construida y resuelta
     * @throws ContainerException
     */
    public function get(string $abstract, array $parameters = []): mixed {
        return $this->make($abstract, $parameters);
    }

    /**
     * Verifica si existe una vinculación en el contenedor.
     *
     * Comprueba si el identificador abstracto está registrado como:
     * - Una vinculación (binding)
     * - Una instancia singleton
     * - Una clase que existe en el sistema
     *
     * @param string $abstract El identificador abstracto a verificar
     * @return bool True si existe la vinculación, false en caso contrario
     */
    public function has(string $abstract): bool {
        $abstract = $this->getAlias($abstract);

        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || class_exists($abstract);
    }

    /**
     * Verifica si una vinculación está registrada como singleton.
     *
     * Comprueba si el identificador abstracto:
     * - Ya tiene una instancia guardada
     * - Está registrado como vinculación compartida
     *
     * @param string $abstract El identificador abstracto a verificar
     * @return bool True si es singleton, false en caso contrario
     */
    public function isShared(string $abstract): bool {
        $abstract = $this->getAlias($abstract);

        return isset($this->instances[$abstract])
            || (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']);
    }

    /**
     * Construye una instancia resolviendo todas sus dependencias.
     *
     * Este método maneja:
     * - Ejecución de Closures
     * - Verificación de clases instanciables
     * - Detección de dependencias circulares
     * - Resolución recursiva de dependencias del constructor
     *
     * @param mixed $concrete La implementación concreta a construir
     * @param array $parameters Parámetros opcionales para la construcción
     * @return object La instancia construida con sus dependencias
     * @throws ContainerException Si hay errores en la construcción
     */
    private function build(mixed $concrete, array $parameters = []): object {
        // Si es un Closure, ejecutarlo
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        // Si no es una clase, lanzar error
        if (!is_string($concrete) || !class_exists($concrete)) {
            throw new ContainerException("Target [$concrete] is not instantiable.");
        }

        // Detectar dependencias circulares
        if (in_array($concrete, $this->buildStack)) {
            throw new ContainerException(
                "Circular dependency detected: " . implode(' -> ', $this->buildStack) . " -> $concrete"
            );
        }

        $this->buildStack[] = $concrete;

        try {
            $reflector = new ReflectionClass($concrete);

            // Verificar si es instanciable
            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Target [$concrete] is not instantiable.");
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
            throw new ContainerException("Error building [$concrete]: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resuelve las dependencias de un constructor o método.
     *
     * Este método analiza los parámetros de un constructor o método y resuelve
     * cada una de sus dependencias, ya sean:
     * - Valores primitivos proporcionados explícitamente
     * - Valores por defecto del parámetro
     * - Instancias de clases que deben ser resueltas por el contenedor
     *
     * @param array $parameters Lista de parámetros a resolver
     * @param array $primitives Valores primitivos proporcionados explícitamente
     * @return array Lista de dependencias resueltas
     * @throws ContainerException Si una dependencia no puede ser resuelta
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
                    "Unresolvable dependency [$name] in class " . $parameter->getDeclaringClass()->getName()
                );
            }

            // Si es un Union type (ej: int|HttpStatus), intentar resolver
            if ($type instanceof ReflectionUnionType) {
                $resolved = false;

                foreach ($type->getTypes() as $unionType) {
                    if (!$unionType->isBuiltin()) {
                        try {
                            $dependencies[] = $this->make($unionType->getName());
                            $resolved = true;
                            break;
                        } catch (ContainerException) {

                        }
                    }
                }

                if (!$resolved) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                        continue;
                    }
                    throw new ContainerException(
                        "Unresolvable union type dependency [\$$name]"
                    );
                }
                continue;
            }

            if ($type instanceof ReflectionIntersectionType) {
                $types = $type->getTypes();
                $firstType = $types[0]->getName();

                try {
                    $instance = $this->make($firstType);
                    foreach ($types as $intersectionType) {
                        $typeName = $intersectionType->getName();
                        if (!$instance instanceof $typeName) {
                            throw new ContainerException(
                                "Resolved instance does not satisfy intersection type [$typeName]"
                            );
                        }
                    }

                    $dependencies[] = $instance;
                    continue;
                } catch (ContainerException) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                        continue;
                    }

                    throw new ContainerException(
                        "Unresolvable intersection type dependency [\$$name]"
                    );
                }
            }

            $typeName = $type->getName();

            // Si es un tipo primitivo
            if ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new ContainerException(
                    "Unresolvable primitive dependency [$typeName \$$name]"
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
     * Obtiene la implementación concreta de una abstracción.
     *
     * Busca en las vinculaciones registradas y devuelve la implementación
     * concreta asociada a una abstracción. Si no existe una vinculación,
     * devuelve la misma abstracción.
     *
     * @param string $abstract El identificador abstracto a resolver
     * @return mixed La implementación concreta o la misma abstracción
     */
    private function getConcrete(string $abstract): mixed {
        // Si no hay binding, usar el abstract como concrete
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * Resuelve un alias a su nombre original.
     *
     * Verifica si existe un alias registrado para el identificador dado
     * y devuelve el nombre original asociado. Si no existe un alias,
     * devuelve el mismo identificador.
     *
     * @param string $abstract El identificador a resolver
     * @return string El nombre original asociado al alias
     */
    private function getAlias(string $abstract): string {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Invoca un método con inyección de dependencias.
     *
     * Permite ejecutar un método o función resolviendo automáticamente
     * sus dependencias. Soporta:
     * - Closures
     * - Métodos estáticos
     * - Métodos de instancia
     *
     * @param callable|array $callback El método a invocar
     * @param array $parameters Parámetros opcionales para la invocación
     * @return mixed El resultado de la invocación
     * @throws ReflectionException Si hay errores de reflexión
     * @throws ContainerException Si el callback es inválido
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
     * Limpia el contenedor.
     *
     * Elimina todas las vinculaciones, instancias singleton,
     * aliases y la pila de construcción del contenedor,
     * dejándolo en su estado inicial.
     */
    public function flush(): void {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->buildStack = [];

        Observer::record('container.flushed');
    }

    /**
     * Obtiene todas las vinculaciones.
     *
     * Devuelve un array con todas las vinculaciones registradas
     * en el contenedor, incluyendo tanto las compartidas como
     * las no compartidas.
     *
     * @return array Las vinculaciones registradas
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Obtiene todas las instancias singleton.
     *
     * Devuelve un array con todas las instancias singleton
     * que han sido creadas y almacenadas en el contenedor.
     *
     * @return array Las instancias singleton almacenadas
     */
    public function getInstances(): array {
        return $this->instances;
    }
}
