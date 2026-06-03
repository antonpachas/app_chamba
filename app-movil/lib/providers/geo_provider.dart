import 'package:flutter/foundation.dart';
import '../data/models/geo.dart';
import '../data/repositories/geo_repository.dart';

class GeoProvider extends ChangeNotifier {
  GeoProvider(this._repo);
  final GeoRepository _repo;

  List<Department> _departments = [];
  List<Province>   _provinces   = [];
  List<District>   _districts   = [];
  int? _selectedDeptId;
  int? _selectedProvId;
  int? _selectedDistId;
  bool _loadingDepts = false;
  bool _loadingProvs = false;
  bool _loadingDists = false;

  List<Department> get departments       => _departments;
  List<Province>   get provinces         => _provinces;
  List<District>   get districts         => _districts;
  int?             get selectedDeptId    => _selectedDeptId;
  int?             get selectedProvId    => _selectedProvId;
  int?             get selectedDistId    => _selectedDistId;
  bool             get loadingDepts      => _loadingDepts;
  bool             get loadingProvs      => _loadingProvs;
  bool             get loadingDists      => _loadingDists;

  String? get selectedDeptName =>
      _departments.where((d) => d.id == _selectedDeptId).map((d) => d.name).firstOrNull;
  String? get selectedProvName =>
      _provinces.where((p) => p.id == _selectedProvId).map((p) => p.name).firstOrNull;
  String? get selectedDistName =>
      _districts.where((d) => d.id == _selectedDistId).map((d) => d.name).firstOrNull;

  Future<void> loadDepartments() async {
    if (_departments.isNotEmpty) return;
    _loadingDepts = true;
    notifyListeners();
    try {
      _departments = await _repo.departments();
    } catch (_) {}
    _loadingDepts = false;
    notifyListeners();
  }

  Future<void> selectDepartment(int? id) async {
    _selectedDeptId = id;
    _selectedProvId = null;
    _selectedDistId = null;
    _provinces = [];
    _districts = [];
    notifyListeners();
    if (id == null) return;
    _loadingProvs = true;
    notifyListeners();
    try {
      _provinces = await _repo.provinces(id);
    } catch (_) {}
    _loadingProvs = false;
    notifyListeners();
  }

  Future<void> selectProvince(int? id) async {
    _selectedProvId = id;
    _selectedDistId = null;
    _districts = [];
    notifyListeners();
    if (id == null) return;
    _loadingDists = true;
    notifyListeners();
    try {
      _districts = await _repo.districts(id);
    } catch (_) {}
    _loadingDists = false;
    notifyListeners();
  }

  void selectDistrict(int? id) {
    _selectedDistId = id;
    notifyListeners();
  }

  void clear() {
    _selectedDeptId = null;
    _selectedProvId = null;
    _selectedDistId = null;
    _provinces = [];
    _districts = [];
    notifyListeners();
  }
}
