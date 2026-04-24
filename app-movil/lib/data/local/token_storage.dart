import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Persistencia del token Sanctum (no guardar en texto plano en SharedPreferences).
class TokenStorage {
  TokenStorage({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  static const _kToken = 'auth_token';

  final FlutterSecureStorage _storage;

  Future<void> writeToken(String token) => _storage.write(key: _kToken, value: token);

  Future<String?> readToken() => _storage.read(key: _kToken);

  Future<void> deleteToken() => _storage.delete(key: _kToken);
}
