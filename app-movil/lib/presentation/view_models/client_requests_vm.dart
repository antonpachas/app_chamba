import 'package:chamba_app/data/api/client_api.dart';
import 'package:chamba_app/data/models/service_request_model.dart';
import 'package:flutter/foundation.dart';

class ClientRequestsViewModel extends ChangeNotifier {
  ClientRequestsViewModel({required this.api});

  final ClientApi api;

  List<ClientRequest> items = [];
  bool loading = false;
  String? error;
  String? ok;

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

  Future<void> close(int id) async {
    try {
      await api.closeServiceRequest(id);
      ok = 'Solicitud cerrada.';
      await load();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }
}
