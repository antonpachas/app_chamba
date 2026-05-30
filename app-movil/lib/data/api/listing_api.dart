import 'package:chamba_app/data/api/api_client.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:dio/dio.dart';

/// Endpoints públicos de anuncios/servicios.
class ListingApi {
  ListingApi(this._dio);

  final Dio _dio;

  Future<ListingDetail> show(dynamic id) async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('listings/$id');
      final data = r.data!['data'] as Map<String, dynamic>? ?? r.data!;
      return ListingDetail.fromJson(data);
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<List<ListingCard>> homeFeatured() async {
    try {
      final r = await _dio.get<dynamic>('listings/home-featured');
      final body = r.data;
      List<dynamic> list;
      if (body is Map && body['data'] is List) {
        list = body['data'] as List<dynamic>;
      } else if (body is List) {
        list = body;
      } else {
        list = [];
      }
      return list.whereType<Map>().map((e) => ListingCard.fromJson(e.cast<String, dynamic>())).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<void> storeReview({
    required int providerServiceId,
    required int rating,
    String? comment,
    int? serviceRequestId,
  }) async {
    try {
      await _dio.post<void>(
        'client/reviews',
        data: {
          'provider_service_id': providerServiceId,
          'rating': rating,
          if (comment != null && comment.isNotEmpty) 'comment': comment,
          'service_request_id': ?serviceRequestId,
        },
      );
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
