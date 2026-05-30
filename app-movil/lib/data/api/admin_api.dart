import 'package:chamba_app/data/api/api_client.dart';
import 'package:dio/dio.dart';

/// Endpoints del panel de administración (`/admin/*`).
class AdminApi {
  AdminApi(this._dio);

  final Dio _dio;

  Future<Map<String, dynamic>> dashboard() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('admin/dashboard');
      return (r.data!['data'] as Map?)?.cast<String, dynamic>() ?? {};
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> users({String? q, String role = 'all', String status = 'all', int page = 1}) async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('admin/users', queryParameters: {
        if (q != null && q.isNotEmpty) 'q': q,
        'role': role,
        'status': status,
        'page': page,
        'per_page': 25,
      });
      return r.data!;
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> suspendUser(int userId, String reason) async {
    try {
      await _dio.post<void>('admin/users/$userId/suspend', data: {'reason': reason, 'hide_listings': true});
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> activateUser(int userId) async {
    try {
      await _dio.post<void>('admin/users/$userId/activate');
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> listings({String filter = 'all', String? q, int page = 1}) async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('admin/listings', queryParameters: {
        'filter': filter,
        if (q != null && q.isNotEmpty) 'q': q,
        'page': page,
        'per_page': 20,
      });
      return r.data!;
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> hideListing(int id, {String? reason}) async {
    try {
      await _dio.post<void>('admin/listings/$id/hide', data: reason != null ? {'reason': reason} : {});
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> restoreListing(int id) async {
    try {
      await _dio.post<void>('admin/listings/$id/restore');
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> reports({String? from, String? to}) async {
    try {
      final now = DateTime.now();
      final f = from ?? DateTime(now.year, now.month - 1, now.day).toIso8601String().substring(0, 10);
      final t = to ?? now.toIso8601String().substring(0, 10);
      final results = await Future.wait([
        _dio.get<Map<String, dynamic>>('admin/reports/top-categories', queryParameters: {'from': f, 'to': t}),
        _dio.get<Map<String, dynamic>>('admin/reports/top-queries', queryParameters: {'from': f, 'to': t}),
      ]);
      return {
        'top_categories': results[0].data!['data'] ?? [],
        'top_queries': results[1].data!['data'] ?? [],
        'from': f,
        'to': t,
      };
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<List<Map<String, dynamic>>> supportTickets({String status = 'all'}) async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('admin/support-tickets', queryParameters: {
        'status': status,
        'per_page': 25,
      });
      final list = r.data!['data'] as List<dynamic>? ?? [];
      return list.whereType<Map>().map((e) => e.cast<String, dynamic>()).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
