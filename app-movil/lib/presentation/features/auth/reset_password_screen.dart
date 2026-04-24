import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/presentation/common/widgets/error_banner.dart';
import 'package:chamba_app/presentation/features/auth/reset_password_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

/// Restablece la contraseña con el token del correo (pega token y correo si abriste el enlace en otro dispositivo).
class ResetPasswordScreen extends StatelessWidget {
  const ResetPasswordScreen({
    super.key,
    this.initialEmail = '',
    this.initialToken = '',
  });

  final String initialEmail;
  final String initialToken;

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => ResetPasswordViewModel(authApi: ctx.read<AuthApi>()),
      child: _ResetBody(
        initialEmail: initialEmail,
        initialToken: initialToken,
      ),
    );
  }
}

class _ResetBody extends StatefulWidget {
  const _ResetBody({
    required this.initialEmail,
    required this.initialToken,
  });

  final String initialEmail;
  final String initialToken;

  @override
  State<_ResetBody> createState() => _ResetBodyState();
}

class _ResetBodyState extends State<_ResetBody> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _email;
  late final TextEditingController _token;
  final _password = TextEditingController();
  final _password2 = TextEditingController();

  @override
  void initState() {
    super.initState();
    _email = TextEditingController(text: widget.initialEmail);
    _token = TextEditingController(text: widget.initialToken);
  }

  @override
  void dispose() {
    _email.dispose();
    _token.dispose();
    _password.dispose();
    _password2.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<ResetPasswordViewModel>();
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Nueva contraseña'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Pega el token del correo y tu correo, o abre el enlace del correo en el navegador.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: scheme.onSurfaceVariant,
                      height: 1.4,
                    ),
              ),
              const SizedBox(height: 20),
              TextFormField(
                controller: _email,
                decoration: const InputDecoration(
                  labelText: 'Correo',
                  prefixIcon: Icon(Icons.alternate_email_rounded),
                ),
                keyboardType: TextInputType.emailAddress,
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _token,
                decoration: const InputDecoration(
                  labelText: 'Token del enlace',
                  prefixIcon: Icon(Icons.key_rounded),
                  hintText: 'Largo código del correo',
                ),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _password,
                decoration: const InputDecoration(
                  labelText: 'Nueva contraseña',
                  prefixIcon: Icon(Icons.lock_outline_rounded),
                ),
                obscureText: true,
                validator: (v) => (v == null || v.length < 8) ? 'Mínimo 8 caracteres' : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _password2,
                decoration: const InputDecoration(
                  labelText: 'Confirmar contraseña',
                  prefixIcon: Icon(Icons.lock_reset_rounded),
                ),
                obscureText: true,
                validator: (v) => v != _password.text ? 'No coincide' : null,
              ),
              if (vm.error != null) ...[
                const SizedBox(height: 14),
                ErrorBanner(message: vm.error!),
              ],
              const SizedBox(height: 22),
              FilledButton(
                onPressed: vm.loading ? null : () => _submit(context, vm),
                child: vm.loading
                    ? SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.5,
                          color: scheme.onPrimary,
                        ),
                      )
                    : const Text('Guardar contraseña'),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: () => context.go('/login'),
                child: const Text('Ir a iniciar sesión'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit(BuildContext context, ResetPasswordViewModel vm) async {
    if (!_formKey.currentState!.validate()) return;
    await vm.submit(
      email: _email.text.trim(),
      token: _token.text.trim(),
      password: _password.text,
      passwordConfirmation: _password2.text,
    );
    if (!context.mounted) return;
    if (vm.error == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Contraseña actualizada. Ya puedes iniciar sesión.')),
      );
      context.go('/login');
    }
  }
}
