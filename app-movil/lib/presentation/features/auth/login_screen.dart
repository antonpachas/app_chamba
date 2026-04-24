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
    final textTheme = Theme.of(context).textTheme;
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;

    return Scaffold(
      backgroundColor: scheme.surfaceContainerLow,
      resizeToAvoidBottomInset: true,
      body: CustomScrollView(
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        slivers: [
          const SliverToBoxAdapter(
            child: AuthHeroHeader(
              compact: true,
              title: 'Acceso a Chamba',
              subtitle: 'Correo y contraseña, u otras opciones abajo.',
            ),
          ),
          SliverPadding(
            padding: EdgeInsets.fromLTRB(20, 8, 20, 28 + bottomInset),
            sliver: SliverToBoxAdapter(
              child: Transform.translate(
                offset: const Offset(0, -18),
                child: Material(
                  color: scheme.surface,
                  elevation: 2,
                  shadowColor: Colors.black.withValues(alpha: 0.06),
                  borderRadius: BorderRadius.circular(24),
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(22, 26, 22, 24),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Iniciar sesión',
                            style: textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w800,
                              letterSpacing: -0.4,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Introduce tus credenciales.',
                            style: textTheme.bodyMedium?.copyWith(
                              color: scheme.onSurfaceVariant,
                              height: 1.35,
                            ),
                          ),
                          const SizedBox(height: 22),
                          TextFormField(
                            controller: _email,
                            decoration: const InputDecoration(
                              labelText: 'Correo electrónico',
                              prefixIcon: Icon(Icons.alternate_email_rounded),
                              alignLabelWithHint: true,
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
                              alignLabelWithHint: true,
                            ),
                            obscureText: true,
                            textInputAction: TextInputAction.done,
                            onFieldSubmitted: (_) => _submit(context, vm),
                            validator: (v) => (v == null || v.isEmpty) ? 'Campo obligatorio' : null,
                          ),
                          const SizedBox(height: 8),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton.icon(
                              onPressed: vm.loading ? null : () => context.push('/forgot-password'),
                              icon: Icon(
                                Icons.lock_reset_rounded,
                                size: 18,
                                color: scheme.primary,
                              ),
                              label: const Text('Recuperar contraseña'),
                              style: TextButton.styleFrom(
                                foregroundColor: scheme.primary,
                                textStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w600),
                              ),
                            ),
                          ),
                          if (vm.error != null) ...[
                            const SizedBox(height: 6),
                            ErrorBanner(message: vm.error!),
                          ],
                          const SizedBox(height: 18),
                          FilledButton(
                            style: FilledButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                            ),
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
                          const SizedBox(height: 22),
                          Row(
                            children: [
                              Expanded(child: Divider(color: scheme.outlineVariant.withValues(alpha: 0.6))),
                              Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 12),
                                child: Text(
                                  'Más opciones',
                                  style: textTheme.labelSmall?.copyWith(
                                    color: scheme.onSurfaceVariant,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 0.6,
                                  ),
                                ),
                              ),
                              Expanded(child: Divider(color: scheme.outlineVariant.withValues(alpha: 0.6))),
                            ],
                          ),
                          const SizedBox(height: 18),
                          OutlinedButton(
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              side: BorderSide(color: scheme.outline.withValues(alpha: 0.85)),
                            ),
                            onPressed: vm.loading ? null : () => context.push('/register'),
                            child: const Text('Crear cuenta nueva'),
                          ),
                          const SizedBox(height: 10),
                          OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              foregroundColor: scheme.onSurfaceVariant,
                              side: BorderSide(color: scheme.outlineVariant),
                            ),
                            onPressed: vm.loading
                                ? null
                                : () async {
                                    await context.read<SessionViewModel>().enterGuestMode();
                                    if (context.mounted) context.go('/home');
                                  },
                            icon: const Icon(Icons.explore_outlined, size: 20),
                            label: const Text('Explorar como invitado'),
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
