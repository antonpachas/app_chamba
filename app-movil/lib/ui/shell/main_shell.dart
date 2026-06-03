import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/session_provider.dart';
import '../search/search_screen.dart';
import '../client/requests_screen.dart';
import '../provider/dashboard_screen.dart';
import '../account/account_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionProvider>();
    final isProveedor = session.user?.isProveedor ?? false;

    final tabs = isProveedor ? _providerTabs() : _clienteTabs(session.isGuest);

    return Scaffold(
      body: IndexedStack(index: _index, children: tabs.map((t) => t.screen).toList()),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: tabs.map((t) => NavigationDestination(
          icon: Icon(t.icon),
          label: t.label,
        )).toList(),
      ),
    );
  }

  List<_Tab> _clienteTabs(bool isGuest) => [
    _Tab(Icons.search_rounded,      'Buscar',     const SearchScreen()),
    if (!isGuest)
      _Tab(Icons.inbox_outlined,    'Solicitudes', const ClientRequestsScreen()),
    _Tab(Icons.person_outline,      'Cuenta',     const AccountScreen()),
  ];

  List<_Tab> _providerTabs() => [
    _Tab(Icons.dashboard_outlined,  'Panel',      const ProviderDashboardScreen()),
    _Tab(Icons.campaign_outlined,   'Anuncios',   const _PlaceholderTab('Mis anuncios')),
    _Tab(Icons.inbox_outlined,      'Contactos',  const _PlaceholderTab('Solicitudes')),
    _Tab(Icons.person_outline,      'Cuenta',     const AccountScreen()),
  ];
}

class _Tab {
  const _Tab(this.icon, this.label, this.screen);
  final IconData icon;
  final String label;
  final Widget screen;
}

class _PlaceholderTab extends StatelessWidget {
  const _PlaceholderTab(this.title);
  final String title;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(title)),
    body: Center(child: Text(title,
        style: const TextStyle(fontSize: 18, color: Color(0xFF64748B)))),
  );
}
