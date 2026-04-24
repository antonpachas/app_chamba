import 'package:chamba_app/data/api/api_exception.dart';
import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/foundation.dart';

class LoginViewModel extends ChangeNotifier {
  LoginViewModel({
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

  Future<void> submit({required String email, required String password}) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      final result = await _authApi.login(email: email.trim(), password: password);
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
}
