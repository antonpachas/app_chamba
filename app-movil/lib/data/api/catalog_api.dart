import 'package:chamba_app/data/api/api_client.dart';
import 'package:chamba_app/data/models/category_model.dart';
import 'package:dio/dio.dart';

/// Categorías y catálogo geográfico.
class CatalogApi {
  CatalogApi(this._dio);

  final Dio _dio;

  Future<List<CategoryModel>> categories() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('categories');
      final list = r.data!['data'] as List<dynamic>;
      return list.map((e) => CategoryModel.fromJson(e as Map<String, dynamic>)).toList();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<List<Map<String, dynamic>>> departments() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('geo/departments');
      return (r.data!['data'] as List<dynamic>).cast<Map<String, dynamic>>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<List<Map<String, dynamic>>> provinces({required int departmentId}) async {
    try {
      final r = await _dio.get<Map<String, dynamic>>(
        'geo/provinces',
        queryParameters: {'department_id': departmentId},
      );
      return (r.data!['data'] as List<dynamic>).cast<Map<String, dynamic>>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<List<Map<String, dynamic>>> districts({required int provinceId}) async {
    try {
      final r = await _dio.get<Map<String, dynamic>>(
        'geo/districts',
        queryParameters: {'province_id': provinceId},
      );
      return (r.data!['data'] as List<dynamic>).cast<Map<String, dynamic>>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
