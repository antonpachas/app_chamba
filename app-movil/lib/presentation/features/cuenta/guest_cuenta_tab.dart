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
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 28),
      children: [
        Card(
          clipBehavior: Clip.antiAlias,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 22, 20, 22),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: scheme.tertiaryContainer.withValues(alpha: 0.85),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Icon(Icons.visibility_outlined, color: scheme.onTertiaryContainer, size: 28),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Text(
                        'Modo invitado',
                        style: textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w900,
                          letterSpacing: -0.3,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _GuestBullet(
                  icon: Icons.check_circle_outline_rounded,
                  text: 'Puedes buscar servicios y ver categorías sin cuenta.',
                ),
                const SizedBox(height: 10),
                _GuestBullet(
                  icon: Icons.lock_outline_rounded,
                  text: 'Para contactar, favoritos o publicar como proveedor, crea cuenta o inicia sesión.',
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 18),
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
        Center(
          child: TextButton.icon(
            onPressed: () async {
              await session.logout();
              if (context.mounted) context.go('/login');
            },
            icon: const Icon(Icons.logout_rounded, size: 20),
            label: const Text('Salir del modo invitado'),
          ),
        ),
      ],
    );
  }
}

class _GuestBullet extends StatelessWidget {
  const _GuestBullet({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 22, color: scheme.primary),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            style: textTheme.bodyLarge?.copyWith(
              color: scheme.onSurfaceVariant,
              height: 1.45,
            ),
          ),
        ),
      ],
    );
  }
}
