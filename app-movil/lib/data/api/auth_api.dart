import 'package:chamba_app/data/api/api_client.dart';
import 'package:chamba_app/data/models/user_model.dart';
import 'package:dio/dio.dart';

/// Endpoints de autenticación (`/auth/*`).
class AuthApi {
  AuthApi(this._dio);

  final Dio _dio;

  Future<({String token, UserModel user})> login({
    required String email,
    required String password,
  }) async {
    try {
      final r = await _dio.post<Map<String, dynamic>>(
        'auth/login',
        data: {'email': email, 'password': password},
      );
      final data = r.data!;
      final token = data['token'] as String;
      final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);
      return (token: token, user: user);
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<({String token, UserModel user})> register({
    required String fullName,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String role,
    String? phone,
  }) async {
    try {
      final r = await _dio.post<Map<String, dynamic>>(
        'auth/register',
        data: {
          'full_name': fullName,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'role': role,
          if (phone != null && phone.isNotEmpty) 'phone': phone,
        },
      );
      final data = r.data!;
      final token = data['token'] as String;
      final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);
      return (token: token, user: user);
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<UserModel> me() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('auth/me');
      final data = r.data!;
      return UserModel.fromJson(data['user'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post<void>('auth/logout');
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
