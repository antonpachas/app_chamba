/// URL base de la API Laravel (`/api/v1`).
///
/// En Android emulador, `10.0.2.2` apunta al `localhost` de tu PC.
/// En iOS simulador suele usarse `127.0.0.1`.
///
/// Ejemplo al ejecutar:
/// `flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1`
class AppConfig {
  AppConfig._();

  /// Debe terminar en `/` para que rutas relativas (`auth/login`) resuelvan bien en Dio.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://jaapsystem.com/v1/app_chamba/public/api/v1/',
  );
}
