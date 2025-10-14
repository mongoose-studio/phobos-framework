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

use Override;

/**
 * HTTP Status Codes
 *
 * Enum completo de códigos de estado HTTP según la especificación.
 * Basado en Mozilla Developer Network (MDN) Web Docs.
 *
 * @link https://developer.mozilla.org/es/docs/Web/HTTP/Status
 * @package PhobosFramework\Http
 */
enum HttpStatus: int {

    // =========================================================================
    // 1xx - Informational Responses
    // =========================================================================

    /**
     * Esta respuesta provisional indica que todo hasta ahora está bien y que el cliente
     * debe continuar con la solicitud o ignorarla si ya está terminada.
     */
    case CONTINUE = 100;

    /**
     * Este código se envía en respuesta a un encabezado de solicitud Upgrade por el cliente
     * e indica que el servidor acepta el cambio de protocolo propuesto por el agente de usuario.
     */
    case SWITCHING_PROTOCOLS = 101;

    /**
     * (WebDAV) Este código indica que el servidor ha recibido la solicitud y aún se encuentra
     * procesándola, por lo que no hay respuesta disponible.
     */
    case PROCESSING = 102;

    /**
     * Este código de estado está pensado principalmente para ser usado con el encabezado Link,
     * permitiendo que el agente de usuario empiece a pre-cargar recursos mientras el servidor
     * prepara una respuesta.
     */
    case EARLY_HINTS = 103;

    // =========================================================================
    // 2xx - Successful Responses
    // =========================================================================

    /**
     * La solicitud ha tenido éxito. El significado de un éxito varía dependiendo del método HTTP.
     */
    case OK = 200;

    /**
     * La solicitud ha tenido éxito y se ha creado un nuevo recurso como resultado de ello.
     * Esta es típicamente la respuesta enviada después de una petición POST o PUT.
     */
    case CREATED = 201;

    /**
     * La solicitud se ha recibido, pero aún no se ha actuado. Es una petición "sin compromiso",
     * lo que significa que no hay manera en HTTP que permite enviar una respuesta asíncrona que
     * indique el resultado del procesamiento de la solicitud.
     */
    case ACCEPTED = 202;

    /**
     * La petición se ha completado con éxito, pero su contenido no se ha obtenido de la fuente
     * originalmente solicitada, sino que se recoge de una copia local o de un tercero.
     */
    case NON_AUTHORITATIVE_INFORMATION = 203;

    /**
     * La petición se ha completado con éxito, pero su respuesta no tiene ningún contenido,
     * aunque los encabezados pueden ser útiles.
     */
    case NO_CONTENT = 204;

    /**
     * La petición se ha completado con éxito, pero su respuesta no tiene contenidos y además,
     * el agente de usuario tiene que inicializar la página desde la que se realizó la petición.
     */
    case RESET_CONTENT = 205;

    /**
     * La petición servirá parcialmente el contenido solicitado. Esta característica es utilizada
     * por herramientas de descarga como wget para continuar la transferencia de descargas
     * anteriormente interrumpidas.
     */
    case PARTIAL_CONTENT = 206;

    /**
     * (WebDAV) Una respuesta Multi-Estado transmite información sobre varios recursos en
     * situaciones en las que varios códigos de estado podrían ser apropiados.
     */
    case MULTI_STATUS = 207;

    /**
     * (WebDAV) El listado de elementos DAV ya se notificó previamente, por lo que no se van
     * a volver a listar.
     */
    case ALREADY_REPORTED = 208;

    /**
     * (HTTP Delta encoding) El servidor ha cumplido una petición GET para el recurso y la
     * respuesta es una representación del resultado de una o más manipulaciones de instancia
     * aplicadas a la instancia actual.
     */
    case IM_USED = 226;

    // =========================================================================
    // 3xx - Redirection Messages
    // =========================================================================

    /**
     * Esta solicitud tiene más de una posible respuesta. User-Agent o el usuario debe escoger
     * uno de ellos. No hay forma estandarizada de seleccionar una de las respuestas.
     */
    case MULTIPLE_CHOICES = 300;

    /**
     * Este código de respuesta significa que la URI del recurso solicitado ha sido cambiado
     * permanentemente. Probablemente una nueva URI sea devuelta en la respuesta.
     */
    case MOVED_PERMANENTLY = 301;

