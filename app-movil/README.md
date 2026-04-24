# Chamba (Flutter)

Cliente móvil para el marketplace de servicios locales. Consume la API Laravel en `../webapi/`.

## Estructura (`lib/`)

| Carpeta | Contenido |
|---------|-----------|
| `core/config` | URL base de la API (`AppConfig`, `dart-define`) |
| `core/theme` | Tema Material |
| `core/router` | `go_router` y redirecciones por sesión |
| `data/api` | **Solo llamadas HTTP** por dominio (`AuthApi`, `CatalogApi`, …) |
| `data/local` | Token en `flutter_secure_storage` |
| `data/models` | Modelos de datos (usuario, categoría, …) |
| `presentation/view_models` | Estado global (`SessionViewModel`) |
| `presentation/features/*` | Pantallas + ViewModels por flujo (MVVM con `ChangeNotifier` + `provider`) |

## Ejecutar

1. Levanta la API (`php artisan serve` en `webapi`).
2. Ajusta la URL base si hace falta (emulador Android usa `10.0.2.2` por defecto):

```bash
cd app-movil
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1/
```

En **iOS simulador** suele funcionar mejor:

```bash
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1/
```

3. En Android, `android:usesCleartextTraffic` está en `true` para desarrollo con HTTP local.

## Modo invitado (App Store)

En **login** y **registro** hay **Continuar / Explorar como invitado**: permite usar la pestaña **Buscar** (API pública) sin cuenta. El estado se guarda en `SharedPreferences` para no obligar a elegirlo en cada apertura. En **Cuenta** el invitado puede ir a login/registro o **salir del modo invitado**.

## Estado actual del código

- Flujo: **splash → login / registro / invitado → home** con rol **cliente** o **proveedor** (invitado solo ve flujo de exploración tipo cliente).
- Cliente: pestaña **Buscar** (categorías + búsqueda contra `services/search`).
- Proveedor: pestaña **Inicio** (resumen `provider/dashboard`).
- **Cuenta** y cierre de sesión en ambos roles.
- Pestañas “Actividad” / “Mis servicios” son placeholders para siguientes iteraciones.
