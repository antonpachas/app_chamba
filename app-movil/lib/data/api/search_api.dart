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

      final r = await _dio.get<dynamic>(
        'listings/search',
        queryParameters: q,
      );
      final body = r.data;
      List<dynamic> list;
      if (body is Map && body['data'] is List) {
        list = body['data'] as List<dynamic>;
      } else if (body is List) {
        list = body;
      } else {
        list = [];
      }
      return list.whereType<Map>().map((e) => e.cast<String, dynamic>()).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
