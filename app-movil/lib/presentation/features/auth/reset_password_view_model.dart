import 'package:chamba_app/data/api/api_exception.dart';
import 'package:chamba_app/data/api/auth_api.dart';
import 'package:flutter/foundation.dart';

class ResetPasswordViewModel extends ChangeNotifier {
  ResetPasswordViewModel({required AuthApi authApi}) : _authApi = authApi;

  final AuthApi _authApi;

  bool _loading = false;
  String? _error;

  bool get loading => _loading;
  String? get error => _error;

  Future<void> submit({
    required String email,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      await _authApi.resetPassword(
        email: email,
        token: token,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
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
