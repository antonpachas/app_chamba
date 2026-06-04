import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/provider_repository.dart';
import '../shared/widgets/app_button.dart';
import '../shared/widgets/error_view.dart';

class ProviderProfileScreen extends StatefulWidget {
  const ProviderProfileScreen({super.key});

  @override
  State<ProviderProfileScreen> createState() => _ProviderProfileScreenState();
}

class _ProviderProfileScreenState extends State<ProviderProfileScreen> {
  final _form = GlobalKey<FormState>();
  Map<String, dynamic>? _profile;
  bool _loading = true;
  bool _saving = false;
  String? _error;
  String? _success;
  bool _exists = false;

  late final TextEditingController _razonSocial;
  late final TextEditingController _description;
  late final TextEditingController _whatsapp;
  late final TextEditingController _phone;
  late final TextEditingController _website;

  @override
  void initState() {
    super.initState();
    _razonSocial = TextEditingController();
    _description = TextEditingController();
    _whatsapp    = TextEditingController();
    _phone       = TextEditingController();
    _website     = TextEditingController();
    _load();
  }

  @override
  void dispose() {
    _razonSocial.dispose(); _description.dispose();
    _whatsapp.dispose(); _phone.dispose(); _website.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await context.read<ProviderRepository>().profile();
      _profile = data;
      _exists = data['id'] != null;
      _razonSocial.text = data['razon_social'] as String? ?? '';
      _description.text = data['description'] as String? ?? '';
      _whatsapp.text    = data['whatsapp'] as String? ?? '';
      _phone.text       = data['contact_phone'] as String? ?? '';
      _website.text     = data['website'] as String? ?? '';
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() { _saving = true; _error = null; _success = null; });
    try {
      await context.read<ProviderRepository>().saveProfile({
        'razon_social': _razonSocial.text.trim(),
        'description':  _description.text.trim(),
        'whatsapp':     _whatsapp.text.trim(),
        'contact_phone': _phone.text.trim(),
        'website':      _website.text.trim(),
      }, create: !_exists);
      if (mounted) {
        setState(() { _success = 'Perfil actualizado.'; _exists = true; });
      }
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Perfil de negocio')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null && _profile == null
              ? ErrorView(message: _error!, onRetry: _load)
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: Form(
                    key: _form,
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      if (_error != null) _Banner(text: _error!, isError: true),
                      if (_success != null) _Banner(text: _success!, isError: false),

                      const _SectionLabel('Información del negocio'),
                      const SizedBox(height: 12),

                      TextFormField(
                        controller: _razonSocial,
                        textCapitalization: TextCapitalization.words,
                        decoration: const InputDecoration(
                          labelText: 'Nombre o razón social *',
                          prefixIcon: Icon(Icons.storefront_outlined),
                        ),
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Campo requerido' : null,
                      ),
                      const SizedBox(height: 16),

                      TextFormField(
                        controller: _description,
                        maxLines: 4,
                        textCapitalization: TextCapitalization.sentences,
                        decoration: const InputDecoration(
                          labelText: 'Descripción del negocio',
                          alignLabelWithHint: true,
                          prefixIcon: Padding(
                            padding: EdgeInsets.only(bottom: 60),
                            child: Icon(Icons.description_outlined),
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      const _SectionLabel('Datos de contacto'),
                      const SizedBox(height: 12),

                      TextFormField(
                        controller: _whatsapp,
                        keyboardType: TextInputType.phone,
                        decoration: const InputDecoration(
                          labelText: 'WhatsApp (con código de país)',
                          prefixIcon: Icon(Icons.chat_outlined),
                          hintText: '51912345678',
                        ),
                      ),
                      const SizedBox(height: 16),

                      TextFormField(
                        controller: _phone,
                        keyboardType: TextInputType.phone,
                        decoration: const InputDecoration(
                          labelText: 'Teléfono de contacto',
                          prefixIcon: Icon(Icons.phone_outlined),
                        ),
                      ),
                      const SizedBox(height: 16),

                      TextFormField(
                        controller: _website,
                        keyboardType: TextInputType.url,
                        decoration: const InputDecoration(
                          labelText: 'Sitio web',
                          prefixIcon: Icon(Icons.language_outlined),
                          hintText: 'https://...',
                        ),
                      ),
                      const SizedBox(height: 32),

                      AppButton(
                        label: _exists ? 'Guardar cambios' : 'Crear perfil de negocio',
                        onPressed: _save,
                        loading: _saving,
                      ),
                    ]),
                  ),
                ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Text(text,
      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800,
          color: Color(0xFF64748B)));
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
