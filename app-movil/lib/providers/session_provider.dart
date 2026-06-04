import 'package:flutter/foundation.dart';
import '../core/storage/token_storage.dart';
import '../data/models/user.dart';
import '../data/repositories/auth_repository.dart';

enum SessionState { loading, guest, authenticated }

class SessionProvider extends ChangeNotifier {
  SessionProvider(this._auth, this._storage);

  final AuthRepository _auth;
  final TokenStorage _storage;

  SessionState _state = SessionState.loading;
  User? _user;
  String? _error;

  SessionState get state => _state;
  User? get user => _user;
  String? get error => _error;
  bool get isLoading        => _state == SessionState.loading;
  bool get isAuthenticated  => _state == SessionState.authenticated;
  bool get isGuest          => _state == SessionState.guest;

  Future<void> init() async {
    final token = await _storage.read();
    if (token == null) {
      _state = SessionState.guest;
      notifyListeners();
      return;
    }
    try {
      _user = await _auth.me();
      _state = SessionState.authenticated;
    } catch (_) {
      await _storage.delete();
      _state = SessionState.guest;
    }
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    _error = null;
    try {
      _user = await _auth.login(email, password);
      _state = SessionState.authenticated;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      rethrow;
    }
  }

  Future<void> register({
    required String fullName,
    required String email,
    required String password,
    required String role,
    required String phone,
    String? razonSocial,
    String? ruc,
  }) async {
    _error = null;
    try {
      _user = await _auth.register(
        fullName: fullName,
        email: email,
        password: password,
        role: role,
        phone: phone,
        razonSocial: razonSocial,
        ruc: ruc,
      );
      _state = SessionState.authenticated;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      rethrow;
    }
  }

  Future<void> logout() async {
    await _auth.logout();
    _user = null;
    _state = SessionState.guest;
    notifyListeners();
  }

  void updateUserData(User user) {
    _user = user;
    notifyListeners();
  }
}
