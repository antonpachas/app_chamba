import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/presentation/common/widgets/auth_hero_header.dart';
import 'package:chamba_app/presentation/common/widgets/error_banner.dart';
import 'package:chamba_app/presentation/features/auth/login_view_model.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class LoginScreen extends StatelessWidget {
  const LoginScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => LoginViewModel(
        authApi: ctx.read<AuthApi>(),
        session: ctx.read<SessionViewModel>(),
      ),
      child: const _LoginBody(),
    );
  }
}

class _LoginBody extends StatefulWidget {
  const _LoginBody();

  @override
  State<_LoginBody> createState() => _LoginBodyState();
}

class _LoginBodyState extends State<_LoginBody> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<LoginViewModel>();
    final scheme = Theme.of(context).colorScheme;

    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;

    return Scaffold(
      resizeToAvoidBottomInset: true,
      body: Column(
        children: [
          const AuthHeroHeader(
            title: 'Bienvenido',
            subtitle: 'Encuentra oficios de confianza cerca de ti.',
          ),
          Expanded(
            child: Transform.translate(
              offset: const Offset(0, -20),
              child: SingleChildScrollView(
                padding: EdgeInsets.fromLTRB(20, 0, 20, 28 + bottomInset),
                child: Card(
                  elevation: 2,
                  shadowColor: scheme.shadow.withValues(alpha: 0.12),
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(22, 26, 22, 22),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Iniciar sesión',
                            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: -0.3,
                                ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Usa el correo con el que te registraste.',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                  color: scheme.onSurfaceVariant,
                                  height: 1.4,
                                ),
                          ),
                          const SizedBox(height: 24),
                          TextFormField(
                            controller: _email,
                            decoration: const InputDecoration(
                              labelText: 'Correo electrónico',
                              prefixIcon: Icon(Icons.alternate_email_rounded),
                            ),
                            keyboardType: TextInputType.emailAddress,
                            textInputAction: TextInputAction.next,
                            validator: (v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _password,
                            decoration: const InputDecoration(
                              labelText: 'Contraseña',
                              prefixIcon: Icon(Icons.lock_outline_rounded),
                            ),
                            obscureText: true,
                            textInputAction: TextInputAction.done,
                            onFieldSubmitted: (_) => _submit(context, vm),
                            validator: (v) => (v == null || v.isEmpty) ? 'Campo obligatorio' : null,
                          ),
                          if (vm.error != null) ...[
                            const SizedBox(height: 14),
                            ErrorBanner(message: vm.error!),
                          ],
                          const SizedBox(height: 24),
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
                                : const Text('Entrar'),
                          ),
                          const SizedBox(height: 12),
                          OutlinedButton.icon(
                            onPressed: vm.loading
                                ? null
                                : () async {
                                    await context.read<SessionViewModel>().enterGuestMode();
                                    if (context.mounted) context.go('/home');
                                  },
                            icon: const Icon(Icons.explore_outlined),
                            label: const Text('Solo quiero explorar'),
                          ),
                          const SizedBox(height: 4),
                          Center(
                            child: TextButton(
                              onPressed: vm.loading ? null : () => context.go('/register'),
                              child: const Text('¿No tienes cuenta? Crear una'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _submit(BuildContext context, LoginViewModel vm) async {
    if (!_formKey.currentState!.validate()) return;
    await vm.submit(email: _email.text, password: _password.text);
    if (!context.mounted) return;
    if (vm.error == null) {
      context.go('/home');
    }
  }
}
