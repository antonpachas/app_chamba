import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/session_provider.dart';
import '../shared/widgets/app_button.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _form        = GlobalKey<FormState>();
  final _name        = TextEditingController();
  final _email       = TextEditingController();
  final _phone       = TextEditingController();
  final _razonSocial = TextEditingController();
  final _ruc         = TextEditingController();
  final _password    = TextEditingController();
  String _role    = 'cliente';
  bool _loading   = false;
  bool _obscure   = true;
  String? _error;

  bool get _isNegocio => _role == 'proveedor';

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _razonSocial.dispose();
    _ruc.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() { _loading = true; _error = null; });
    try {
      await context.read<SessionProvider>().register(
        fullName:    _name.text.trim(),
        email:       _email.text.trim(),
        password:    _password.text,
        role:        _role,
        phone:       _phone.text.trim(),
        razonSocial: _isNegocio ? _razonSocial.text.trim() : null,
        ruc:         _isNegocio ? _ruc.text.trim() : null,
      );
      if (mounted) context.go('/home');
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Crear cuenta'),
        leading: BackButton(onPressed: () => context.go('/login')),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: Form(
              key: _form,
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                // Selector de rol
                const Text('Tipo de cuenta',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700,
                        color: Color(0xFF64748B))),
                const SizedBox(height: 8),
                SegmentedButton<String>(
                  segments: const [
                    ButtonSegment(value: 'cliente',
                        icon: Icon(Icons.person_outline), label: Text('Cliente')),
                    ButtonSegment(value: 'proveedor',
                        icon: Icon(Icons.storefront_outlined), label: Text('Negocio')),
                  ],
                  selected: {_role},
                  onSelectionChanged: (s) => setState(() => _role = s.first),
                ),
                const SizedBox(height: 24),

                // Error banner
                if (_error != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 16),
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEE2E2),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(children: [
                      const Icon(Icons.error_outline, color: Color(0xFFDC2626), size: 18),
                      const SizedBox(width: 8),
                      Expanded(child: Text(_error!,
                          style: const TextStyle(color: Color(0xFFDC2626), fontSize: 13))),
                    ]),
                  ),

                // Nombre completo
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

                // Correo
                TextFormField(
                  controller: _email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Correo electrónico',
                    prefixIcon: Icon(Icons.email_outlined),
                  ),
                  validator: (v) =>
                      (v == null || !v.contains('@')) ? 'Correo inválido' : null,
                ),
                const SizedBox(height: 16),

                // Teléfono — obligatorio, solo dígitos
                TextFormField(
                  controller: _phone,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Teléfono',
                    prefixIcon: Icon(Icons.phone_outlined),
                    hintText: '987654321',
                  ),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return 'El teléfono es obligatorio';
                    if (!RegExp(r'^\d{7,15}$').hasMatch(v.trim())) {
                      return 'Solo dígitos, entre 7 y 15 números';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Campos exclusivos de negocio
                if (_isNegocio) ...[
                  TextFormField(
                    controller: _razonSocial,
                    textCapitalization: TextCapitalization.words,
                    decoration: const InputDecoration(
                      labelText: 'Razón social',
                      prefixIcon: Icon(Icons.business_outlined),
                      hintText: 'Mi Empresa SAC',
                    ),
                    validator: (v) =>
                        (v == null || v.trim().isEmpty) ? 'La razón social es obligatoria' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _ruc,
                    keyboardType: TextInputType.number,
                    maxLength: 11,
                    decoration: const InputDecoration(
                      labelText: 'RUC',
                      prefixIcon: Icon(Icons.tag_outlined),
                      hintText: '20123456789',
                      counterText: '',
                    ),
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return 'El RUC es obligatorio';
                      if (!RegExp(r'^\d{11}$').hasMatch(v.trim())) {
                        return 'El RUC debe tener exactamente 11 dígitos';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                ],

                // Contraseña
                TextFormField(
                  controller: _password,
                  obscureText: _obscure,
                  decoration: InputDecoration(
                    labelText: 'Contraseña',
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      icon: Icon(_obscure
                          ? Icons.visibility_outlined
                          : Icons.visibility_off_outlined),
                      onPressed: () => setState(() => _obscure = !_obscure),
                    ),
                  ),
                  validator: (v) =>
                      (v == null || v.length < 8) ? 'Mínimo 8 caracteres' : null,
                ),
                const SizedBox(height: 28),

                AppButton(label: 'Crear cuenta', onPressed: _submit, loading: _loading),
                const SizedBox(height: 20),
                Center(
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    const Text('¿Ya tienes cuenta? ',
                        style: TextStyle(color: Color(0xFF64748B))),
                    GestureDetector(
                      onTap: () => context.go('/login'),
                      child: const Text('Inicia sesión',
                          style: TextStyle(color: Color(0xFF003874),
                              fontWeight: FontWeight.w700)),
                    ),
                  ]),
                ),
                const SizedBox(height: 16),
              ]),
            ),
          ),
        ),
      ),
    );
  }
}
