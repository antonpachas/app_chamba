import 'package:chamba_app/data/api/api_client.dart';
import 'package:dio/dio.dart';

/// Búsqueda pública de servicios.
class SearchApi {
  SearchApi(this._dio);

  final Dio _dio;

  Future<List<Map<String, dynamic>>> searchServices({
    int? categoryId,
    int? districtId,
    String? keyword,
    double? userLat,
    double? userLng,
    double? radiusKm,
  }) async {
    try {
      final q = <String, dynamic>{};
      if (categoryId != null) q['category_id'] = categoryId;
      if (districtId != null) q['district_id'] = districtId;
      if (keyword != null && keyword.isNotEmpty) q['keyword'] = keyword;
      if (userLat != null) q['user_lat'] = userLat;
      if (userLng != null) q['user_lng'] = userLng;
      if (radiusKm != null) q['radius_km'] = radiusKm;

      final r = await _dio.get<Map<String, dynamic>>(
        'services/search',
        queryParameters: q,
      );
      return (r.data!['data'] as List<dynamic>).cast<Map<String, dynamic>>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
