import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/data/local/token_storage.dart';
import 'package:chamba_app/data/models/user_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Estado global de sesión (usuario + token, o modo invitado).
class SessionViewModel extends ChangeNotifier {
  SessionViewModel({
    required AuthApi authApi,
    required TokenStorage tokenStorage,
    required SharedPreferences prefs,
  })  : _authApi = authApi,
        _tokenStorage = tokenStorage,
        _prefs = prefs;

  static const _guestKey = 'chamba_guest_mode';

  final AuthApi _authApi;
  final TokenStorage _tokenStorage;
  final SharedPreferences _prefs;

  UserModel? _user;
  bool _isGuest = false;
  bool _ready = false;

  UserModel? get user => _user;
  bool get isReady => _ready;

  /// Sesión con cuenta (token + usuario cargado).
  bool get isLoggedIn => _user != null;

  /// Exploración sin cuenta (persistido para no forzar login en cada apertura).
  bool get isGuest => _isGuest;

  /// Puede entrar al home: cuenta o invitado.
  bool get canAccessHome => isLoggedIn || isGuest;

  /// Restaura invitado (preferencias) o token y `/auth/me`.
  Future<void> restore() async {
    _ready = false;
    notifyListeners();

    final guest = _prefs.getBool(_guestKey) ?? false;
    if (guest) {
      _isGuest = true;
      _user = null;
      _ready = true;
      notifyListeners();
      return;
    }

    _isGuest = false;
    final token = await _tokenStorage.readToken();
    if (token == null || token.isEmpty) {
      _user = null;
      _ready = true;
      notifyListeners();
      return;
    }

    try {
      _user = await _authApi.me();
    } catch (_) {
      await _tokenStorage.deleteToken();
      _user = null;
    }

    _ready = true;
    notifyListeners();
  }

  /// Entrar como invitado (solo endpoints públicos de la API).
  Future<void> enterGuestMode() async {
    await _prefs.setBool(_guestKey, true);
    await _tokenStorage.deleteToken();
    _isGuest = true;
    _user = null;
    notifyListeners();
  }

  /// Dejar modo invitado (vuelve a pantalla de acceso sin borrar cuentas).
  Future<void> exitGuestMode() async {
    await _prefs.setBool(_guestKey, false);
    _isGuest = false;
    notifyListeners();
  }

  Future<void> applyLogin(String token, UserModel user) async {
    await _prefs.setBool(_guestKey, false);
    _isGuest = false;
    await _tokenStorage.writeToken(token);
    _user = user;
    notifyListeners();
  }

  Future<void> logout() async {
    if (_user != null) {
      try {
        await _authApi.logout();
      } catch (_) {
        // Ignorar si el token ya no es válido.
      }
      await _tokenStorage.deleteToken();
    }
    await _prefs.setBool(_guestKey, false);
    _isGuest = false;
    _user = null;
    notifyListeners();
  }

  void replaceUser(UserModel user) {
    _user = user;
    notifyListeners();
  }
}
