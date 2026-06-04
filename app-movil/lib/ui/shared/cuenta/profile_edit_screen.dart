import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/endpoints.dart';
import '../../../data/models/user.dart';
import '../../../data/repositories/auth_repository.dart';
import '../../../providers/session_provider.dart';
import '../../shared/widgets/app_button.dart';

class ProfileEditScreen extends StatefulWidget {
  const ProfileEditScreen({super.key});

  @override
  State<ProfileEditScreen> createState() => _ProfileEditScreenState();
}

class _ProfileEditScreenState extends State<ProfileEditScreen> {
  final _form            = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _phone;
  final _currentPass     = TextEditingController();
  final _newPass         = TextEditingController();
  final _confirmPass     = TextEditingController();
  bool _loading          = false;
  bool _obscureCurrent   = true;
  bool _obscureNew       = true;
  bool _showPassSection  = false;
  bool _uploadingAvatar  = false;
  String? _avatarUrl;
  String? _error;
  String? _success;
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    final user = context.read<SessionProvider>().user;
    _name      = TextEditingController(text: user?.fullName ?? '');
    _phone     = TextEditingController(text: user?.phone ?? '');
    _avatarUrl = user?.avatarUrl;
  }

  @override
  void dispose() {
    _name.dispose(); _phone.dispose();
    _currentPass.dispose(); _newPass.dispose(); _confirmPass.dispose();
    super.dispose();
  }

  Future<void> _uploadAvatar() async {
    final file = await _picker.pickImage(
        source: ImageSource.gallery, imageQuality: 80, maxWidth: 800);
    if (file == null || !mounted) return;
    setState(() => _uploadingAvatar = true);
    try {
      final api  = context.read<ApiClient>();
      final form = FormData.fromMap({
        'avatar': await MultipartFile.fromFile(file.path),
      });
      final resp = await api.postForm(Endpoints.uploadAvatar, data: form);
      final url = (resp['data'] ?? resp)['avatar_url'] as String?;
      if (url != null && mounted) {
        setState(() => _avatarUrl = url);
        final cur = context.read<SessionProvider>().user!;
        context.read<SessionProvider>().updateUserData(User(
          id:        cur.id,
          fullName:  cur.fullName,
          email:     cur.email,
          role:      cur.role,
          status:    cur.status,
          phone:     cur.phone,
          avatarUrl: url,
        ));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error al subir foto: $e')));
      }
    } finally {
      if (mounted) setState(() => _uploadingAvatar = false);
    }
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    if (_showPassSection && _newPass.text != _confirmPass.text) {
      setState(() => _error = 'Las contraseñas nuevas no coinciden.');
      return;
    }
    setState(() { _loading = true; _error = null; _success = null; });
    try {
      final updated = await context.read<AuthRepository>().updateMe(
        fullName:             _name.text.trim(),
        phone:                _phone.text.trim(),
        currentPassword:      _showPassSection ? _currentPass.text : null,
        newPassword:          _showPassSection && _newPass.text.isNotEmpty
                              ? _newPass.text : null,
        passwordConfirmation: _showPassSection && _confirmPass.text.isNotEmpty
                              ? _confirmPass.text : null,
      );
      if (mounted) {
        // Actualiza el estado de sesión con los nuevos datos
        context.read<SessionProvider>().updateUserData(updated);
        setState(() {
          _success = 'Perfil actualizado correctamente.';
          _showPassSection = false;
          _currentPass.clear();
          _newPass.clear();
          _confirmPass.clear();
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Editar datos personales')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _form,
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            // Avatar
            Center(
              child: Stack(clipBehavior: Clip.none, children: [
                CircleAvatar(
                  radius: 48,
                  backgroundColor: const Color(0xFFDBEAFF),
                  backgroundImage: _avatarUrl != null
                      ? NetworkImage(_avatarUrl!) : null,
                  child: _avatarUrl == null
                      ? Text(
                          _name.text.isNotEmpty
                              ? _name.text[0].toUpperCase() : '?',
                          style: const TextStyle(fontSize: 36,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF003874)))
                      : null,
                ),
                Positioned(
                  bottom: 0, right: 0,
                  child: GestureDetector(
                    onTap: _uploadingAvatar ? null : _uploadAvatar,
                    child: Container(
                      padding: const EdgeInsets.all(6),
                      decoration: const BoxDecoration(
                        color: Color(0xFF003874),
                        shape: BoxShape.circle,
                      ),
                      child: _uploadingAvatar
                          ? const SizedBox(width: 16, height: 16,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : const Icon(Icons.camera_alt_outlined,
                              color: Colors.white, size: 16),
                    ),
                  ),
                ),
              ]),
            ),
            const SizedBox(height: 24),

            if (_error != null) _Banner(text: _error!, isError: true),
            if (_success != null) _Banner(text: _success!, isError: false),
            const SizedBox(height: 8),

            // Nombre
            TextFormField(
              controller: _name,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                labelText: 'Nombre completo',
                prefixIcon: Icon(Icons.badge_outlined),
              ),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'Campo requerido' : null,
            ),
            const SizedBox(height: 16),

            // Teléfono
            TextFormField(
              controller: _phone,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Teléfono',
                prefixIcon: Icon(Icons.phone_outlined),
              ),
              validator: (v) {
                if (v == null || v.trim().isEmpty) return 'Campo requerido';
                if (!RegExp(r'^\d{7,15}$').hasMatch(v.trim())) {
                  return 'Solo dígitos, 7 a 15 números';
                }
                return null;
              },
            ),
            const SizedBox(height: 24),

            // Toggle cambio de contraseña
            OutlinedButton.icon(
              onPressed: () => setState(() => _showPassSection = !_showPassSection),
              icon: Icon(_showPassSection ? Icons.lock_open : Icons.lock_outline),
              label: Text(_showPassSection
                  ? 'Cancelar cambio de contraseña'
                  : 'Cambiar contraseña'),
            ),

            if (_showPassSection) ...[
              const SizedBox(height: 20),
              const Text('Cambiar contraseña',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700,
                      color: Color(0xFF64748B))),
              const SizedBox(height: 12),
              _PassField(
                controller: _currentPass,
                label: 'Contraseña actual',
                obscure: _obscureCurrent,
                onToggle: () => setState(() => _obscureCurrent = !_obscureCurrent),
                validator: (v) =>
                    (v == null || v.isEmpty) ? 'Ingresa tu contraseña actual' : null,
              ),
              const SizedBox(height: 12),
              _PassField(
                controller: _newPass,
                label: 'Nueva contraseña',
                obscure: _obscureNew,
                onToggle: () => setState(() => _obscureNew = !_obscureNew),
                validator: (v) =>
                    (v != null && v.isNotEmpty && v.length < 8)
                        ? 'Mínimo 8 caracteres' : null,
              ),
              const SizedBox(height: 12),
              _PassField(
                controller: _confirmPass,
                label: 'Confirmar nueva contraseña',
                obscure: _obscureNew,
                onToggle: () => setState(() => _obscureNew = !_obscureNew),
                validator: (v) =>
                    (v != null && v.isNotEmpty && v != _newPass.text)
                        ? 'Las contraseñas no coinciden' : null,
              ),
            ],

            const SizedBox(height: 32),
            AppButton(label: 'Guardar cambios', onPressed: _save, loading: _loading),
          ]),
        ),
      ),
    );
  }
}

