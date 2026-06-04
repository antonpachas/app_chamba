import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/auth_repository.dart';
import '../shared/widgets/app_button.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _form  = GlobalKey<FormState>();
  final _email = TextEditingController();
  bool _loading = false;
  bool _sent    = false;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() { _loading = true; _error = null; });
    try {
      await context.read<AuthRepository>().forgotPassword(_email.text.trim());
      if (mounted) setState(() { _sent = true; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Recuperar contraseña')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(28),
          child: _sent ? _SuccessView(email: _email.text) : _FormView(
            form: _form,
            emailCtrl: _email,
            error: _error,
            loading: _loading,
            onSubmit: _submit,
          ),
        ),
      ),
    );
  }
}

class _FormView extends StatelessWidget {
  const _FormView({
    required this.form,
    required this.emailCtrl,
    required this.error,
    required this.loading,
    required this.onSubmit,
  });

  final GlobalKey<FormState> form;
  final TextEditingController emailCtrl;
  final String? error;
  final bool loading;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const SizedBox(height: 8),
      const Icon(Icons.lock_reset_rounded, size: 56, color: Color(0xFF003874)),
      const SizedBox(height: 20),
      const Text('¿Olvidaste tu contraseña?',
          style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
      const SizedBox(height: 8),
      const Text(
          'Ingresa tu correo y te enviamos un enlace para restablecerla.',
          style: TextStyle(fontSize: 14, color: Color(0xFF64748B), height: 1.5)),
      const SizedBox(height: 28),
      if (error != null)
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
            Expanded(child: Text(error!,
                style: const TextStyle(color: Color(0xFFDC2626), fontSize: 13))),
          ]),
        ),
      Form(
        key: form,
        child: Column(children: [
          TextFormField(
            controller: emailCtrl,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(
              labelText: 'Correo electrónico',
              prefixIcon: Icon(Icons.email_outlined),
            ),
            validator: (v) =>
                (v == null || !v.contains('@')) ? 'Correo inválido' : null,
          ),
          const SizedBox(height: 24),
          AppButton(label: 'Enviar enlace', onPressed: onSubmit, loading: loading),
        ]),
      ),
    ]);
  }
}

class _SuccessView extends StatelessWidget {
  const _SuccessView({required this.email});
  final String email;

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      const SizedBox(height: 40),
      const Icon(Icons.mark_email_read_outlined, size: 72, color: Color(0xFF16A34A)),
      const SizedBox(height: 24),
      const Text('¡Correo enviado!',
          style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
      const SizedBox(height: 12),
      Text('Enviamos un enlace a $email.\nRevisá tu bandeja de entrada.',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 14, color: Color(0xFF64748B), height: 1.6)),
      const SizedBox(height: 36),
      FilledButton(
        onPressed: () => context.go('/login'),
        style: FilledButton.styleFrom(minimumSize: const Size(200, 48)),
        child: const Text('Volver al login'),
      ),
    ]);
  }
}
