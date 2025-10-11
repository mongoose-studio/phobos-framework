# Phobos Framework by MongooseStudio

> **Versión:** 3.0.2 <br>
> **PHP:** 8.3+

**PhobosFramework** es un framework PHP moderno, minimalista y de alto rendimiento, diseñado para construir APIs RESTful robustas y escalables. Toma inspiración de la ligereza de Slim y de la modularidad de Angular, pero su núcleo permanece libre de dependencias externas. Su sistema de routing es claro y flexible; admite parámetros tipo `:param`, wildcards avanzados (`*`, `**`) y módulos autocontenidos que ayudan a mantener la estructura en aplicaciones empresariales.

¿Que tiene de bueno esta versión? pues, encontrarás un `DI Container` con `autowiring` automático, `middleware` encadenados y compatibilidad con `PHP 8.3+` para asegurar tipos de forma completa. Suena muy técnico, pero entiéndelo de esta forma: se traduce en código más seguro, menos acoplamiento y pruebas más simples. Además, Phobos trae un `Observer` para debugging "en vivo", soporte `multi-tenant` nativo, versionado de APIs y orientado a API-first con conversión JSON automática. Desde microservicios hasta SSO servers, API gateways o backends para SPAs, Phobos da las herramientas necesarias sin complicar la experiencia del desarrollador.

Si trabajaste con `Phobos 1 o 2` —o incluso con `XWork (3 a 7)`— vas a reconocer el ADN: sigue siendo modular, con DAOs y rutas como pilares. Pero Phobos 3 se da un lavado de cara: mantiene lo bueno y suma cosas que hacen la vida del dev mucho más fácil. Piensa en helpers por todos lados, menos singletons por defecto, middleware e injections ordenadas, pipelines claros, un ciclo de vida más robusto y un observador que hace el debug menos doloroso. Añadimos servicios, configuración vía `.env`, `request`/`response` objects para entender exactamente qué pasa, librerías sólidas y un montón de pequeñas mejoras muy prácticas. En resumen: lo mismo de siempre, pero más limpio, más rápido, más amable y mas pulento XD 🇨🇱.
