import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/session_provider.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final user = context.watch<SessionProvider>().user;
    if (user == null) return const SizedBox.shrink();

    return Scaffold(
      appBar: AppBar(title: const Text('Datos personales')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          // Avatar
          Center(
            child: CircleAvatar(
              radius: 44,
              backgroundColor: const Color(0xFFDBEAFF),
              backgroundImage: user.avatarUrl != null
                  ? NetworkImage(user.avatarUrl!) : null,
              child: user.avatarUrl == null
                  ? Text(
                      user.fullName.isNotEmpty
                          ? user.fullName[0].toUpperCase() : '?',
                      style: const TextStyle(fontSize: 36,
                          fontWeight: FontWeight.w800, color: Color(0xFF003874)))
                  : null,
            ),
          ),
          const SizedBox(height: 28),
          _ReadOnlyField(label: 'Nombre completo', value: user.fullName),
          const SizedBox(height: 16),
          _ReadOnlyField(label: 'Correo electrónico', value: user.email),
          const SizedBox(height: 16),
          _ReadOnlyField(
            label: 'Teléfono',
            value: user.phone?.isNotEmpty == true ? user.phone! : 'No registrado',
          ),
          const SizedBox(height: 16),
          _ReadOnlyField(
            label: 'Tipo de cuenta',
            value: user.isProveedor ? 'Negocio' : user.isAdmin ? 'Admin' : 'Cliente',
          ),
          const SizedBox(height: 32),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF7ED),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFFED7AA)),
            ),
            child: const Row(children: [
              Icon(Icons.info_outline, color: Color(0xFFB45309), size: 18),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Para editar tus datos, ingresa desde la web en tu computadora.',
                  style: TextStyle(fontSize: 13, color: Color(0xFFB45309),
                      height: 1.4),
                ),
              ),
            ]),
          ),
        ],
      ),
    );
  }
}

class _ReadOnlyField extends StatelessWidget {
  const _ReadOnlyField({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700,
          color: Color(0xFF64748B))),
      const SizedBox(height: 6),
      Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: const Color(0xFFF8F9FF),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Text(value,
            style: const TextStyle(fontSize: 15, color: Color(0xFF0B1C30))),
      ),
    ]);
  }
}
