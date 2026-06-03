import '../models/service_request.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';

class ProviderRepository {
  ProviderRepository(this._api);
  final ApiClient _api;

  Future<Map<String, dynamic>> dashboard() async {
    final data = await _api.get(Endpoints.providerDashboard);
    return data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> profile() async {
    final data = await _api.get(Endpoints.providerProfile);
    return (data['data'] ?? data) as Map<String, dynamic>;
  }

  Future<List<Map<String, dynamic>>> myListings() async {
    final data = await _api.get(Endpoints.providerServices);
    return ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
  }

  Future<List<ServiceRequest>> myRequests() async {
    final data = await _api.get(Endpoints.providerRequests);
    final rows = (data['data'] as List?) ?? [];
    return rows.map((e) => ServiceRequest.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> setListingActive(int id, {required bool active}) async {
    await _api.patch(Endpoints.providerServiceStatus(id), data: {'is_active': active});
  }
}
