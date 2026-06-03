import 'package:dio/dio.dart';
import '../config/app_env.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient(this._tokenStorage) {
    _dio = Dio(BaseOptions(
      baseUrl: AppEnv.apiBaseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: _onRequest,
      onError: _onError,
    ));
  }

  final TokenStorage _tokenStorage;
  late final Dio _dio;

  Future<void> _onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _tokenStorage.read();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  void _onError(DioException e, ErrorInterceptorHandler handler) {
    handler.next(e);
  }

  Future<dynamic> get(String path, {Map<String, dynamic>? params}) async {
    return _request(() => _dio.get(path, queryParameters: params));
  }

  Future<dynamic> post(String path, {dynamic data}) async {
    return _request(() => _dio.post(path, data: data));
  }

  Future<dynamic> put(String path, {dynamic data}) async {
    return _request(() => _dio.put(path, data: data));
  }

  Future<dynamic> patch(String path, {dynamic data}) async {
    return _request(() => _dio.patch(path, data: data));
  }

  Future<dynamic> delete(String path) async {
    return _request(() => _dio.delete(path));
  }

  Future<dynamic> _request(Future<Response> Function() call) async {
    try {
      final response = await call();
      return response.data;
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final body = e.response?.data;
      String message = 'Error de conexión. Verifica tu internet.';

      if (body is Map) {
        message = (body['message'] as String?)
            ?? _joinErrors(body['errors'])
            ?? message;
      }

      throw ApiException(message, statusCode: status);
    }
  }

  String? _joinErrors(dynamic errors) {
    if (errors == null) return null;
    if (errors is Map) {
      return errors.values
          .expand((v) => v is List ? v : [v])
          .join(' ');
    }
    return null;
  }
}
