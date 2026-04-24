import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

/// Cuenta en modo invitado (requisito típico App Store: explorar sin registrarse).
class GuestCuentaTab extends StatelessWidget {
  const GuestCuentaTab({super.key});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;
    final session = context.read<SessionViewModel>();

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.visibility_outlined, color: scheme.primary, size: 28),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Modo invitado',
                        style: textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  'Puedes explorar servicios y categorías sin crear una cuenta. '
                  'Para contactar proveedores, guardar favoritos o publicar como proveedor, '
                  'usa iniciar sesión o registro.',
                  style: textTheme.bodyMedium?.copyWith(
                    color: scheme.onSurfaceVariant,
                    height: 1.45,
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        FilledButton.icon(
          onPressed: () async {
            await session.exitGuestMode();
            if (context.mounted) context.go('/login');
          },
          icon: const Icon(Icons.login_rounded),
          label: const Text('Iniciar sesión'),
        ),
        const SizedBox(height: 10),
        OutlinedButton.icon(
          onPressed: () async {
            await session.exitGuestMode();
            if (context.mounted) context.go('/register');
          },
          icon: const Icon(Icons.person_add_alt_1_rounded),
          label: const Text('Crear cuenta'),
        ),
        const SizedBox(height: 20),
        TextButton.icon(
          onPressed: () async {
            await session.logout();
            if (context.mounted) context.go('/login');
          },
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Salir del modo invitado'),
        ),
      ],
    );
  }
}