    /**
     * Este código de respuesta significa que el recurso de la URI solicitada ha sido cambiado
     * temporalmente. Nuevos cambios en la URI serán agregados en el futuro.
     */
    case FOUND = 302;

    /**
     * El servidor envía esta respuesta para dirigir al cliente a un nuevo recurso solicitado
     * a otra dirección usando una petición GET.
     */
    case SEE_OTHER = 303;

    /**
     * Esta es usada para propósitos de "caché". Le indica al cliente que la respuesta no ha
     * sido modificada. Entonces, el cliente puede continuar usando la misma versión almacenada
     * en su caché.
     */
    case NOT_MODIFIED = 304;

    /**
     * @deprecated Fue definida en una versión previa de la especificación del protocolo HTTP.
     * Ha quedado obsoleta debido a preocupaciones de seguridad correspondientes a la configuración
     * de un proxy.
     */
    case USE_PROXY = 305;

    /**
     * Este código de respuesta ya no es usado más. Actualmente se encuentra reservado.
     */
    case UNUSED = 306;

    /**
     * El servidor envía esta respuesta para dirigir al cliente a obtener el recurso solicitado
     * a otra URI con el mismo método que se usó la petición anterior.
     */
    case TEMPORARY_REDIRECT = 307;

    /**
     * Significa que el recurso ahora se encuentra permanentemente en otra URI, especificada por
     * la respuesta de encabezado HTTP Location.
     */
    case PERMANENT_REDIRECT = 308;

    // =========================================================================
    // 4xx - Client Error Responses
    // =========================================================================

    /**
     * Esta respuesta significa que el servidor no pudo interpretar la solicitud dada una sintaxis
     * inválida.
     */
    case BAD_REQUEST = 400;

    /**
     * Es necesario autenticar para obtener la respuesta solicitada. Esta es similar a 403,
     * pero en este caso, la autenticación es posible.
     */
    case UNAUTHORIZED = 401;

    /**
     * Este código de respuesta está reservado para futuros usos. El objetivo inicial de crear
     * este código fue para ser utilizado en sistemas digitales de pagos.
     */
    case PAYMENT_REQUIRED = 402;

    /**
     * El cliente no posee los permisos necesarios para cierto contenido, por lo que el servidor
     * está rechazando otorgar una respuesta apropiada.
     */
    case FORBIDDEN = 403;

    /**
     * El servidor no pudo encontrar el contenido solicitado. Este código de respuesta es uno
     * de los más famosos dada su alta ocurrencia en la web.
     */
    case NOT_FOUND = 404;

    /**
     * El método solicitado es conocido por el servidor pero ha sido deshabilitado y no puede
     * ser utilizado. Los dos métodos obligatorios, GET y HEAD, nunca deben ser deshabilitados.
     */
    case METHOD_NOT_ALLOWED = 405;

    /**
     * Esta respuesta es enviada cuando el servidor, después de aplicar una negociación de
     * contenido servidor-impulsado, no encuentra ningún contenido seguido por la criteria
     * dada por el usuario.
     */
    case NOT_ACCEPTABLE = 406;

    /**
     * Esto es similar al código 401, pero la autenticación debe estar hecha a partir de un proxy.
     */
    case PROXY_AUTHENTICATION_REQUIRED = 407;

    /**
     * Esta respuesta es enviada en una conexión inactiva en algunos servidores, incluso sin
     * alguna petición previa por el cliente.
     */
    case REQUEST_TIMEOUT = 408;

    /**
     * Esta respuesta puede ser enviada cuando una petición tiene conflicto con el estado actual
     * del servidor.
     */
    case CONFLICT = 409;

    /**
     * Esta respuesta puede ser enviada cuando el contenido solicitado ha sido borrado del servidor.
     */
    case GONE = 410;

    /**
     * El servidor rechaza la petición porque el campo de encabezado Content-Length no está
     * definido y el servidor lo requiere.
     */
    case LENGTH_REQUIRED = 411;

    /**
     * El cliente ha indicado pre-condiciones en sus encabezados la cual el servidor no cumple.
     */
    case PRECONDITION_FAILED = 412;

    /**
     * La entidad de petición es más larga que los límites definidos por el servidor.
     */
    case PAYLOAD_TOO_LARGE = 413;

