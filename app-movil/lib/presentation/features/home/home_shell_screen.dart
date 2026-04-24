import 'package:chamba_app/presentation/features/cliente/buscar_tab.dart';
import 'package:chamba_app/presentation/features/cuenta/cuenta_tab.dart';
import 'package:chamba_app/presentation/features/cuenta/guest_cuenta_tab.dart';
import 'package:chamba_app/presentation/features/proveedor/proveedor_inicio_tab.dart';
import 'package:chamba_app/presentation/features/shared/placeholder_tab.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class HomeShellScreen extends StatefulWidget {
  const HomeShellScreen({super.key});

  @override
  State<HomeShellScreen> createState() => _HomeShellScreenState();
}

class _HomeShellScreenState extends State<HomeShellScreen> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionViewModel>();
    final scheme = Theme.of(context).colorScheme;

    late final List<_TabSpec> tabs;
    late final String titleLine;
    late final String subtitleLine;

    if (session.isGuest) {
      titleLine = 'Explorar';
      subtitleLine = 'Modo invitado · sin cuenta';
      tabs = [
        const _TabSpec('Buscar', Icons.search_rounded, BuscarTab()),
        const _TabSpec(
          'Actividad',
          Icons.event_note_rounded,
          PlaceholderTab(
            title: 'Actividad',
            message: 'Inicia sesión para ver solicitudes, favoritos y reseñas.',
            icon: Icons.lock_outline_rounded,
          ),
        ),
        const _TabSpec('Cuenta', Icons.person_rounded, GuestCuentaTab()),
      ];
    } else {
      final user = session.user!;
      final isCliente = user.isCliente;
      titleLine = isCliente ? 'Explorar' : 'Tu negocio';
      subtitleLine = user.fullName;
      tabs = isCliente
          ? [
              const _TabSpec('Buscar', Icons.search_rounded, BuscarTab()),
              const _TabSpec(
                'Actividad',
                Icons.event_note_rounded,
                PlaceholderTab(
                  title: 'Tu actividad',
                  message: 'Aquí verás solicitudes enviadas, favoritos y reseñas cuando estén listas.',
                  icon: Icons.timeline_rounded,
                ),
              ),
              const _TabSpec('Cuenta', Icons.person_rounded, CuentaTab()),
            ]
          : [
              const _TabSpec('Inicio', Icons.dashboard_customize_rounded, ProveedorInicioTab()),
              const _TabSpec(
                'Servicios',
                Icons.design_services_rounded,
                PlaceholderTab(
                  title: 'Mis servicios',
                  message: 'Publica, edita y activa tus anuncios desde aquí en la siguiente versión.',
                  icon: Icons.construction_rounded,
                ),
              ),
              const _TabSpec('Cuenta', Icons.person_rounded, CuentaTab()),
            ];
    }

    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: scheme.primaryContainer.withValues(alpha: 0.9),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: scheme.outlineVariant.withValues(alpha: 0.35)),
              ),
              child: Icon(Icons.handyman_rounded, color: scheme.onPrimaryContainer, size: 24),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    titleLine,
                    style: textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                      letterSpacing: -0.4,
                      height: 1.1,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitleLine,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            if (session.isGuest)
              Tooltip(
                message: 'Modo invitado',
                child: Padding(
                  padding: const EdgeInsets.only(left: 4),
                  child: Icon(Icons.visibility_outlined, size: 24, color: scheme.tertiary),
                ),
              ),
          ],
        ),
      ),
      body: IndexedStack(
        index: _index,
        children: [for (final t in tabs) t.child],
      ),
      bottomNavigationBar: Material(
        elevation: 8,
        shadowColor: scheme.shadow.withValues(alpha: 0.12),
        color: scheme.surface,
        child: SafeArea(
          top: false,
          child: NavigationBar(
            selectedIndex: _index,
            onDestinationSelected: (i) => setState(() => _index = i),
            destinations: [
              for (final t in tabs)
                NavigationDestination(
                  icon: Icon(t.icon),
                  selectedIcon: Icon(t.icon),
                  label: t.label,
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TabSpec {
  const _TabSpec(this.label, this.icon, this.child);

  final String label;
  final IconData icon;
  final Widget child;
}
