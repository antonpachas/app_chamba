import 'package:chamba_app/data/api/api_exception.dart';
import 'package:chamba_app/data/api/provider_api.dart';
import 'package:flutter/foundation.dart';

class DashboardTabViewModel extends ChangeNotifier {
  DashboardTabViewModel({required ProviderApi providerApi}) : _providerApi = providerApi;

  final ProviderApi _providerApi;

  Map<String, dynamic>? _data;
  bool _loading = false;
  String? _error;

  Map<String, dynamic>? get data => _data;
  bool get loading => _loading;
  String? get error => _error;

  Future<void> load() async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      _data = await _providerApi.dashboard();
    } on ApiException catch (e) {
      _error = e.message;
    } catch (e) {
      _error = e.toString();
    } finally {
      _loading = false;
      notifyListeners();
    }
  }
}
