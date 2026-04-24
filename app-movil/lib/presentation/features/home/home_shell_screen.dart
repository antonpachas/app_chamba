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

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: scheme.primaryContainer,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(Icons.handyman_rounded, color: scheme.onPrimaryContainer, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(titleLine),
                  Text(
                    subtitleLine,
                    style: Theme.of(context).textTheme.labelMedium?.copyWith(
                          color: scheme.onSurfaceVariant,
                          fontWeight: FontWeight.w500,
                        ),
                  ),
                ],
              ),
            ),
            if (session.isGuest)
              Padding(
                padding: const EdgeInsets.only(left: 4),
                child: Icon(Icons.visibility_outlined, size: 22, color: scheme.tertiary),
              ),
          ],
        ),
      ),
      body: IndexedStack(
        index: _index,
        children: [for (final t in tabs) t.child],
      ),
      bottomNavigationBar: NavigationBar(
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
    );
  }
}

class _TabSpec {
  const _TabSpec(this.label, this.icon, this.child);

  final String label;
  final IconData icon;
  final Widget child;
}
