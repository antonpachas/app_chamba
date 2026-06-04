import 'package:flutter/foundation.dart';
import '../data/models/listing.dart';
import '../data/repositories/search_repository.dart';

enum SearchStatus { idle, loading, success, error }

class SearchProvider extends ChangeNotifier {
  SearchProvider(this._repo);
  final SearchRepository _repo;

  List<Listing> _results = [];
  List<Listing> _featured = [];
  SearchStatus _status = SearchStatus.idle;
  String? _error;
  int _total = 0;
  int _page = 1;
  bool _hasMore = false;
  bool _isGuest = false;

  // Filtros activos
  String _keyword = '';
  int? _categoryId;
  int? _districtId;
  double? _userLat;
  double? _userLng;
  bool _useGps = false;
  String _sort = 'recent';

  List<Listing> get results  => _results;
  List<Listing> get featured => _featured;
  SearchStatus  get status   => _status;
  String?       get error    => _error;
  int           get total    => _total;
  bool          get hasMore  => _hasMore;
  bool          get isGuest  => _isGuest;
  bool          get searched => _status == SearchStatus.success || _status == SearchStatus.error;
  String        get keyword    => _keyword;
  int?          get categoryId => _categoryId;
  bool          get useGps     => _useGps;

  void setKeyword(String v)    { _keyword = v; notifyListeners(); }
  void setCategory(int? id)    { _categoryId = id; notifyListeners(); }
  void setDistrict(int? id)    { _districtId = id; notifyListeners(); }
  void setSort(String s)       { _sort = s; notifyListeners(); }
  void setGps(double lat, double lng) {
    _userLat = lat; _userLng = lng; _useGps = true;
    notifyListeners();
  }
  void clearGps() {
    _userLat = null; _userLng = null; _useGps = false;
    notifyListeners();
  }

  Future<void> search() async {
    _status = SearchStatus.loading;
    _page = 1;
    _error = null;
    notifyListeners();
    try {
      final res = await _repo.search(
        keyword:    _keyword.isEmpty ? null : _keyword,
        categoryId: _categoryId,
        districtId: _districtId,
        userLat:    _useGps ? _userLat : null,
        userLng:    _useGps ? _userLng : null,
        radiusKm:   _useGps ? 25 : null,
        sort:       _sort,
      );
      _results = res.listings;
      _total   = res.total;
      _isGuest = res.isGuest;
      _hasMore = _results.length < _total;
      _status  = SearchStatus.success;
    } catch (e) {
      _error  = e.toString();
      _status = SearchStatus.error;
    }
    notifyListeners();
  }

  Future<void> loadMore() async {
    if (!_hasMore || _status == SearchStatus.loading) return;
    _page++;
    try {
      final res = await _repo.search(
        keyword:    _keyword.isEmpty ? null : _keyword,
        categoryId: _categoryId,
        districtId: _districtId,
        userLat:    _useGps ? _userLat : null,
        userLng:    _useGps ? _userLng : null,
        sort:       _sort,
        page:       _page,
      );
      _results.addAll(res.listings);
      _hasMore = _results.length < _total;
    } catch (_) {
      _page--;
    }
    notifyListeners();
  }

  Future<void> loadFeatured() async {
    try {
      _featured = await _repo.homeFeatured();
      notifyListeners();
    } catch (_) {}
  }

  void clear() {
    _results = []; _keyword = ''; _categoryId = null;
    _districtId = null; _status = SearchStatus.idle;
    _total = 0; _page = 1; _hasMore = false;
    notifyListeners();
  }
}
