/// Configuración de entorno. Para cambiar la URL de la API:
///
/// Desarrollo:
///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
///
/// Producción (ya configurado por defecto):
///   flutter build apk
///
/// El archivo .env.example en la raíz del proyecto documenta las variables disponibles.
class AppEnv {
  AppEnv._();

  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://jaapsystem.com/v1/chamba/api/v1',
  );

  static const String appName = 'Busca PE';
}
