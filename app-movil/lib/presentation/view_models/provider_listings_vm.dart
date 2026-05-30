import 'package:chamba_app/data/api/provider_api.dart';
import 'package:flutter/foundation.dart';

class ProviderListingsViewModel extends ChangeNotifier {
  ProviderListingsViewModel({required this.api});

  final ProviderApi api;

  List<Map<String, dynamic>> items = [];
  bool loading = false;
  String? error;
  String? ok;

  Future<void> load() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      items = await api.listings();
    } catch (e) {
      error = e.toString();
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> toggleStatus(int id, String currentStatus) async {
    final next = currentStatus == 'active' ? 'hidden' : 'active';
    try {
      await api.toggleListingStatus(id, next);
      ok = next == 'active' ? 'Anuncio activado.' : 'Anuncio ocultado.';
      await load();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }
}
