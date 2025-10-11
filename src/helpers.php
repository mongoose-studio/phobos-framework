<?php

use PhobosFramework\Config\Config;
use PhobosFramework\Config\EnvLoader;
use PhobosFramework\Core\Container;
use PhobosFramework\Core\Phobos;
use PhobosFramework\Exceptions\BadRequestException;
use PhobosFramework\Exceptions\ForbiddenException;
use PhobosFramework\Exceptions\NotFoundException;
use PhobosFramework\Exceptions\UnauthorizedException;
use PhobosFramework\Exceptions\ValidationException;
use PhobosFramework\Http\Request;
use PhobosFramework\Http\ResponseFactory;

if (!function_exists('phobos')) {
    /**
     * Obtener instancia de Phobos o resolver del container
     *
     * @param string|null $abstract Clase a resolver del container
     * @param array $parameters Parámetros adicionales
     * @return mixed
     */
    function phobos(string $abstract = null, array $parameters = []): mixed {
        $instance = Phobos::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance?->getContainer()->make($abstract, $parameters);
    }
}

if (!function_exists('phb')) {
    /**
     * Alias corto de phobos()
     */
    function phb(string $abstract = null, array $parameters = []): mixed {
        return phobos($abstract, $parameters);
    }
}

if (!function_exists('phobos_version')) {
    /**
     * Obtener versión de Phobos
     */
    function phobos_version(): string {
        return '3.0.2';
    }
}

if (!function_exists('is_dev')) {
    /**
     * Verificar si está en modo desarrollo
     */
    function is_dev(): bool {
        return env('APP_ENV') === 'dev' || env('APP_ENV') === 'development' || !in_array(env('APP_DEBUG', false), [false, 'false', '0']);
    }
}

if (!function_exists('is_prod')) {
    /**
     * Verificar si está en modo producción
     */
    function is_prod(): bool {
        return env('APP_ENV') === 'prod' || env('APP_ENV') === 'production';
    }
}

if (!function_exists('inject')) {
    /**
     * Alias más explícito para resolver del container
     */
    function inject(string $abstract, array $parameters = []): mixed {
        return phobos($abstract, $parameters);
    }
}

if (!function_exists('instance')) {
    /**
     * Registrar una instancia existente
     */
    function instance(string $abstract, object $instance): void {
        container()->instance($abstract, $instance);
    }
}

if (!function_exists('env')) {
    /**
     * Obtener variable de entorno
     */
    function env(string $key, mixed $default = null): mixed {
        return EnvLoader::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Obtener valor de configuración
     */
    function config(string $key, mixed $default = null): mixed {
        return Config::get($key, $default);
    }
}

if (!function_exists('request')) {
    /**
     * Obtener request actual
     */
    function request(): ?Request {
        return phobos()->getRequest();
    }
}

if (!function_exists('response')) {
    /**
     * Crear una respuesta
     */
    function response(): ResponseFactory {
        return new ResponseFactory();
    }
}

if (!function_exists('route')) {
    /**
     * Generar URL para una ruta nombrada
     */
    function route(string $name, array $params = []): string {
        return phobos()->getRouter()->route($name, $params);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Obtener path del directorio storage
     */
    function storage_path(string $path = ''): string {
        $basePath = dirname(phobos()->getBasePath());
        return rtrim($basePath . '/storage/' . ltrim($path, '/'), '/');
    }
}

if (!function_exists('base_path')) {
    /**
     * Obtener path base de la aplicación
     */
    function base_path(string $path = ''): string {
        $basePath = dirname(phobos()->getBasePath());
        return rtrim($basePath . '/' . ltrim($path, '/'), '/');
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die (útil para debugging)
     */
    function dd(...$vars): never {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die(1);
    }
}

if (!function_exists('dpre')) {
    /**
     * Dump and die (útil para debugging)
     */
    function dpre(...$vars): never {
        foreach ($vars as $var) {
            echo '<pre>';
            print_r($var);
            echo '</pre>';
        }
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump sin morir
     */
    function dump(...$vars): void {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('container')) {
    /**
     * Obtener el container
     */
    function container(): ?Container {
        return phobos()->getContainer();
    }
}

if (!function_exists('singleton')) {
    /**
     * Registrar un singleton en el container
     */
    function singleton(string $abstract, mixed $concrete = null): void {
        container()->singleton($abstract, $concrete);
    }
}

if (!function_exists('bind')) {
    /**
     * Registrar un binding en el container
     */
    function bind(string $abstract, mixed $concrete = null): void {
        container()->bind($abstract, $concrete);
    }
}

if (!function_exists('config_path')) {
    /**
     * Obtener path del directorio config
     */
    function config_path(string $path = ''): string {
        $basePath = dirname(phobos()->getBasePath());
        return rtrim($basePath . '/config/' . ltrim($path, '/'), '/');
    }
}

if (!function_exists('public_path')) {
    /**
     * Obtener path del directorio public
     */
    function public_path(string $path = ''): string {
        $basePath = dirname(phobos()->getBasePath());
        return rtrim($basePath . '/public/' . ltrim($path, '/'), '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generar URL absoluta
     */
    function url(string $path = ''): string {
        $baseUrl = config('app.url', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('abort')) {
    /**
     * Lanzar una excepción HTTP
     */
    function abort(int $code, string $message = ''): never {
        throw match ($code) {
            404 => new NotFoundException($message ?: 'Not Found'),
            401 => new UnauthorizedException($message ?: 'Unauthorized'),
            403 => new ForbiddenException($message ?: 'Forbidden'),
            400 => new BadRequestException($message ?: 'Bad Request'),
            422 => new ValidationException([], $message ?: 'Validation Failed'),
            default => new HttpException(
                $message ?: 'Error',
                $code,
                'Error'
            ),
        };
    }

}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value
     */
    function value(mixed $value, ...$args): mixed {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value
     */
    function tap(mixed $value, ?callable $callback = null): mixed {
        if ($callback === null) {
            return $value;
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passed through the given callback
     */
    function with(mixed $value, ?callable $callback = null): mixed {
        return $callback === null ? $value : $callback($value);
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value (placeholder para futuro)
     */
    function collect(mixed $value = []): array {
        return is_array($value) ? $value : [$value];
    }
}

if (!function_exists('trace')) {
    /**
     * Registrar evento en el Observer
     */
    function trace(string $event, array $context = []): void {
        \PhobosFramework\Core\Observer::record($event, $context);
    }
}

if (!function_exists('observe')) {
    /**
     * Alias de trace()
     */
    function observe(string $event, array $context = []): void {
        trace($event, $context);
    }
}

if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank"
     */
    function blank(mixed $value): bool {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('filled')) {
    /**
     * Determine if a value is "filled"
     */
    function filled(mixed $value): bool {
        return !blank($value);
    }
}
