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
    required String phone,
    String? razonSocial,
    String? ruc,
  }) async {
    final data = await _api.post(Endpoints.register, data: {
      'full_name': fullName,
      'email': email,
      'password': password,
      'password_confirmation': password,
      'role': role,
      'phone': phone,
      if (razonSocial != null && razonSocial.isNotEmpty) 'razon_social': razonSocial,
      if (ruc != null && ruc.isNotEmpty) 'ruc': ruc,
    });
    await _storage.write(data['token'] as String);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<User> me() async {
    final data = await _api.get(Endpoints.me);
    return User.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<User> updateMe({
    required String fullName,
    required String phone,
    String? currentPassword,
    String? newPassword,
    String? passwordConfirmation,
  }) async {
    final body = <String, dynamic>{
      'full_name': fullName,
      'phone': phone,
      if (currentPassword != null && currentPassword.isNotEmpty)
        'current_password': currentPassword,
      if (newPassword != null && newPassword.isNotEmpty) ...{
        'password': newPassword,
        'password_confirmation': passwordConfirmation ?? newPassword,
      },
    };
    final data = await _api.put(Endpoints.updateMe, data: body);
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
