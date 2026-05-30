import 'package:chamba_app/data/api/api_client.dart';
import 'package:chamba_app/data/models/service_request_model.dart';
import 'package:dio/dio.dart';

/// Endpoints de proveedor autenticado (`/provider/*`).
class ProviderApi {
  ProviderApi(this._dio);

  final Dio _dio;

  // ── Dashboard ─────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> dashboard() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('provider/dashboard');
      return (r.data!['data'] as Map).cast<String, dynamic>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  // ── Perfil ────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> profile() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('provider/profile');
      return (r.data!['data'] as Map).cast<String, dynamic>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> saveProfile(Map<String, dynamic> data) async {
    try {
      final r = await _dio.post<Map<String, dynamic>>('provider/profile', data: data);
      return (r.data!['data'] as Map).cast<String, dynamic>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> createProfile({
    required int districtId,
    String? businessName,
    String? description,
    String? whatsapp,
    String? contactPhone,
    String? addressText,
  }) async {
    return saveProfile({
      'district_id': districtId,
      'business_name': ?businessName,
      'description': ?description,
      'whatsapp': ?whatsapp,
      'contact_phone': ?contactPhone,
      'address_text': ?addressText,
    });
  }

  // ── Anuncios ──────────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> listings() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('provider/services');
      final list = r.data!['data'] as List<dynamic>? ?? [];
      return list.whereType<Map>().map((e) => e.cast<String, dynamic>()).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> storeListing(Map<String, dynamic> data) async {
    try {
      final r = await _dio.post<Map<String, dynamic>>('provider/services', data: data);
      return (r.data!['data'] as Map?)?.cast<String, dynamic>() ?? {};
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> updateListing(int id, Map<String, dynamic> data) async {
    try {
      final r = await _dio.put<Map<String, dynamic>>('provider/services/$id', data: data);
      return (r.data!['data'] as Map?)?.cast<String, dynamic>() ?? {};
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> toggleListingStatus(int id, String status) async {
    try {
      await _dio.patch<void>('provider/services/$id', data: {'status': status});
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  // ── Solicitudes recibidas ─────────────────────────────────────────────────

  Future<List<ReceivedRequest>> serviceRequests() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('provider/service-requests');
      final list = r.data!['data'] as List<dynamic>? ?? [];
      return list.whereType<Map>().map((e) => ReceivedRequest.fromJson(e.cast<String, dynamic>())).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> updateRequestStatus(int id, String status) async {
    try {
      await _dio.post<void>('provider/service-requests/$id/status', data: {'status': status});
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
