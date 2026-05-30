import 'package:chamba_app/data/api/api_client.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:chamba_app/data/models/service_request_model.dart';
import 'package:dio/dio.dart';

/// Endpoints de cliente autenticado (`/client/*`).
class ClientApi {
  ClientApi(this._dio);

  final Dio _dio;

  // ── Solicitudes ────────────────────────────────────────────────────────────

  Future<int> createServiceRequest({
    required int providerServiceId,
    required String contactChannel,
    String? message,
  }) async {
    try {
      final r = await _dio.post<Map<String, dynamic>>(
        'client/service-requests',
        data: {
          'provider_service_id': providerServiceId,
          'contact_channel': contactChannel,
          if (message != null && message.isNotEmpty) 'message': message,
        },
      );
      return (r.data!['service_request_id'] as num).toInt();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<List<ClientRequest>> serviceRequests() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('client/service-requests');
      final list = r.data!['data'] as List<dynamic>? ?? [];
      return list.whereType<Map>().map((e) => ClientRequest.fromJson(e.cast<String, dynamic>())).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> closeServiceRequest(int serviceRequestId) async {
    try {
      await _dio.post<void>('client/service-requests/$serviceRequestId/close');
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  // ── Favoritos ─────────────────────────────────────────────────────────────

  Future<List<ListingCard>> favorites() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('client/favorites');
      final list = r.data!['data'] as List<dynamic>? ?? [];
      return list.whereType<Map>().map((e) => ListingCard.fromJson(e.cast<String, dynamic>())).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<String> toggleFavorite({required int providerProfileId}) async {
    try {
      final r = await _dio.post<Map<String, dynamic>>(
        'client/favorites/toggle',
        data: {'provider_profile_id': providerProfileId},
      );
      return r.data!['action'] as String;
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