class _PassField extends StatelessWidget {
  const _PassField({
    required this.controller,
    required this.label,
    required this.obscure,
    required this.onToggle,
    this.validator,
  });
  final TextEditingController controller;
  final String label;
  final bool obscure;
  final VoidCallback onToggle;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) => TextFormField(
    controller: controller,
    obscureText: obscure,
    decoration: InputDecoration(
      labelText: label,
      prefixIcon: const Icon(Icons.lock_outline),
      suffixIcon: IconButton(
        icon: Icon(obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
        onPressed: onToggle,
      ),
    ),
    validator: validator,
  );
}

class _Banner extends StatelessWidget {
  const _Banner({required this.text, required this.isError});
  final String text;
  final bool isError;

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 16),
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: isError ? const Color(0xFFFEE2E2) : const Color(0xFFDCFCE7),
      borderRadius: BorderRadius.circular(10),
    ),
    child: Row(children: [
      Icon(isError ? Icons.error_outline : Icons.check_circle_outline,
          color: isError ? const Color(0xFFDC2626) : const Color(0xFF16A34A), size: 18),
      const SizedBox(width: 8),
      Expanded(child: Text(text, style: TextStyle(
          color: isError ? const Color(0xFFDC2626) : const Color(0xFF16A34A),
          fontSize: 13))),
    ]),
  );
}
