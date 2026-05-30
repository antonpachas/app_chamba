import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:chamba_app/presentation/features/cliente/buscar_tab.dart';
import 'package:chamba_app/presentation/features/cliente/favoritos_tab.dart';
import 'package:chamba_app/presentation/features/cliente/solicitudes_tab.dart';
import 'package:chamba_app/presentation/features/cuenta/cuenta_tab.dart';
import 'package:chamba_app/presentation/features/cuenta/guest_cuenta_tab.dart';
import 'package:chamba_app/presentation/features/proveedor/proveedor_anuncios_tab.dart';
import 'package:chamba_app/presentation/features/proveedor/proveedor_inicio_tab.dart';
import 'package:chamba_app/presentation/features/proveedor/proveedor_solicitudes_tab.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
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

    late final List<_TabSpec> tabs;

    if (session.isGuest) {
      tabs = [
        const _TabSpec('Buscar', Icons.search_rounded, BuscarTab()),
        const _TabSpec('Cuenta', Icons.person_rounded, GuestCuentaTab()),
      ];
    } else {
      final user = session.user!;
      if (user.isCliente) {
        tabs = [
          const _TabSpec('Buscar', Icons.search_rounded, BuscarTab()),
          const _TabSpec('Favoritos', Icons.favorite_rounded, FavoritosTab()),
          const _TabSpec('Solicitudes', Icons.send_rounded, SolicitudesTab()),
          const _TabSpec('Cuenta', Icons.person_rounded, CuentaTab()),
        ];
      } else {
        tabs = [
          const _TabSpec('Inicio', Icons.dashboard_customize_rounded, ProveedorInicioTab()),
          const _TabSpec('Anuncios', Icons.list_alt_rounded, ProveedorAnunciosTab()),
          const _TabSpec('Contactos', Icons.inbox_rounded, ProveedorSolicitudesTab()),
          const _TabSpec('Cuenta', Icons.person_rounded, CuentaTab()),
        ];
      }
    }

    final safeIndex = _index.clamp(0, tabs.length - 1);
    final isSearchTab = safeIndex == 0 && (session.isGuest || (session.user?.isCliente ?? false));

    // Cuando no hay AppBar (tab de búsqueda), el Scaffold ocupa toda la pantalla.
    // backgroundColor = primary → el área del status bar se ve azul.
    // SafeArea(top: true) empuja el contenido por debajo del status bar.
    // Cuando SÍ hay AppBar, Flutter elimina el padding de MediaQuery del cuerpo,
    // por lo que SafeArea(top: true) no añade padding redundante.
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: Colors.white,
        systemNavigationBarIconBrightness: Brightness.dark,
      ),
      child: Scaffold(
        backgroundColor: isSearchTab ? AppTheme.primary : AppTheme.background,
        appBar: isSearchTab ? null : _buildAppBar(context, session, tabs[safeIndex].label),
        body: SafeArea(
          top: true,
          bottom: false,
          child: ColoredBox(
            color: AppTheme.background,
            child: IndexedStack(
              index: safeIndex,
              children: [for (final t in tabs) t.child],
            ),
          ),
        ),
        bottomNavigationBar: _buildNavBar(tabs, safeIndex),
      ),
    );
  }

  PreferredSizeWidget _buildAppBar(BuildContext ctx, SessionViewModel session, String tabLabel) {
    return AppBar(
      backgroundColor: AppTheme.primary,
      elevation: 0,
      titleSpacing: 16,
      title: Row(children: [
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10),
            boxShadow: [
              BoxShadow(color: Colors.black.withValues(alpha: 0.18), blurRadius: 8, offset: const Offset(0, 3)),
            ],
          ),
          clipBehavior: Clip.antiAlias,
          child: Image.asset('assets/images/app_icon.png', fit: BoxFit.cover),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Busca PE',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                  letterSpacing: -0.3,
                  height: 1,
                ),
              ),
              if (!session.isGuest && session.user != null)
                Text(
                  session.user!.fullName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Colors.white70,
                    fontWeight: FontWeight.w500,
                  ),
                ),
            ],
          ),
        ),
        // Botón admin
        if (!session.isGuest && (session.user?.isAdmin ?? false))
          IconButton(
            tooltip: 'Panel admin',
            icon: const Icon(Icons.admin_panel_settings_rounded, color: Colors.white),
            onPressed: () => ctx.push('/admin'),
          ),
        if (session.isGuest)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Row(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.visibility_outlined, size: 14, color: Colors.white70),
              SizedBox(width: 4),
              Text('Invitado', style: TextStyle(fontSize: 11, color: Colors.white70, fontWeight: FontWeight.w600)),
            ]),
          ),
      ]),
    );
  }

  Widget _buildNavBar(List<_TabSpec> tabs, int safeIndex) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: AppTheme.border)),
      ),
      child: SafeArea(
        top: false,
        child: NavigationBar(
          selectedIndex: safeIndex,
          onDestinationSelected: (i) => setState(() => _index = i),
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.transparent,
          elevation: 0,
          height: 68,
          destinations: [
            for (final t in tabs)
              NavigationDestination(
                icon: Icon(t.icon),
                selectedIcon: Icon(t.icon, color: AppTheme.primary),
                label: t.label,
              ),
          ],
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
