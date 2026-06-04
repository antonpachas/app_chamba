import 'package:dio/dio.dart';
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

  Future<Map<String, dynamic>> saveProfile(Map<String, dynamic> data,
      {required bool create}) async {
    final resp = create
        ? await _api.post(Endpoints.providerProfile, data: data)
        : await _api.put(Endpoints.providerProfile, data: data);
    return (resp['data'] ?? resp) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> createListing(Map<String, dynamic> data) async {
    final resp = await _api.post(Endpoints.providerServices, data: data);
    return (resp['data'] ?? resp) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> updateListing(
      int id, Map<String, dynamic> data) async {
    final resp =
        await _api.put(Endpoints.providerServiceDetail(id), data: data);
    return (resp['data'] ?? resp) as Map<String, dynamic>;
  }

  Future<void> updateRequestStatus(int id, String status) async {
    await _api.patch(Endpoints.providerRequestStatus(id),
        data: {'status': status});
  }

  Future<void> uploadRequestEvidence(
      int requestId, List<String> filePaths) async {
    final form = FormData.fromMap({
      'images': await Future.wait(
        filePaths.map((p) => MultipartFile.fromFile(p)),
      ),
    });
    await _api.postForm(Endpoints.providerRequestEvidence(requestId), data: form);
  }

  Future<Map<String, dynamic>> uploadListingImages(
      int serviceId, List<String> filePaths) async {
    final form = FormData.fromMap({
      'images': await Future.wait(
        filePaths.map((p) => MultipartFile.fromFile(p)),
      ),
    });
    final resp = await _api.postForm(
        Endpoints.providerServiceImages(serviceId), data: form);
    return (resp['data'] ?? resp) as Map<String, dynamic>;
  }

  Future<void> deleteListingImage(int serviceId, String imageUrl) async {
    await _api.delete(
      Endpoints.providerServiceImages(serviceId),
    );
  }

  Future<List<Map<String, dynamic>>> categories() async {
    final data = await _api.get(Endpoints.categories);
    return ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
  }
}