    /**
     * La URI solicitada por el cliente es más larga de lo que el servidor está dispuesto
     * a interpretar.
     */
    case URI_TOO_LONG = 414;

    /**
     * El formato multimedia de los datos solicitados no está soportado por el servidor, por
     * lo cual el servidor rechaza la solicitud.
     */
    case UNSUPPORTED_MEDIA_TYPE = 415;

    /**
     * El rango especificado por el campo de encabezado Range en la solicitud no cumple.
     */
    case RANGE_NOT_SATISFIABLE = 416;

    /**
     * Significa que la expectativa indicada por el campo de encabezado Expect solicitada no
     * puede ser cumplida por el servidor.
     */
    case EXPECTATION_FAILED = 417;

    /**
     * El servidor se rehúsa a intentar hacer café con una tetera.
     */
    case IM_A_TEAPOT = 418;

    /**
     * La petición fue dirigida a un servidor que no es capaz de producir una respuesta.
     */
    case MISDIRECTED_REQUEST = 421;

    /**
     * (WebDAV) La petición estaba bien formada pero no se pudo seguir debido a errores de semántica.
     */
    case UNPROCESSABLE_ENTITY = 422;

    /**
     * (WebDAV) El recurso que está siendo accedido está bloqueado.
     */
    case LOCKED = 423;

    /**
     * (WebDAV) La petición falló debido a una falla de una petición previa.
     */
    case FAILED_DEPENDENCY = 424;

    /**
     * Indica que el servidor no está dispuesto a arriesgar el procesamiento de una solicitud
     * que podría ser reproducida.
     */
    case TOO_EARLY = 425;

    /**
     * El servidor se rehúsa a aplicar la solicitud usando el protocolo actual pero puede estar
     * dispuesto a hacerlo después que el cliente se actualice a un protocolo diferente.
     */
    case UPGRADE_REQUIRED = 426;

    /**
     * El servidor origen requiere que la solicitud sea condicional.
     */
    case PRECONDITION_REQUIRED = 428;

    /**
     * El usuario ha enviado demasiadas solicitudes en un periodo de tiempo dado.
     */
    case TOO_MANY_REQUESTS = 429;

    /**
     * El servidor no está dispuesto a procesar la solicitud porque los campos de encabezado
     * son demasiado largos.
     */
    case REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

    /**
     * El usuario solicita un recurso ilegal, como alguna página web censurada por algún gobierno.
     */
    case UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    // =========================================================================
    // 5xx - Server Error Responses
    // =========================================================================

    /**
     * El servidor ha encontrado una situación que no sabe cómo manejarla.
     */
    case INTERNAL_SERVER_ERROR = 500;

    /**
     * El método solicitado no está soportado por el servidor y no puede ser manejado.
     */
    case NOT_IMPLEMENTED = 501;

    /**
     * Esta respuesta de error significa que el servidor, mientras trabaja como una puerta de
     * enlace para obtener una respuesta necesaria para manejar la petición, obtuvo una respuesta
     * inválida.
     */
    case BAD_GATEWAY = 502;

    /**
     * El servidor no está listo para manejar la petición. Causas comunes puede ser que el
     * servidor está caído por mantenimiento o está sobrecargado.
     */
    case SERVICE_UNAVAILABLE = 503;

    /**
     * Esta respuesta de error es dada cuando el servidor está actuando como una puerta de
     * enlace y no puede obtener una respuesta a tiempo.
     */
    case GATEWAY_TIMEOUT = 504;

    /**
     * La versión de HTTP usada en la petición no está soportada por el servidor.
     */
    case HTTP_VERSION_NOT_SUPPORTED = 505;

    /**
     * El servidor tiene un error de configuración interna: negociación de contenido transparente
     * para la petición resulta en una referencia circular.
     */
    case VARIANT_ALSO_NEGOTIATES = 506;

    /**
     * El servidor tiene un error de configuración interna: la variable de recurso escogida está
     * configurada para acoplar la negociación de contenido transparente misma.
     */
    case INSUFFICIENT_STORAGE = 507;

    /**
     * (WebDAV) El servidor detectó un ciclo infinito mientras procesaba la solicitud.
     */
    case LOOP_DETECTED = 508;

    /**
     * Extensiones adicionales para la solicitud son requeridas para que el servidor las cumpla.
     */
    case NOT_EXTENDED = 510;

