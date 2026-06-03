import '../models/geo.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';

class GeoRepository {
  GeoRepository(this._api);
  final ApiClient _api;

  Future<List<Department>> departments() async {
    final data = await _api.get(Endpoints.departments);
    final rows = (data['data'] as List?) ?? [];
    return rows.map((e) => Department.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<Province>> provinces(int departmentId) async {
    final data = await _api.get(Endpoints.provinces, params: {'department_id': departmentId});
    final rows = (data['data'] as List?) ?? [];
    return rows.map((e) => Province.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<District>> districts(int provinceId) async {
    final data = await _api.get(Endpoints.districts, params: {'province_id': provinceId});
    final rows = (data['data'] as List?) ?? [];
    return rows.map((e) => District.fromJson(e as Map<String, dynamic>)).toList();
  }
}
