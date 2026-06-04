import '../models/listing.dart';
import '../models/category.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';

class SearchRepository {
  SearchRepository(this._api);
  final ApiClient _api;

  Future<({List<Listing> listings, int total, bool isGuest})> search({
    String? keyword,
    int? categoryId,
    int? districtId,
    double? userLat,
    double? userLng,
    double? radiusKm,
    String? sort,
    double? minRating,
    int page = 1,
    int perPage = 24,
  }) async {
    final params = <String, dynamic>{
      'page': page,
      'per_page': perPage,
      if (keyword != null && keyword.isNotEmpty) 'keyword': keyword,
      'category_id': ?categoryId,
      'district_id': ?districtId,
      'user_lat': ?userLat,
      'user_lng': ?userLng,
      'radius_km': ?radiusKm,
      'sort': ?sort,
      'min_rating': ?minRating,
    };

    final data = await _api.get(Endpoints.searchListings, params: params);
    final rows = (data['data'] as List?) ?? [];
    final meta = data['meta'] as Map<String, dynamic>? ?? {};
    final pagination = meta['pagination'] as Map<String, dynamic>? ?? {};

    return (
      listings: rows
          .map((e) => Listing.fromJson(e as Map<String, dynamic>))
          .toList(),
      total: pagination['total'] is num
          ? (pagination['total'] as num).toInt()
          : int.tryParse(pagination['total']?.toString() ?? '') ?? rows.length,
      isGuest: meta['guest_preview'] == true,
    );
  }

  Future<List<Listing>> homeFeatured() async {
    final data = await _api.get(Endpoints.homeFeatured);
    final rows = (data['data'] as List?) ?? [];
    return rows
        .map((e) => Listing.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<Category>> categories() async {
    final data = await _api.get(Endpoints.categories);
    final rows = (data['data'] as List?) ?? [];
    return rows
        .map((e) => Category.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
