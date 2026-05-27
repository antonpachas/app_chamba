# Guía de tareas programadas (Cron) — Busca PE en cPanel

Esta guía explica **para qué sirven** las tareas automáticas de Busca PE y **cómo configurarlas** en cPanel cuando no tienes SSH.

**Hosting de referencia:** `https://jaapsystem.com/v1/chamba/`  
**Ruta en servidor:** `/home/jaapsyst/public_html/v1/chamba/`

---

## Índice

1. [¿Qué es un “cron” y por qué lo necesitas?](#1-qué-es-un-cron-y-por-qué-lo-necesitas)
2. [Las dos tareas de Busca PE](#2-las-dos-tareas-de-busca-pe)
3. [Qué pasa si NO configuras el cron](#3-qué-pasa-si-no-configuras-el-cron)
4. [Antes de empezar](#4-antes-de-empezar)
5. [Método recomendado: cPanel + PHP (artisan)](#5-método-recomendado-cpanel--php-artisan)
6. [Método alternativo: cPanel + URL (_cron.php)](#6-método-alternativo-cpanel--url-_cronphp)
7. [Cómo saber si funciona](#7-cómo-saber-si-funciona)
8. [Problemas frecuentes](#8-problemas-frecuentes)
9. [Seguridad](#9-seguridad)
10. [Preguntas frecuentes](#10-preguntas-frecuentes)

---

## 1. ¿Qué es un “cron” y por qué lo necesitas?

Un **cron** (o *Cron Job* en cPanel) es un **recordatorio del servidor** que ejecuta un comando a una hora fija, todos los días (o con la frecuencia que elijas).

Busca PE es una aplicación web: **solo hace cosas cuando alguien abre una página o llama a la API**. No tiene un “reloj interno” que corra solo en segundo plano.

Hay acciones de negocio que deben ocurrir **aunque nadie entre a la web**, por ejemplo:

- Ocultar anuncios que ya cumplieron sus 5 días publicados.
- Bajar a plan Free a un negocio cuya suscripción Premium venció ayer.

Para eso usamos **dos comandos** que Laravel ya tiene preparados. Tú solo tienes que decirle al servidor: *“ejecútalos cada noche”*.

> **Importante:** El archivo `routes/console.php` define horarios (`dailyAt('02:00')`), pero en hosting compartido **eso no se ejecuta solo**. Hace falta crear el Cron Job en cPanel (esta guía).

---

## 2. Las dos tareas de Busca PE

### Tarea A — Vencimiento de anuncios

| | |
|---|---|
| **Comando** | `busca:listings:expire` |
| **Cuándo conviene** | Todos los días, ~02:00 (hora del servidor) |
| **Qué hace** | Busca anuncios con `expires_at` en el pasado y los **oculta** (estado inactivo). **No los borra** de la base de datos. |
| **Duración por defecto** | 5 días (`listings.default_duration_days` en Admin). Un negocio puede tener override por perfil. |
| **Salida típica** | `Anuncios vencidos ocultados: 3` |

**Ejemplo:** Juan publica “Plomería 24h” el lunes. Si la duración es 5 días, el sábado a la madrugada el cron lo oculta. Juan puede **renovar** desde su panel si tiene cupo en su plan.

**Se puede desactivar** desde Admin → Configuración → Anuncios (`listings.expire_cron_enabled`). Si está apagado, el comando no hace nada (útil si quieres gestionar vencimientos solo a mano).

---

### Tarea B — Vencimiento de suscripciones

| | |
|---|---|
| **Comando** | `chamba:expire-subscriptions` |
| **Cuándo conviene** | Todos los días, ~03:00 (después de la tarea A) |
| **Qué hace** | Revisa trials y suscripciones Premium cuya fecha de fin ya pasó y hace **downgrade a plan Free**. |
| **Salida típica** | `Procesadas 1 suscripciones.` |

**Ejemplo:** María tenía Premium hasta el 25. El 26 a las 03:00 el cron la pasa a Free: menos cupo de anuncios, límites de contactos, vuelven los anuncios de AdSense en la app, etc.

---

### Lo que NO debes programar

| Comando | Motivo |
|---------|--------|
| `chamba:escrow:auto-release` | Busca PE **no usa custodia de pagos** entre usuarios. No lo necesitas. |

---

## 3. Qué pasa si NO configuras el cron

| Situación | Sin cron | Con cron |
|-----------|----------|----------|
| Anuncio cumplió 5 días | Puede seguir marcado “activo” en BD; la búsqueda a veces lo filtra, pero el proveedor ve datos inconsistentes y el **cupo** puede contarlo mal | Se oculta de forma uniforme cada noche |
| Premium venció | El usuario puede seguir viéndose Premium hasta que alguien toque la BD | Pasa a Free automáticamente |
| Renovar anuncio | Puede fallar o confundir si el sistema cree que sigue activo | Estado claro: vencido → renovar |

**Resumen:** la app “funciona” sin cron para pruebas, pero en producción **deberías configurarlo** desde el primer deploy estable.

---

## 4. Antes de empezar

Checklist:

- [ ] Ya desplegaste Busca PE en `/v1/chamba/` y la app abre en el navegador.
- [ ] Corriste migraciones (`_migrate.php`) al menos una vez.
- [ ] En `.env` tienes `CHAMBA_SETUP_TOKEN` con un valor secreto (lo usarás si eliges el método por URL).
- [ ] Sabes entrar a **cPanel → Cron Jobs** (en tu caso: sí puedes crear crons).

**Token:** está en el archivo `.env` del servidor, línea `CHAMBA_SETUP_TOKEN=...`. No lo compartas en chats públicos.

---

## 5. Método recomendado: cPanel + PHP (artisan)

Usa este método si en la pantalla de Cron Jobs de cPanel puedes pegar un comando que empiece con `php` o `/usr/local/bin/php`.

### Paso 1 — Abrir Cron Jobs

1. Entra a **cPanel**.
2. Busca **Cron Jobs** o **Tareas cron**.
3. En “Añadir nuevo trabajo cron” verás campos de tiempo y un cuadro **Comando**.

### Paso 2 — Primera tarea (anuncios)

Configura la **frecuencia** (ejemplo: todos los días a las 2:00 AM):

| Campo en cPanel | Valor |
|-----------------|-------|
| Minuto | `0` |
| Hora | `2` |
| Día del mes | `*` |
| Mes | `*` |
| Día de la semana | `*` |

En **Comando**, pega (una sola línea):

```bash
/usr/local/bin/php /home/jaapsyst/public_html/v1/chamba/artisan busca:listings:expire >> /home/jaapsyst/public_html/v1/chamba/storage/logs/cron.log 2>&1
```

Pulsa **Añadir** / **Create**.

> **Ruta de PHP:** en muchos hostings es `/usr/local/bin/php`. En la misma página de Cron a veces cPanel muestra un ejemplo con la ruta correcta (ej. `php` o `/opt/cpanel/...`). Si el cron falla, copia esa ruta y sustituye la primera parte del comando.

### Paso 3 — Segunda tarea (suscripciones)

Crea **otro** cron job:

| Campo | Valor |
|-------|-------|
| Minuto | `0` |
| Hora | `3` |
| Resto | `*` |

Comando:

```bash
/usr/local/bin/php /home/jaapsyst/public_html/v1/chamba/artisan chamba:expire-subscriptions >> /home/jaapsyst/public_html/v1/chamba/storage/logs/cron.log 2>&1
```

### Paso 4 — Esperar o forzar una prueba

- Los crons de cPanel suelen evaluarse en la **hora del servidor** (a veces UTC, no hora de Perú). Revisa en cPanel si indica la zona horaria.
- Para no esperar hasta las 2 AM, puedes ejecutar **una vez** el método de la [sección 6](#6-método-alternativo-cpanel--url-_cronphp) en el navegador y comprobar el texto de salida.

---

## 6. Método alternativo: cPanel + URL (_cron.php)

Úsalo si:

- El cron con `php artisan` falla o no encuentras la ruta de PHP, **o**
- Prefieres **un solo** cron en lugar de dos.

El deploy incluye el script `public/_cron.php`. Hace lo mismo que los dos comandos, pero lo invocas por **HTTPS** (protegido con token).

### Paso 1 — Prueba en el navegador

Abre (cambia `TU_TOKEN`):

```
https://jaapsystem.com/v1/chamba/public/_cron.php?token=TU_TOKEN&task=all
```

Respuesta esperada (texto plano, no JSON):

```
=== BUSCA PE · CRON REMOTO ===
...
--- busca:listings:expire ---
Anuncios vencidos ocultados: 0
...
--- chamba:expire-subscriptions ---
Procesadas 0 suscripciones.
...
Resumen: {"listings":"ok","subscriptions":"ok"}
```

Parámetro `task`:

| Valor | Ejecuta |
|-------|---------|
| `all` | Ambas tareas (recomendado) |
| `listings` | Solo anuncios |
| `subscriptions` | Solo suscripciones |

### Paso 2 — Un solo Cron Job en cPanel

Frecuencia ejemplo: **02:15** todos los días (`15` minutos, `2` hora).

Comando con **curl**:

```bash
curl -fsS "https://jaapsystem.com/v1/chamba/public/_cron.php?token=TU_TOKEN&task=all" >> /home/jaapsyst/public_html/v1/chamba/storage/logs/cron-http.log 2>&1
```

Si cPanel dice que `curl` no existe, prueba con **wget**:

```bash
wget -q -O - "https://jaapsystem.com/v1/chamba/public/_cron.php?token=TU_TOKEN&task=all" >> /home/jaapsyst/public_html/v1/chamba/storage/logs/cron-http.log 2>&1
```

Sustituye `TU_TOKEN` por el valor real de `.env` (sin espacios).

### ¿Cuál método elegir?

| | PHP + artisan | URL + _cron.php |
|--|---------------|-----------------|
| Seguridad | Mejor (no expone URL) | Requiere token fuerte en la URL |
| Simplicidad | Dos crons | **Un solo** cron |
| Depende de | Ruta correcta de `php` | Que `curl`/`wget` existan en el servidor |
| Archivo en `public/` | No hace falta `_cron.php` | Debes **dejar** `_cron.php` en el servidor |

Si ya confirmaste que cPanel acepta comandos PHP, **prioriza el método artisan (sección 5)**.

---

## 7. Cómo saber si funciona

### A) Revisar el log en File Manager

| Método | Archivo |
|--------|---------|
| artisan | `/v1/chamba/storage/logs/cron.log` |
| URL | `/v1/chamba/storage/logs/cron-http.log` |

Después de la hora programada, el archivo debería tener líneas nuevas con la fecha del día.

### B) Email de cPanel

Muchos hostings envían un correo al dueño de la cuenta **si el cron falla**. Si recibes “Cron <command> failed”, copia el mensaje y revisa la [sección 8](#8-problemas-frecuentes).

### C) Prueba con datos reales

1. Crea un anuncio de prueba con vencimiento en el pasado (o espera los 5 días).
2. Ejecuta manualmente la URL de `_cron.php` o espera al cron nocturno.
3. En el panel del proveedor el anuncio debería aparecer como **vencido / inactivo** y ofrecer **renovar**.

### D) Email de resumen en cPanel

En Cron Jobs a veces puedes poner un email para recibir la salida del comando. Útil la primera semana; luego puedes desactivarlo si molesta.

---

## 8. Problemas frecuentes

| Síntoma | Causa probable | Qué hacer |
|---------|----------------|-----------|
| No pasa nada a la hora programada | Zona horaria del servidor distinta a la tuya | Ajusta hora +2 o +3 h, o prueba manual con `_cron.php` |
| Email: `php: command not found` | Ruta de PHP incorrecta | Usa la ruta que muestra cPanel en el ejemplo de cron |
| Email: `Could not open input file: artisan` | Ruta del proyecto mal | Verifica que sea `/home/jaapsyst/public_html/v1/chamba/artisan` |
| `Token inválido` en URL | Token distinto al del `.env` | Copia de nuevo desde File Manager → `.env` |
| `403` o página en blanco en URL | Token vacío o script borrado | Redespliega `_cron.php` o usa método artisan |
| `Anuncios vencidos ocultados: 0` siempre | No hay anuncios vencidos **o** cron desactivado en Admin | Normal si no hay vencidos; revisa `listings.expire_cron_enabled` |
| Log no se crea | `storage/logs` sin permisos de escritura | Ejecuta una vez `_reset.php` o chmod 775 en `storage/` |

---

## 9. Seguridad

1. **`CHAMBA_SETUP_TOKEN`:** usa una cadena larga y aleatoria (32+ caracteres). Quien tenga la URL con token puede ejecutar el cron manualmente.
2. **Scripts `_*.php`:** después del deploy inicial puedes borrar `_migrate.php` y `_reset.php`. Si usas cron por **URL**, **mantén** `_cron.php`. Si usas solo **artisan**, puedes borrar `_cron.php`.
3. **No publiques** la URL con token en foros, capturas ni repos públicos.
4. Opcional: cuando todo funcione con artisan, vacía `CHAMBA_SETUP_TOKEN` en `.env` **solo si** ya borraste todos los scripts que lo usan.

---

## 10. Preguntas frecuentes

**¿Tengo que usar SSH?**  
No. cPanel Cron Jobs + esta guía son suficientes.

**¿Puedo usar un solo cron para las dos tareas?**  
Sí: método URL con `task=all`, o un comando que encadene ambos artisan en una línea (menos legible). Lo más claro en artisan son **dos** crons separados.

**¿Cada cuánto debe correr?**  
Una vez al día basta. No hace falta cada hora.

**¿El cron borra datos?**  
No. Oculta anuncios y cambia el plan de suscripción; los registros siguen en la base de datos.

**¿Afecta al rendimiento de la web?**  
Son procesos cortos (segundos). Programarlos de madrugada evita coincidir con picos de tráfico.

**¿Qué borro después del deploy?**  
Ver `webapi/DEPLOY.md` sección de limpieza. `_cron.php` solo si ya no lo usas.

---

## Referencia rápida (copiar y pegar)

**Cron 1 — 02:00 — Anuncios**

```bash
/usr/local/bin/php /home/jaapsyst/public_html/v1/chamba/artisan busca:listings:expire >> /home/jaapsyst/public_html/v1/chamba/storage/logs/cron.log 2>&1
```

**Cron 2 — 03:00 — Suscripciones**

```bash
/usr/local/bin/php /home/jaapsyst/public_html/v1/chamba/artisan chamba:expire-subscriptions >> /home/jaapsyst/public_html/v1/chamba/storage/logs/cron.log 2>&1
```

**Prueba manual (navegador)**

```
https://jaapsystem.com/v1/chamba/public/_cron.php?token=TU_TOKEN&task=all
```

---

**Documentos relacionados**

- Deploy general: [`webapi/DEPLOY.md`](../webapi/DEPLOY.md)
- Pruebas funcionales: [`GUIA_PRUEBAS.md`](GUIA_PRUEBAS.md)
- Reglas de hosting: [`.cursor/rules/deploy-jaapsystem.mdc`](../.cursor/rules/deploy-jaapsystem.mdc)

*Última actualización: 2026-05-27 · Busca PE*
