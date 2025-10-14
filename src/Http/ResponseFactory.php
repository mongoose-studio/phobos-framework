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

namespace PhobosFramework\Http;

/**
 * Factory para crear respuestas HTTP de forma fluida.
 *
 * Esta clase proporciona métodos convenientes para crear diferentes tipos de respuestas HTTP,
 * como JSON, texto plano, HTML, descargas y redirecciones.
 */
class ResponseFactory {

    /**
     * Crea una respuesta HTTP con contenido JSON.
     *
     * @param mixed $data Los datos a convertir a JSON
     * @param int $status Código de estado HTTP
     * @param array $headers Cabeceras HTTP adicionales
     * @return Response
     */
    public function json(mixed $data, int $status = 200, array $headers = []): Response {
        return Response::json($data, $status, $headers);
    }

    /**
     * Crea una respuesta HTTP de error.
     *
     * @param string $message Mensaje de error
     * @param int $status Código de estado HTTP
     * @param array $extra Datos adicionales del error
     * @return Response
     */
    public function error(string $message, int $status = 400, array $extra = []): Response {
        return Response::error($message, $status, $extra);
    }

    /**
     * Crea una respuesta HTTP vacía.
     *
     * @param int $status Código de estado HTTP
     * @return Response
     */
    public function empty(int $status = 204): Response {
        return Response::empty($status);
    }

    /**
     * Crea una respuesta HTTP con contenido de texto plano.
     *
     * @param string $content Contenido de texto
     * @param int $status Código de estado HTTP
     * @return Response
     */
    public function text(string $content, int $status = 200): Response {
        return Response::text($content, $status);
    }

    /**
     * Crea una respuesta HTTP con contenido HTML.
     *
     * @param string $content Contenido HTML
     * @param int $status Código de estado HTTP
     * @return Response
     */
    public function html(string $content, int $status = 200): Response {
        return Response::html($content, $status);
    }

    /**
     * Crea una respuesta HTTP para descargar un archivo.
     *
     * @param string $filePath Ruta al archivo a descargar
     * @param string|null $name Nombre del archivo para la descarga
     * @return Response
     * @throws \RuntimeException cuando el archivo no existe
     */
    public function download(string $filePath, string $name = null): Response {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $content = file_get_contents($filePath);

        return new Response($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'Content-Length' => strlen($content),
        ]);
    }

    /**
     * Crea una respuesta HTTP de redirección.
     *
     * @param string $url URL de destino
     * @param int $status Código de estado HTTP
     * @return Response
     */
    public function redirect(string $url, int $status = 302): Response {
        return new Response('', $status, [
            'Location' => $url,
        ]);
    }
}
