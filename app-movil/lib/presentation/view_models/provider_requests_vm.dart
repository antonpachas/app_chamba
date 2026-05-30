import 'package:chamba_app/data/api/provider_api.dart';
import 'package:chamba_app/data/models/service_request_model.dart';
import 'package:flutter/foundation.dart';

class ProviderRequestsViewModel extends ChangeNotifier {
  ProviderRequestsViewModel({required this.api});

  final ProviderApi api;

  List<ReceivedRequest> items = [];
  bool loading = false;
  String? error;
  String? ok;
  String statusFilter = 'all';

  List<ReceivedRequest> get filtered {
    if (statusFilter == 'all') return items;
    return items.where((r) => r.status == statusFilter).toList();
  }

  Future<void> load() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      items = await api.serviceRequests();
    } catch (e) {
      error = e.toString();
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  void setFilter(String f) {
    statusFilter = f;
    notifyListeners();
  }

  Future<void> markSeen(int id) async {
    try {
      await api.updateRequestStatus(id, 'visto');
      await load();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }

  Future<void> markDone(int id) async {
    try {
      await api.updateRequestStatus(id, 'cerrado');
      ok = 'Solicitud marcada como atendida.';
      await load();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }
}
