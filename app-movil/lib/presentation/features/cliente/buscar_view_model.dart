import 'package:chamba_app/data/api/api_exception.dart';
import 'package:chamba_app/data/api/catalog_api.dart';
import 'package:chamba_app/data/api/listing_api.dart';
import 'package:chamba_app/data/api/search_api.dart';
import 'package:chamba_app/data/models/category_model.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:flutter/foundation.dart';

class BuscarViewModel extends ChangeNotifier {
  BuscarViewModel({
    required CatalogApi catalogApi,
    required SearchApi searchApi,
    required ListingApi listingApi,
  })  : _catalogApi = catalogApi,
        _searchApi = searchApi,
        _listingApi = listingApi;

  final CatalogApi _catalogApi;
  final SearchApi _searchApi;
  final ListingApi _listingApi;

  List<CategoryModel> _categories = [];
  List<Map<String, dynamic>> _results = [];
  List<ListingCard> _featured = [];

  bool _loadingCats = false;
  bool _loadingSearch = false;
  bool _loadingFeatured = false;
  bool _hasSearched = false;
  String? _error;

  int? _selectedCategoryId;
  String _keyword = '';

  List<CategoryModel> get categories => _categories;
  List<Map<String, dynamic>> get results => _results;
  List<ListingCard> get featured => _featured;
  bool get loadingCategories => _loadingCats;
  bool get loadingSearch => _loadingSearch;
  bool get loadingFeatured => _loadingFeatured;
  bool get hasSearched => _hasSearched;
  String? get error => _error;
  int? get selectedCategoryId => _selectedCategoryId;
  String get keyword => _keyword;

  void setKeyword(String v) {
    _keyword = v;
    notifyListeners();
  }

  void setCategory(int? id) {
    _selectedCategoryId = id;
    notifyListeners();
  }

  Future<void> loadCategories() async {
    _loadingCats = true;
    _error = null;
    notifyListeners();
    try {
      _categories = await _catalogApi.categories();
    } on ApiException catch (e) {
      _error = e.message;
    } catch (e) {
      _error = e.toString();
    } finally {
      _loadingCats = false;
      notifyListeners();
    }
  }

  Future<void> loadFeatured() async {
    _loadingFeatured = true;
    notifyListeners();
    try {
      _featured = await _listingApi.homeFeatured();
    } catch (_) {
      _featured = [];
    } finally {
      _loadingFeatured = false;
      notifyListeners();
    }
  }

  Future<void> search() async {
    _loadingSearch = true;
    _error = null;
    notifyListeners();
    try {
      _results = await _searchApi.searchServices(
        categoryId: _selectedCategoryId,
        keyword: _keyword.isEmpty ? null : _keyword,
      );
    } on ApiException catch (e) {
      _error = e.message;
    } catch (e) {
      _error = e.toString();
    } finally {
      _loadingSearch = false;
      _hasSearched = true;
      notifyListeners();
    }
  }
}
