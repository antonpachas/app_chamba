import 'package:flutter/foundation.dart' hide Category;
import '../data/models/category.dart';
import '../data/repositories/search_repository.dart';

class CatalogProvider extends ChangeNotifier {
  CatalogProvider(this._repo);
  final SearchRepository _repo;

  List<Category> _categories = [];
  bool _loaded = false;

  List<Category> get categories => _categories;

  Future<void> ensureLoaded() async {
    if (_loaded) return;
    try {
      _categories = await _repo.categories();
      _loaded = true;
      notifyListeners();
    } catch (_) {}
  }
}