    /**
     * El código de estado 511 indica que el cliente necesita autenticar para obtener acceso a la red.
     */
    case NETWORK_AUTHENTICATION_REQUIRED = 511;

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Obtener descripción textual del código de estado
     */
    public function text(): string {
        return match($this) {
            self::CONTINUE => 'Continue',
            self::SWITCHING_PROTOCOLS => 'Switching Protocols',
            self::PROCESSING => 'Processing',
            self::EARLY_HINTS => 'Early Hints',
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::ACCEPTED => 'Accepted',
            self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
            self::NO_CONTENT => 'No Content',
            self::RESET_CONTENT => 'Reset Content',
            self::PARTIAL_CONTENT => 'Partial Content',
            self::MULTI_STATUS => 'Multi-Status',
            self::ALREADY_REPORTED => 'Already Reported',
            self::IM_USED => 'IM Used',
            self::MULTIPLE_CHOICES => 'Multiple Choices',
            self::MOVED_PERMANENTLY => 'Moved Permanently',
            self::FOUND => 'Found',
            self::SEE_OTHER => 'See Other',
            self::NOT_MODIFIED => 'Not Modified',
            self::USE_PROXY => 'Use Proxy',
            self::UNUSED => 'Unused',
            self::TEMPORARY_REDIRECT => 'Temporary Redirect',
            self::PERMANENT_REDIRECT => 'Permanent Redirect',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::PAYMENT_REQUIRED => 'Payment Required',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::NOT_ACCEPTABLE => 'Not Acceptable',
            self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
            self::REQUEST_TIMEOUT => 'Request Timeout',
            self::CONFLICT => 'Conflict',
            self::GONE => 'Gone',
            self::LENGTH_REQUIRED => 'Length Required',
            self::PRECONDITION_FAILED => 'Precondition Failed',
            self::PAYLOAD_TOO_LARGE => 'Payload Too Large',
            self::URI_TOO_LONG => 'URI Too Long',
            self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
            self::RANGE_NOT_SATISFIABLE => 'Range Not Satisfiable',
            self::EXPECTATION_FAILED => 'Expectation Failed',
            self::IM_A_TEAPOT => "I'm a teapot",
            self::MISDIRECTED_REQUEST => 'Misdirected Request',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            self::LOCKED => 'Locked',
            self::FAILED_DEPENDENCY => 'Failed Dependency',
            self::TOO_EARLY => 'Too Early',
            self::UPGRADE_REQUIRED => 'Upgrade Required',
            self::PRECONDITION_REQUIRED => 'Precondition Required',
            self::TOO_MANY_REQUESTS => 'Too Many Requests',
            self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
            self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::NOT_IMPLEMENTED => 'Not Implemented',
            self::BAD_GATEWAY => 'Bad Gateway',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
            self::GATEWAY_TIMEOUT => 'Gateway Timeout',
            self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
            self::VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
            self::INSUFFICIENT_STORAGE => 'Insufficient Storage',
            self::LOOP_DETECTED => 'Loop Detected',
            self::NOT_EXTENDED => 'Not Extended',
            self::NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
        };
    }

    /**
     * Verificar si es código de información (1xx)
     */
    public function isInformational(): bool {
        return $this->value >= 100 && $this->value < 200;
    }

    /**
     * Verificar si es código de éxito (2xx)
     */
    public function isSuccessful(): bool {
        return $this->value >= 200 && $this->value < 300;
    }

    /**
     * Verificar si es código de redirección (3xx)
     */
    public function isRedirection(): bool {
        return $this->value >= 300 && $this->value < 400;
    }

    /**
     * Verificar si es código de error del cliente (4xx)
     */
    public function isClientError(): bool {
        return $this->value >= 400 && $this->value < 500;
    }

    /**
     * Verificar si es código de error del servidor (5xx)
     */
    public function isServerError(): bool {
        return $this->value >= 500 && $this->value < 600;
    }

    /**
     * Verificar si es cualquier tipo de error (4xx o 5xx)
     */
    public function isError(): bool {
        return $this->isClientError() || $this->isServerError();
    }

    /**
     * Crear desde código numérico (con validación)
     */
    public static function fromCode(int $code): self {
        return self::tryFrom($code) ?? self::INTERNAL_SERVER_ERROR;
    }
}
