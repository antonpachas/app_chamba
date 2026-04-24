import 'package:chamba_app/data/api/api_client.dart';
import 'package:dio/dio.dart';

/// Endpoints de proveedor autenticado (`/provider/*`).
class ProviderApi {
  ProviderApi(this._dio);

  final Dio _dio;

  Future<Map<String, dynamic>> dashboard() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('provider/dashboard');
      return (r.data!['data'] as Map).cast<String, dynamic>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> profile() async {
    try {
      final r = await _dio.get<Map<String, dynamic>>('provider/profile');
      return (r.data!['data'] as Map).cast<String, dynamic>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }

  Future<Map<String, dynamic>> createProfile({
    required int districtId,
    String? businessName,
    String? description,
    String? whatsapp,
    String? contactPhone,
    String? addressText,
  }) async {
    try {
      final data = <String, dynamic>{'district_id': districtId};
      if (businessName != null) data['business_name'] = businessName;
      if (description != null) data['description'] = description;
      if (whatsapp != null) data['whatsapp'] = whatsapp;
      if (contactPhone != null) data['contact_phone'] = contactPhone;
      if (addressText != null) data['address_text'] = addressText;

      final r = await _dio.post<Map<String, dynamic>>(
        'provider/profile',
        data: data,
      );
      return (r.data!['data'] as Map).cast<String, dynamic>();
    } on DioException catch (e) {
      throw ApiClient.mapDioException(e);
    }
  }
}
