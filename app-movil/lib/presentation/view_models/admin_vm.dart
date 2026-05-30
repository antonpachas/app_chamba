import 'package:chamba_app/data/api/admin_api.dart';
import 'package:flutter/foundation.dart';

class AdminViewModel extends ChangeNotifier {
  AdminViewModel({required this.api});

  final AdminApi api;

  // Dashboard
  Map<String, dynamic>? dashboardData;
  bool dashboardLoading = false;

  // Users
  List<dynamic> users = [];
  Map<String, dynamic> usersMeta = {};
  bool usersLoading = false;
  String userQ = '';
  String userRole = 'all';

  // Listings
  List<dynamic> listings = [];
  Map<String, dynamic> listingsMeta = {};
  bool listingsLoading = false;
  String listingsFilter = 'all';

  // Reports
  Map<String, dynamic>? reportsData;
  bool reportsLoading = false;

  // Support
  List<Map<String, dynamic>> tickets = [];
  bool ticketsLoading = false;

  String? error;
  String? ok;

  Future<void> loadDashboard() async {
    dashboardLoading = true;
    error = null;
    notifyListeners();
    try {
      dashboardData = await api.dashboard();
    } catch (e) {
      error = e.toString();
    } finally {
      dashboardLoading = false;
      notifyListeners();
    }
  }

  Future<void> loadUsers({int page = 1}) async {
    usersLoading = true;
    error = null;
    notifyListeners();
    try {
      final r = await api.users(q: userQ.isEmpty ? null : userQ, role: userRole, page: page);
      users = r['data'] as List<dynamic>? ?? [];
      usersMeta = r['meta'] as Map<String, dynamic>? ?? {};
    } catch (e) {
      error = e.toString();
    } finally {
      usersLoading = false;
      notifyListeners();
    }
  }

  Future<void> suspendUser(int id, String reason) async {
    try {
      await api.suspendUser(id, reason);
      ok = 'Usuario suspendido.';
      await loadUsers();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }

  Future<void> activateUser(int id) async {
    try {
      await api.activateUser(id);
      ok = 'Usuario activado.';
      await loadUsers();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }

  Future<void> loadListings({int page = 1}) async {
    listingsLoading = true;
    error = null;
    notifyListeners();
    try {
      final r = await api.listings(filter: listingsFilter, page: page);
      listings = r['data'] as List<dynamic>? ?? [];
      listingsMeta = r['meta'] as Map<String, dynamic>? ?? {};
    } catch (e) {
      error = e.toString();
    } finally {
      listingsLoading = false;
      notifyListeners();
    }
  }

  Future<void> hideListing(int id) async {
    try {
      await api.hideListing(id);
      ok = 'Anuncio ocultado.';
      await loadListings();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }

  Future<void> restoreListing(int id) async {
    try {
      await api.restoreListing(id);
      ok = 'Anuncio restaurado.';
      await loadListings();
    } catch (e) {
      error = e.toString();
      notifyListeners();
    }
  }

  Future<void> loadReports() async {
    reportsLoading = true;
    error = null;
    notifyListeners();
    try {
      reportsData = await api.reports();
    } catch (e) {
      error = e.toString();
    } finally {
      reportsLoading = false;
      notifyListeners();
    }
  }

  Future<void> loadTickets({String status = 'all'}) async {
    ticketsLoading = true;
    error = null;
    notifyListeners();
    try {
      tickets = await api.supportTickets(status: status);
    } catch (e) {
      error = e.toString();
    } finally {
      ticketsLoading = false;
      notifyListeners();
    }
  }
}
