# Configuración de Login con Google (OAuth 2.0)

> Hacer esto **después de comprar el dominio**. Sin dominio real no se pueden crear las credenciales de producción.

---

## Resumen del flujo

```
Usuario → "Continuar con Google" → Popup → Google pide permiso
→ Backend crea/encuentra cuenta → Token Sanctum → Popup se cierra → Sesión activa
```

---

## Paso 1 — Crear proyecto en Google Cloud Console

1. Ir a [console.cloud.google.com](https://console.cloud.google.com)
2. Clic en el selector de proyectos (arriba a la izquierda) → **Proyecto nuevo**
3. Nombre: `BuscaPe` → Crear

---

## Paso 2 — Habilitar la API de Google

1. Menú izquierdo → **APIs y servicios → Biblioteca**
2. Buscar **"Google People API"** → Habilitar
3. También habilitar **"Google Identity"** si aparece

---

## Paso 3 — Crear credenciales OAuth 2.0

1. Menú izquierdo → **APIs y servicios → Credenciales**
2. Clic **+ Crear credenciales → ID de cliente de OAuth 2.0**
3. Si pide configurar "Pantalla de consentimiento OAuth":
   - Tipo de usuario: **Externo**
   - Nombre de la app: `Busca PE`
   - Correo de soporte: tu gmail
   - Logo: subir el logo de la app (opcional)
   - Dominio autorizado: `tudominio.com`
   - Guardar y continuar (el resto puede quedar vacío)

4. Volver a crear credenciales → **ID de cliente de OAuth 2.0**
   - Tipo de aplicación: **Aplicación web**
   - Nombre: `BuscaPe Web`

5. Agregar **Orígenes de JavaScript autorizados**:
   ```
   https://tudominio.com
   ```

6. Agregar **URIs de redirección autorizados**:
   ```
   https://tudominio.com/auth/google/callback
   ```
   > Si el sitio vive en una subcarpeta (ej: `/v1/chamba`), usar:
   > `https://tudominio.com/v1/chamba/auth/google/callback`

7. Clic **Crear** → Copiar el **ID de cliente** y **Secreto de cliente**

---

## Paso 4 — Configurar el servidor

Editar `.env.production` y completar las 3 líneas:

```env
GOOGLE_CLIENT_ID=123456789-xxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxxx
GOOGLE_REDIRECT_URI=https://tudominio.com/auth/google/callback
```

> `GOOGLE_REDIRECT_URI` debe coincidir **exactamente** con lo que pusiste en Google Cloud Console (mismo protocolo, mismo dominio, misma ruta).

---

## Paso 5 — Ejecutar la migración

Después de subir el ZIP al servidor, correr:

```bash
php artisan migrate
```

Esto crea la columna `google_id` en la tabla `users`.

---

## Paso 6 — Limpiar caché de configuración

```bash
php artisan config:cache
php artisan route:cache
```

---

## Verificar que funciona

1. Abrir la web → Login → clic **"Continuar con Google"**
2. Debe abrirse un popup de Google pidiendo permiso
3. Al aceptar, el popup se cierra y quedás logueado automáticamente

---

## Casos que maneja el sistema

| Situación | Resultado |
|-----------|-----------|
| Email nuevo (no registrado) | Crea cuenta automáticamente con rol `cliente` o `proveedor` según lo que eligió |
| Email ya registrado (sin Google) | Vincula el `google_id` a la cuenta existente y hace login |
| Email ya registrado (con Google) | Login directo sin crear cuenta nueva |
| Cuenta suspendida | Muestra error, no genera token |
| Usuario cierra el popup | No pasa nada, la página queda igual |
| Browser bloquea popups | Muestra mensaje "Permite las ventanas emergentes" |

---

## Para desarrollo local (opcional)

Si querés probar Google OAuth en local:

1. En Google Cloud Console, agregar también:
   - Origen autorizado: `http://localhost:8000`
   - URI de redirección: `http://localhost:8000/auth/google/callback`

2. En `.env` local:
   ```env
   GOOGLE_CLIENT_ID=<mismo client id>
   GOOGLE_CLIENT_SECRET=<mismo secret>
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
   ```

> Google permite múltiples URIs de redirección en la misma credencial. No hace falta crear una credencial separada para dev.

---

## Archivos modificados por esta feature

| Archivo | Qué hace |
|---------|----------|
| `app/Http/Controllers/SocialAuthController.php` | Redirect a Google y manejo del callback |
| `app/Models/User.php` | Campo `google_id` en `$fillable` |
| `database/migrations/2026_06_08_000001_google_auth_and_fix_settings.php` | Columna `google_id` en tabla `users` |
| `config/services.php` | Credenciales Google para Socialite |
| `routes/web.php` | Rutas `/auth/google/redirect` y `/auth/google/callback` |
| `resources/js/components/auth/GoogleSignInButton.vue` | Botón que abre el popup |
| `resources/js/stores/auth.js` | Acción `loginWithGoogle()` |
| `resources/js/views/LoginView.vue` | Botón Google en pantalla de login |
| `resources/js/views/RegisterView.vue` | Botón Google en pantalla de registro |
