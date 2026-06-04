import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/session_provider.dart';
import '../../admin/admin_screen.dart';
import '../../cliente/requests_screen.dart';
import '../../cliente/favorites_screen.dart';
import '../../negocio/dashboard_screen.dart';
import '../../negocio/listings_screen.dart';
import '../../negocio/requests_screen.dart';
import '../buscar/search_screen.dart';
import '../cuenta/account_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;
  // Rastrea el rol anterior para detectar cambios de sesión
  String? _lastRole;

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionProvider>();
    final currentRole = session.user?.role ?? 'guest';
    final isProveedor = session.user?.isProveedor ?? false;

    // Al cambiar de rol (login, logout, cambio de tipo) resetea al tab 0
    if (_lastRole != null && _lastRole != currentRole) {
      // Scheduleamos para el siguiente frame para no mutar estado en build
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() => _index = 0);
      });
    }
    _lastRole = currentRole;

    final isAdmin = session.user?.isAdmin ?? false;

    List<_Tab> tabs;
    if (isAdmin) {
      tabs = _adminTabs();
    } else if (isProveedor) {
      tabs = _providerTabs();
    } else {
      tabs = _clienteTabs(session.isGuest);
    }

    // Clamp para evitar el assert de NavigationBar durante transiciones
    final safeIndex = _index.clamp(0, tabs.length - 1);

    return Scaffold(
      body: IndexedStack(index: safeIndex, children: tabs.map((t) => t.screen).toList()),
      bottomNavigationBar: NavigationBar(
        selectedIndex: safeIndex,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: tabs.map((t) => NavigationDestination(
          icon: Icon(t.icon),
          label: t.label,
        )).toList(),
      ),
    );
  }

  List<_Tab> _clienteTabs(bool isGuest) => [
    _Tab(Icons.search_rounded,   'Buscar',      const SearchScreen()),
    if (!isGuest) ...[
      _Tab(Icons.inbox_outlined, 'Solicitudes', const ClientRequestsScreen()),
      _Tab(Icons.favorite_border,'Favoritos',   const ClientFavoritesScreen()),
    ],
    _Tab(Icons.person_outline,   'Cuenta',      const AccountScreen()),
  ];

  List<_Tab> _providerTabs() => [
    _Tab(Icons.dashboard_outlined, 'Panel',      const ProviderDashboardScreen()),
    _Tab(Icons.campaign_outlined,  'Anuncios',   const ProviderListingsScreen()),
    _Tab(Icons.inbox_outlined,     'Contactos',  const ProviderRequestsScreen()),
    _Tab(Icons.person_outline,     'Cuenta',     const AccountScreen()),
  ];

  List<_Tab> _adminTabs() => [
    _Tab(Icons.admin_panel_settings_outlined, 'Admin',   const AdminScreen()),
    _Tab(Icons.search_rounded,                'Buscar',  const SearchScreen()),
    _Tab(Icons.person_outline,                'Cuenta',  const AccountScreen()),
  ];
}

class _Tab {
  const _Tab(this.icon, this.label, this.screen);
  final IconData icon;
  final String label;
  final Widget screen;
}
