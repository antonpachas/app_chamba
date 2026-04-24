import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class CuentaTab extends StatelessWidget {
  const CuentaTab({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionViewModel>();
    final u = session.user!;
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    final roleLabel = u.isProveedor ? 'Proveedor' : 'Cliente';

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 36,
                  backgroundColor: scheme.primaryContainer,
                  child: Text(
                    u.fullName.trim().isNotEmpty ? u.fullName.trim().substring(0, 1).toUpperCase() : '?',
                    style: textTheme.headlineSmall?.copyWith(
                      color: scheme.onPrimaryContainer,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        u.fullName,
                        style: textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 4),
                      Wrap(
                        spacing: 8,
                        runSpacing: 6,
                        children: [
                          Chip(
                            avatar: Icon(
                              u.isProveedor ? Icons.handyman_rounded : Icons.person_search_rounded,
                              size: 18,
                              color: scheme.onSecondaryContainer,
                            ),
                            label: Text(roleLabel),
                            visualDensity: VisualDensity.compact,
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                          ),
                          Chip(
                            label: Text(u.status),
                            visualDensity: VisualDensity.compact,
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        Card(
          child: Column(
            children: [
              ListTile(
                leading: Icon(Icons.email_outlined, color: scheme.primary),
                title: const Text('Correo'),
                subtitle: Text(u.email, style: const TextStyle(fontWeight: FontWeight.w600)),
              ),
              const Divider(height: 1),
              if (u.phone != null && u.phone!.isNotEmpty)
                ListTile(
                  leading: Icon(Icons.phone_iphone_rounded, color: scheme.primary),
                  title: const Text('Teléfono'),
                  subtitle: Text(u.phone!, style: const TextStyle(fontWeight: FontWeight.w600)),
                ),
              if (u.providerProfile != null) ...[
                const Divider(height: 1),
                ListTile(
                  leading: Icon(Icons.store_mall_directory_outlined, color: scheme.primary),
                  title: const Text('Negocio'),
                  subtitle: Text(
                    u.providerProfile!.businessName ?? 'Sin nombre comercial',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 20),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            foregroundColor: scheme.error,
            side: BorderSide(color: scheme.error.withValues(alpha: 0.45)),
          ),
          onPressed: () async {
            await session.logout();
            if (context.mounted) context.go('/login');
          },
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Cerrar sesión'),
        ),
      ],
    );
  }
}
