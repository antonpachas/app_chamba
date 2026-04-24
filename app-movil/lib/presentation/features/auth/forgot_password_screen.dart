import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/presentation/common/widgets/error_banner.dart';
import 'package:chamba_app/presentation/features/auth/forgot_password_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class ForgotPasswordScreen extends StatelessWidget {
  const ForgotPasswordScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => ForgotPasswordViewModel(authApi: ctx.read<AuthApi>()),
      child: const _ForgotBody(),
    );
  }
}

class _ForgotBody extends StatefulWidget {
  const _ForgotBody();

  @override
  State<_ForgotBody> createState() => _ForgotBodyState();
}

class _ForgotBodyState extends State<_ForgotBody> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<ForgotPasswordViewModel>();
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Recuperar contraseña'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Te enviaremos un enlace a tu correo para elegir una nueva contraseña. '
                'Revisa también la carpeta de spam.',
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: scheme.onSurfaceVariant,
                      height: 1.45,
                    ),
              ),
              const SizedBox(height: 24),
              if (vm.sent)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Row(
                      children: [
                        Icon(Icons.mark_email_read_outlined, color: scheme.primary, size: 32),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Text(
                            'Si ese correo está registrado en Chamba, revisa tu bandeja de entrada.',
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              else ...[
                TextFormField(
                  controller: _email,
                  decoration: const InputDecoration(
                    labelText: 'Correo electrónico',
                    prefixIcon: Icon(Icons.alternate_email_rounded),
                  ),
                  keyboardType: TextInputType.emailAddress,
                  textInputAction: TextInputAction.done,
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null,
                ),
                if (vm.error != null) ...[
                  const SizedBox(height: 14),
                  ErrorBanner(message: vm.error!),
                ],
                const SizedBox(height: 22),
                FilledButton(
                  onPressed: vm.loading ? null : () => _submit(vm),
                  child: vm.loading
                      ? SizedBox(
                          height: 22,
                          width: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: scheme.onPrimary,
                          ),
                        )
                      : const Text('Enviar enlace'),
                ),
              ],
              const SizedBox(height: 8),
              Center(
                child: TextButton(
                  onPressed: () => context.push('/reset-password'),
                  child: const Text('Ya tengo el token del correo'),
                ),
              ),
              const SizedBox(height: 4),
              TextButton(
                onPressed: () => context.pop(),
                child: const Text('Volver al inicio de sesión'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit(ForgotPasswordViewModel vm) async {
    if (!_formKey.currentState!.validate()) return;
    await vm.submit(_email.text);
  }
}
