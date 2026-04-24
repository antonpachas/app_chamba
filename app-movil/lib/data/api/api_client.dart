import 'package:chamba_app/core/config/app_config.dart';
import 'package:chamba_app/data/api/api_exception.dart';
import 'package:chamba_app/data/local/token_storage.dart';
import 'package:dio/dio.dart';

/// Cliente HTTP compartido (Dio) con base URL y token desde [TokenStorage].
class ApiClient {
  ApiClient(TokenStorage tokenStorage)
      : dio = Dio(
          BaseOptions(
            baseUrl: AppConfig.apiBaseUrl,
            connectTimeout: const Duration(seconds: 20),
            receiveTimeout: const Duration(seconds: 30),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          tokenStorage.readToken().then((token) {
            if (token != null && token.isNotEmpty) {
              options.headers['Authorization'] = 'Bearer $token';
            }
            handler.next(options);
          });
        },
      ),
    );
  }

  final Dio dio;

  static ApiException mapDioException(DioException e) {
    return ApiException(_messageFromDio(e), statusCode: e.response?.statusCode);
  }

  static String _messageFromDio(DioException e) {
    final data = e.response?.data;
    if (data is Map) {
      final m = data['message'];
      if (m is String && m.isNotEmpty) return m;
      final errs = data['errors'];
      if (errs is Map && errs.isNotEmpty) {
        final first = errs.values.first;
        if (first is List && first.isNotEmpty && first.first is String) {
          return first.first as String;
        }
      }
    }
    return e.message ?? 'Error de red';
  }
}
