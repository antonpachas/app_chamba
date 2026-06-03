import '../models/service_request.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';

class ClientRepository {
  ClientRepository(this._api);
  final ApiClient _api;

  Future<List<ServiceRequest>> myRequests() async {
    final data = await _api.get(Endpoints.clientRequests);
    final rows = (data['data'] as List?) ?? [];
    return rows
        .map((e) => ServiceRequest.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<void> contact({
    required int serviceId,
    required String channel,
    String? message,
  }) async {
    await _api.post(
      Endpoints.clientRequests,
      data: {
        'provider_service_id': serviceId,
        'contact_channel': channel,
        if (message != null && message.isNotEmpty) 'message': message,
      },
    );
  }

  Future<List<Map<String, dynamic>>> myFavorites() async {
    final data = await _api.get(Endpoints.favorites);
    return ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
  }

  Future<bool> toggleFavorite({int? profileId, int? serviceId}) async {
    final data = await _api.post(
      Endpoints.favoritesToggle,
      data: {
        'provider_profile_id': ?profileId,
        'provider_service_id': ?serviceId,
      },
    );
    return data['is_favorite'] == true;
  }

  Future<void> submitReview({
    required int serviceId,
    required int rating,
    String? comment,
  }) async {
    await _api.post(
      Endpoints.reviews,
      data: {
        'provider_service_id': serviceId,
        'rating': rating,
        if (comment != null && comment.isNotEmpty) 'comment': comment,
      },
    );
  }
}
