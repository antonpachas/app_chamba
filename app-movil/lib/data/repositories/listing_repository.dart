import '../models/listing.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';

class ListingRepository {
  ListingRepository(this._api);
  final ApiClient _api;

  Future<Listing> detail(String id) async {
    final data = await _api.get(Endpoints.listingDetail(id));
    return Listing.fromJson(data['data'] as Map<String, dynamic>);
  }
}
