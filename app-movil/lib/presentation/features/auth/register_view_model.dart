import 'package:chamba_app/data/api/api_exception.dart';
import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/foundation.dart';

class RegisterViewModel extends ChangeNotifier {
  RegisterViewModel({
    required AuthApi authApi,
    required SessionViewModel session,
  })  : _authApi = authApi,
        _session = session;

  final AuthApi _authApi;
  final SessionViewModel _session;

  bool _loading = false;
  String? _error;

  bool get loading => _loading;
  String? get error => _error;

  Future<void> submit({
    required String fullName,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String role,
    String? phone,
  }) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      final result = await _authApi.register(
        fullName: fullName.trim(),
        email: email.trim(),
        password: password,
        passwordConfirmation: passwordConfirmation,
        role: role,
        phone: _normalizePhone(phone),
      );
      await _session.applyLogin(result.token, result.user);
    } on ApiException catch (e) {
      _error = e.message;
    } catch (e) {
      _error = e.toString();
    } finally {
      _loading = false;
      notifyListeners();
    }
  }

  String? _normalizePhone(String? phone) {
    final p = phone?.trim();
    if (p == null || p.isEmpty) return null;
    return p;
  }
}
