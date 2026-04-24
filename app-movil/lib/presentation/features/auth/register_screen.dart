import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/presentation/common/widgets/auth_hero_header.dart';
import 'package:chamba_app/presentation/common/widgets/error_banner.dart';
import 'package:chamba_app/presentation/features/auth/register_view_model.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class RegisterScreen extends StatelessWidget {
  const RegisterScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => RegisterViewModel(
        authApi: ctx.read<AuthApi>(),
        session: ctx.read<SessionViewModel>(),
      ),
      child: const _RegisterBody(),
    );
  }
}

class _RegisterBody extends StatefulWidget {
  const _RegisterBody();

  @override
  State<_RegisterBody> createState() => _RegisterBodyState();
}

class _RegisterBodyState extends State<_RegisterBody> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  final _password2 = TextEditingController();
  String _role = 'cliente';

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    _password2.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<RegisterViewModel>();
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      body: Column(
        children: [
          const AuthHeroHeader(
            title: 'Crea tu cuenta',
            subtitle: 'Únete como cliente o como proveedor de servicios.',
          ),
          Expanded(
            child: Transform.translate(
              offset: const Offset(0, -18),
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
                child: Card(
                  elevation: 6,
                  shadowColor: scheme.primary.withValues(alpha: 0.2),
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Tipo de cuenta',
                            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w700,
                                ),
                          ),
                          const SizedBox(height: 10),
                          SegmentedButton<String>(
                            segments: const [
                              ButtonSegment<String>(
                                value: 'cliente',
                                label: Text('Cliente'),
                                icon: Icon(Icons.person_search_rounded),
                              ),
                              ButtonSegment<String>(
                                value: 'proveedor',
                                label: Text('Proveedor'),
                                icon: Icon(Icons.handyman_rounded),
                              ),
                            ],
                            selected: {_role},
                            onSelectionChanged: (s) => setState(() => _role = s.first),
                          ),
                          const SizedBox(height: 20),
                          TextFormField(
                            controller: _name,
                            decoration: const InputDecoration(
                              labelText: 'Nombre completo',
                              prefixIcon: Icon(Icons.badge_outlined),
                            ),
                            textCapitalization: TextCapitalization.words,
                            validator: (v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _email,
                            decoration: const InputDecoration(
                              labelText: 'Correo electrónico',
                              prefixIcon: Icon(Icons.alternate_email_rounded),
                            ),
                            keyboardType: TextInputType.emailAddress,
                            validator: (v) => (v == null || v.trim().isEmpty) ? 'Campo obligatorio' : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _phone,
                            decoration: const InputDecoration(
                              labelText: 'Teléfono (opcional)',
                              prefixIcon: Icon(Icons.phone_android_rounded),
                            ),
                            keyboardType: TextInputType.phone,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _password,
                            decoration: const InputDecoration(
                              labelText: 'Contraseña',
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
                                : const Text('Registrarme'),
                          ),
                          Center(
                            child: TextButton.icon(
                              onPressed: vm.loading
                                  ? null
                                  : () async {
                                      await context.read<SessionViewModel>().enterGuestMode();
                                      if (context.mounted) context.go('/home');
                                    },
                              icon: const Icon(Icons.visibility_outlined),
                              label: const Text('Explorar como invitado'),
                            ),
                          ),
                          Center(
                            child: TextButton(
                              onPressed: () => context.go('/login'),
                              child: const Text('Ya tengo cuenta'),
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

  Future<void> _submit(BuildContext context, RegisterViewModel vm) async {
    if (!_formKey.currentState!.validate()) return;
    await vm.submit(
      fullName: _name.text,
      email: _email.text,
      password: _password.text,
      passwordConfirmation: _password2.text,
      role: _role,
      phone: _phone.text,
    );
    if (!context.mounted) return;
    if (vm.error == null) {
      context.go('/home');
    }
  }
}
