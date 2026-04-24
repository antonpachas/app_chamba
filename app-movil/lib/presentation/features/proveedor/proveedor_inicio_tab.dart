import 'package:chamba_app/data/api/provider_api.dart';
import 'package:chamba_app/presentation/common/widgets/error_banner.dart';
import 'package:chamba_app/presentation/features/proveedor/dashboard_tab_view_model.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class ProveedorInicioTab extends StatelessWidget {
  const ProveedorInicioTab({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => DashboardTabViewModel(providerApi: ctx.read<ProviderApi>())..load(),
      child: const _Body(),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<DashboardTabViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    if (vm.loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (vm.error != null) {
      return ListView(
        padding: const EdgeInsets.all(20),
        children: [
          ErrorBanner(message: vm.error!),
          const SizedBox(height: 16),
          Text(
            'Si aún no creaste tu perfil de proveedor, hazlo desde la API o espera a la siguiente versión de la app.',
            style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant, height: 1.4),
          ),
        ],
      );
    }

    final d = vm.data;
    if (d == null) {
      return const Center(child: Text('Sin datos'));
    }

    final stats = <_Stat>[
      _Stat('Servicios', d['total_services']?.toString() ?? '—', Icons.design_services_rounded, scheme.primary),
      _Stat('Activos', d['active_services']?.toString() ?? '—', Icons.check_circle_outline_rounded, scheme.tertiary),
      _Stat('Solicitudes', d['total_requests']?.toString() ?? '—', Icons.mark_chat_unread_rounded, scheme.secondary),
      _Stat('Pendientes', d['pending_requests']?.toString() ?? '—', Icons.pending_actions_rounded, scheme.secondary),
    ];

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.insights_rounded, color: scheme.primary),
                    const SizedBox(width: 8),
                    Text(
                      'Resumen de hoy',
                      style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  d['provider_name']?.toString() ?? 'Tu negocio',
                  style: textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900, height: 1.1),
                ),
                const SizedBox(height: 6),
                Text(
                  'Valoración: ${d['avg_rating'] ?? '—'} · Reseñas: ${d['total_reviews'] ?? '—'}',
                  style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        LayoutBuilder(
          builder: (context, c) {
            final w = c.maxWidth;
            final cross = w > 520 ? 4 : 2;
            return GridView.count(
              crossAxisCount: cross,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              childAspectRatio: 1.15,
              children: [
                for (final s in stats)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(s.iconData, color: s.accent, size: 26),
                          const Spacer(),
                          Text(
                            s.value,
                            style: textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            s.label,
                            style: textTheme.labelLarge?.copyWith(
                              color: scheme.onSurfaceVariant,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            );
          },
        ),
        const SizedBox(height: 8),
        Text(
          'Pronto podrás responder solicitudes y actualizar tus servicios desde aquí.',
          style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant, height: 1.4),
        ),
      ],
    );
  }
}

class _Stat {
  const _Stat(this.label, this.value, this.iconData, this.accent);

  final String label;
  final String value;
  final IconData iconData;
  final Color accent;
}
