# Changelog

Todos los cambios relevantes de Phobos Framework se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el proyecto
se adhiere a [Versionado Semántico](https://semver.org/lang/es/).

## [3.3.0] - 2026-07-14

Esta versión corrige dos errores en `env()` que hacían que el framework **ignorara en
silencio lo que estaba escrito en el `.env`**. Es un cambio de comportamiento: lee la
sección de actualización antes de subir de versión.

### Corregido

- **`env()` ahora castea los valores a su tipo nativo.** Un archivo `.env` solo contiene
  texto, así que antes todo llegaba como string — y en PHP el string `"false"` es
  *truthy*. Es decir, `APP_DEBUG=false` dejaba el modo debug **encendido para siempre**,
  sin fallar y sin avisar.

  El impacto no era solo el debug. La misma causa hacía que una configuración de CORS con
  `CORS_SUPPORTS_CREDENTIALS=false` emitiera igualmente la cabecera
  `Access-Control-Allow-Credentials: true`; combinada con una política de orígenes `*`
  (que refleja el `Origin` de quien llame), eso permitía que **cualquier sitio hiciera
  peticiones con las cookies del usuario** contra la API. Cualquier proyecto que exponga
  un flag booleano del `.env` estaba expuesto a la misma clase de fallo.

  Ahora se convierten `true`, `false`, `null` y `empty` (insensible a mayúsculas, y con
  sus variantes entre paréntesis: `(true)`, `(empty)`, …). Los **números se dejan como
  texto a propósito**: un `"007"` no es `7`, y un `"1.0"` de versión no es el float `1.0`.
  Para esos, castea explícitamente en `config/`.

- **El valor por defecto ya no se devuelve cuando el valor existe pero es falsy.** La
  expresión anterior era `... ?? getenv($key) ?: $default`, y como `??` tiene más
  precedencia que `?:`, cualquier valor falsy legítimo caía al default: un
  `DB_PASSWORD=` vacío, un `CORS_MAX_AGE=0`, un `APP_DEBUG=0`. Ahora el default se aplica
  **solo si la variable no existe**: si el usuario lo escribió, es lo que quiso decir.

- **`is_dev()`** ya no depende de comparar strings contra `['false', '0']`. Usa
  `filter_var(..., FILTER_VALIDATE_BOOL)`, que además cubre las formas que `env()` no
  castea (`1`/`0`, `yes`/`no`, `on`/`off`).

- **La expansión de variables (`${VAR}`) usa el valor crudo.** Interpolar un valor ya
  casteado habría metido un booleano dentro de una cadena, y PHP lo habría convertido en
  `"1"`.

- **`phobos_version()`** devolvía `3.0.2`, una constante olvidada varias versiones atrás.

### Añadido

- Suite de pruebas de `EnvLoader` (`tests/Unit/Config/EnvLoaderTest.php`): no existía
  ninguna. Cubre el casteo, la precedencia del default, la expansión y las regresiones de
  `is_dev()`.

### Actualización desde 3.2.x

Revisa el código que consuma `env()` directamente. Los dos patrones que cambian:

```php
// 1. Comparaciones contra el string. Antes funcionaban; ahora env() devuelve un booleano.
if (env('APP_DEBUG') === 'true') { … }   // ✗ deja de entrar
if (env('APP_DEBUG')) { … }              // ✓

// 2. Valores vacíos que antes caían al default.
env('DB_PASSWORD', 'secreto');           // con DB_PASSWORD= vacío:
                                         //   antes → 'secreto'   ahora → ''
```

Si en tu `config/` ya casteabas defensivamente —`filter_var(env('X'), FILTER_VALIDATE_BOOL)`,
`(int) env('PORT')`— **no tienes que hacer nada**: esas expresiones funcionan igual con el
string y con el tipo nativo.

Los paquetes `phobos-framework-database*` no se ven afectados: no usan `env()`. Su
restricción `^3.1` acepta esta versión sin cambios.