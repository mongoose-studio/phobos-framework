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

namespace PhobosFramework\Middleware;

use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;
use PhobosFramework\Core\Observer;
use Closure;

/**
 * Pipeline para ejecutar middlewares en cadena
 */
class Pipeline {

    private Request $request;
    private array $middlewares = [];

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Establecer los middlewares a ejecutar
     */
    public function through(array $middlewares): self {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * Ejecutar el pipeline y terminar con el destino final
     */
    public function then(Closure $destination): Response {
        Observer::record('pipeline.start', [
            'middlewares' => array_map(fn($m) => is_string($m) ? $m : get_class($m), $this->middlewares),
        ]);

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        $response = $pipeline($this->request);

        Observer::record('pipeline.end', [
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }

    /**
     * Preparar el destino final (controlador)
     */
    private function prepareDestination(Closure $destination): Closure {
        return function(Request $request) use ($destination) {
            Observer::record('pipeline.destination', [
                'class' => 'Controller',
            ]);

            $result = $destination($request);

            // Si el resultado es un array, convertir a JSON response
            if (is_array($result)) {
                return Response::json($result);
            }

            // Si ya es una Response, devolverla
            if ($result instanceof Response) {
                return $result;
            }

            // Si es string, crear respuesta HTML
            if (is_string($result)) {
                return Response::html($result);
            }

            // Para otros tipos, intentar convertir a JSON
            return Response::json(['data' => $result]);
        };
    }

    /**
     * Crear el "carrier" que encapsula cada middleware
     */
    private function carry(): Closure {
        return function(Closure $stack, mixed $middleware) {
            return function(Request $request) use ($stack, $middleware) {
                // Instanciar middleware si es una clase
                if (is_string($middleware)) {
                    Observer::record('pipeline.middleware', [
                        'middleware' => $middleware,
                        'type' => 'before',
                    ]);

                    $middleware = new $middleware();
                }

                // Ejecutar middleware
                $response = $middleware->handle($request, $stack);

                Observer::record('pipeline.middleware', [
                    'middleware' => get_class($middleware),
                    'type' => 'after',
                ]);

                return $response;
            };
        };
    }
}
