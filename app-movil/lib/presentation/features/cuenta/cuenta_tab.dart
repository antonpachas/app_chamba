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
    final initial = u.fullName.trim().isNotEmpty ? u.fullName.trim().substring(0, 1).toUpperCase() : '?';

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 28),
      children: [
        Card(
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      scheme.primaryContainer.withValues(alpha: 0.95),
                      scheme.tertiaryContainer.withValues(alpha: 0.45),
                    ],
                  ),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 38,
                      backgroundColor: scheme.surface,
                      child: Text(
                        initial,
                        style: textTheme.headlineMedium?.copyWith(
                          color: scheme.primary,
                          fontWeight: FontWeight.w900,
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
                            style: textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w900,
                              letterSpacing: -0.3,
                              height: 1.15,
                            ),
                          ),
                          const SizedBox(height: 8),
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
            ],
          ),
        ),
        const SizedBox(height: 14),
        Text(
          'Tus datos',
          style: textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w800,
            color: scheme.onSurfaceVariant,
            letterSpacing: 0.2,
          ),
        ),
        const SizedBox(height: 8),
        Card(
          child: Column(
            children: [
              ListTile(
                leading: CircleAvatar(
                  backgroundColor: scheme.primaryContainer.withValues(alpha: 0.6),
                  child: Icon(Icons.email_outlined, color: scheme.onPrimaryContainer),
                ),
                title: const Text('Correo'),
                subtitle: Text(u.email, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
              ),
              const Divider(height: 1),
              if (u.phone != null && u.phone!.isNotEmpty) ...[
                ListTile(
                  leading: CircleAvatar(
                    backgroundColor: scheme.secondaryContainer.withValues(alpha: 0.7),
                    child: Icon(Icons.phone_iphone_rounded, color: scheme.onSecondaryContainer),
                  ),
                  title: const Text('Teléfono'),
                  subtitle: Text(u.phone!, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                ),
                if (u.providerProfile != null) const Divider(height: 1),
              ],
              if (u.providerProfile != null)
                ListTile(
                  leading: CircleAvatar(
                    backgroundColor: scheme.tertiaryContainer.withValues(alpha: 0.75),
                    child: Icon(Icons.store_mall_directory_outlined, color: scheme.onTertiaryContainer),
                  ),
                  title: const Text('Negocio'),
                  subtitle: Text(
                    u.providerProfile!.businessName ?? 'Sin nombre comercial',
                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 28),
        FilledButton.tonalIcon(
          style: FilledButton.styleFrom(
            minimumSize: const Size.fromHeight(52),
            foregroundColor: scheme.error,
            backgroundColor: scheme.errorContainer.withValues(alpha: 0.35),
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
