import 'package:chamba_app/data/api/client_api.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:flutter/foundation.dart';

class FavoritesViewModel extends ChangeNotifier {
  FavoritesViewModel({required this.api});

  final ClientApi api;

  List<ListingCard> items = [];
  bool loading = false;
  String? error;

  Future<void> load() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      items = await api.favorites();
    } catch (e) {
      error = e.toString();
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> toggleFavorite(int providerProfileId) async {
    try {
      await api.toggleFavorite(providerProfileId: providerProfileId);
      await load();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }
}
