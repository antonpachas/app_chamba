import 'package:chamba_app/data/api/api_exception.dart';
import 'package:chamba_app/data/api/auth_api.dart';
import 'package:flutter/foundation.dart';

class ForgotPasswordViewModel extends ChangeNotifier {
  ForgotPasswordViewModel({required AuthApi authApi}) : _authApi = authApi;

  final AuthApi _authApi;

  bool _loading = false;
  String? _error;
  bool _sent = false;

  bool get loading => _loading;
  String? get error => _error;
  bool get sent => _sent;

  Future<void> submit(String email) async {
    _loading = true;
    _error = null;
    _sent = false;
    notifyListeners();

    try {
      await _authApi.forgotPassword(email: email.trim());
      _sent = true;
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
