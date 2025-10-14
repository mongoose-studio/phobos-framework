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

use JsonSerializable;
use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;
use PhobosFramework\Core\Observer;
use Closure;

/**
 * Pipeline para ejecutar middlewares en cadena
 *
 * Esta clase implementa el patrón Pipeline para la ejecución secuencial de middlewares
 * en una aplicación web. Permite:
 * - Definir una serie de middlewares para procesar las peticiones HTTP
 * - Ejecutar los middlewares en un orden específico
 * - Transformar los resultados en respuestas HTTP válidas
 * - Registrar eventos durante la ejecución para debugging
 *
 * Los middlewares pueden ser especificados como:
 * - Nombres de clase (strings)
 * - Instancias de objetos middleware
 *
 * El pipeline garantiza que cada middleware:
 * 1. Recibe la petición HTTP actual
 * 2. Puede procesar la petición
 * 3. Puede pasar el control al siguiente middleware
 * 4. Puede modificar la respuesta antes de retornarla
 */
class Pipeline {

    private Request $request;
    private array $middlewares = [];

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Establece los middlewares que se ejecutarán en el pipeline
     *
     * Este método permite definir un array de middlewares que serán ejecutados
     * en secuencia. Los middlewares pueden ser especificados como:
     * - Nombres de clase (strings)
     * - Instancias de objetos middleware ya creados
     *
     * El orden de los middlewares en el array determina su orden de ejecución,
     * procesándose desde el primero hasta el último.
     *
     * @param array $middlewares Array de middlewares a ejecutar
     * @return self Retorna la instancia actual para permitir encadenamiento
     */
    public function through(array $middlewares): self {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * Ejecuta el pipeline con todos los middlewares en cadena y finaliza con el destino especificado
     *
     * Este método:
     * 1. Registra el inicio de la ejecución del pipeline
     * 2. Construye y ejecuta la cadena de middlewares en orden inverso
     * 3. Ejecuta el destino final (normalmente un controlador)
     * 4. Registra el fin de la ejecución con el código de estado
     * 5. Retorna la respuesta HTTP generada
     *
     * @param Closure $destination Función de destino que se ejecutará al final del pipeline
     * @return Response Respuesta HTTP generada por el pipeline
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
     * Prepara el destino final del pipeline convirtiendo el resultado en una respuesta HTTP
     *
     * Este método envuelve el destino final (normalmente un controlador) y asegura que
     * su resultado se convierta en una respuesta HTTP válida. Maneja diferentes tipos de retorno:
     * - Arrays: Convertidos a JSON response
     * - JsonSerializable: Convertidos usando jsonSerialize()
     * - Response: Retornados directamente
     * - Strings: Convertidos a respuesta HTML
     * - Otros: Convertidos a JSON con clave 'data'
     *
     * @param Closure $destination Función de destino a ejecutar
     * @return Closure Wrapper que convierte el resultado en Response
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

            // Si el resultado es un JsonSerializable obtener el array
            if ($result instanceof JsonSerializable) {
                return Response::json($result->jsonSerialize());
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
     * Crea el "carrier" que encapsula cada middleware en el pipeline
     *
     * Este método devuelve una función que:
     * 1. Instancia el middleware si se proporciona como string
     * 2. Encadena la ejecución del middleware actual con el siguiente en la pila
     * 3. Registra eventos de observación antes y después de ejecutar cada middleware
     *
     * @return Closure Función que maneja la ejecución encadenada de middlewares
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
