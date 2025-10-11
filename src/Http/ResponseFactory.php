<?php

namespace PhobosFramework\Http;

/**
 * Factory para crear respuestas de forma fluida
 */
class ResponseFactory {

    /**
     * Crear respuesta JSON
     */
    public function json(mixed $data, int $status = 200, array $headers = []): Response {
        return Response::json($data, $status, $headers);
    }

    /**
     * Crear respuesta de error
     */
    public function error(string $message, int $status = 400, array $extra = []): Response {
        return Response::error($message, $status, $extra);
    }

    /**
     * Crear respuesta vacía
     */
    public function empty(int $status = 204): Response {
        return Response::empty($status);
    }

    /**
     * Crear respuesta de texto
     */
    public function text(string $content, int $status = 200): Response {
        return Response::text($content, $status);
    }

    /**
     * Crear respuesta HTML
     */
    public function html(string $content, int $status = 200): Response {
        return Response::html($content, $status);
    }

    /**
     * Crear respuesta de descarga
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
     * Crear respuesta de redirección
     */
    public function redirect(string $url, int $status = 302): Response {
        return new Response('', $status, [
            'Location' => $url,
        ]);
    }
}
