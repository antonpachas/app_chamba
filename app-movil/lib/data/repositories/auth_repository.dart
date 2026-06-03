import '../models/user.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';
import '../../core/storage/token_storage.dart';

class AuthRepository {
  AuthRepository(this._api, this._storage);

  final ApiClient _api;
  final TokenStorage _storage;

  Future<User> login(String email, String password) async {
    final data = await _api.post(Endpoints.login, data: {
      'email': email,
      'password': password,
    });
    await _storage.write(data['token'] as String);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<User> register({
    required String fullName,
    required String email,
    required String password,
    required String role,
    String? phone,
  }) async {
    final data = await _api.post(Endpoints.register, data: {
      'full_name': fullName,
      'email': email,
      'password': password,
      'password_confirmation': password,
      'role': role,
      if (phone != null && phone.isNotEmpty) 'phone': phone,
    });
    await _storage.write(data['token'] as String);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<User> me() async {
    final data = await _api.get(Endpoints.me);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    try {
      await _api.post(Endpoints.logout);
    } finally {
      await _storage.delete();
    }
  }

  Future<void> forgotPassword(String email) async {
    await _api.post(Endpoints.forgotPassword, data: {'email': email});
  }

  Future<void> resetPassword({
    required String email,
    required String token,
    required String password,
  }) async {
    await _api.post(Endpoints.resetPassword, data: {
      'email': email,
      'token': token,
      'password': password,
      'password_confirmation': password,
    });
  }
}
