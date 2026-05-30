import 'package:chamba_app/data/api/admin_api.dart';
import 'package:chamba_app/presentation/view_models/admin_vm.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class AdminPanelScreen extends StatelessWidget {
  const AdminPanelScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => AdminViewModel(api: ctx.read<AdminApi>())..loadDashboard(),
      child: const _AdminBody(),
    );
  }
}

class _AdminBody extends StatefulWidget {
  const _AdminBody();

  @override
  State<_AdminBody> createState() => _AdminBodyState();
}

class _AdminBodyState extends State<_AdminBody> {
  int _tab = 0;

  static const _tabs = [
    (Icons.dashboard_rounded, 'Panel'),
    (Icons.list_alt_rounded, 'Anuncios'),
    (Icons.group_rounded, 'Usuarios'),
    (Icons.bar_chart_rounded, 'Reportes'),
    (Icons.support_agent_rounded, 'Soporte'),
  ];

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Row(children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: scheme.primaryContainer,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(Icons.admin_panel_settings_rounded, color: scheme.onPrimaryContainer, size: 20),
          ),
          const SizedBox(width: 10),
          const Text('Panel Admin'),
        ]),
      ),
      body: IndexedStack(
        index: _tab,
        children: const [
          _DashboardSection(),
          _ListingsSection(),
          _UsersSection(),
          _ReportsSection(),
          _SupportSection(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) {
          setState(() => _tab = i);
          final vm = context.read<AdminViewModel>();
          switch (i) {
            case 0: if (vm.dashboardData == null) vm.loadDashboard();
            case 1: if (vm.listings.isEmpty) vm.loadListings();
            case 2: if (vm.users.isEmpty) vm.loadUsers();
            case 3: if (vm.reportsData == null) vm.loadReports();
            case 4: if (vm.tickets.isEmpty) vm.loadTickets();
          }
        },
        destinations: [
          for (final t in _tabs)
            NavigationDestination(icon: Icon(t.$1), label: t.$2),
        ],
      ),
    );
  }
}

// ── Panel / Dashboard ────────────────────────────────────────────────────────

class _DashboardSection extends StatelessWidget {
  const _DashboardSection();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<AdminViewModel>();
    final d = vm.dashboardData;
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    if (vm.dashboardLoading) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: vm.loadDashboard,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('Resumen general', style: textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900)),
          const SizedBox(height: 16),
          if (d != null) ...[
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              childAspectRatio: 1.6,
              children: [
                _StatCard(
                  label: 'Usuarios',
                  value: d['total_users']?.toString() ?? d['users']?.toString() ?? '—',
                  icon: Icons.group_rounded,
                  color: scheme.primary,
                ),
                _StatCard(
                  label: 'Negocios',
                  value: d['total_providers']?.toString() ?? d['providers']?.toString() ?? '—',
                  icon: Icons.storefront_rounded,
                  color: Colors.teal,
                ),
                _StatCard(
                  label: 'Anuncios',
                  value: d['total_listings']?.toString() ?? d['listings']?.toString() ?? '—',
                  icon: Icons.list_alt_rounded,
                  color: Colors.indigo,
                ),
                _StatCard(
                  label: 'Solicitudes hoy',
                  value: d['requests_today']?.toString() ?? d['requests']?.toString() ?? '—',
                  icon: Icons.send_rounded,
                  color: Colors.orange,
                ),
              ],
            ),
          ] else
            Center(child: Text('Sin datos', style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant))),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.label, required this.value, required this.icon, required this.color});
  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, color: color, size: 28),
          const Spacer(),
          Text(value, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900, color: color)),
          Text(label, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
        ]),
      ),
    );
  }
}

// ── Anuncios ─────────────────────────────────────────────────────────────────

class _ListingsSection extends StatelessWidget {
  const _ListingsSection();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<AdminViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return Column(children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
        child: Row(children: [
          for (final f in [('all', 'Todos'), ('active', 'Activos'), ('hidden', 'Ocultos')])
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilterChip(
                label: Text(f.$2),
                selected: vm.listingsFilter == f.$1,
                onSelected: (_) {
                  vm.listingsFilter = f.$1;
                  vm.loadListings();
                },
              ),
            ),
        ]),
      ),
      if (vm.listingsLoading)
        const Expanded(child: Center(child: CircularProgressIndicator()))
      else
        Expanded(
          child: RefreshIndicator(
            onRefresh: vm.loadListings,
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
              itemCount: vm.listings.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (_, i) {
                final l = vm.listings[i] as Map;
                final id = l['id'] as int? ?? 0;
                final title = l['title']?.toString() ?? '—';
                final status = l['status']?.toString() ?? '';
                final provider = l['provider_name']?.toString() ?? '';
                return ListTile(
                  leading: CircleAvatar(
                    backgroundColor: status == 'active'
                        ? Colors.green.shade100
                        : scheme.surfaceContainerHighest,
                    child: Icon(
                      status == 'active' ? Icons.visibility_rounded : Icons.visibility_off_rounded,
                      color: status == 'active' ? Colors.green.shade700 : scheme.onSurfaceVariant,
                      size: 18,
                    ),
                  ),
                  title: Text(title, style: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
                  subtitle: Text(provider, style: textTheme.bodySmall),
                  trailing: status == 'active'
                      ? TextButton(
                          onPressed: () => vm.hideListing(id),
                          style: TextButton.styleFrom(foregroundColor: scheme.error),
                          child: const Text('Ocultar'),
                        )
                      : TextButton(
                          onPressed: () => vm.restoreListing(id),
                          child: const Text('Restaurar'),
                        ),
                );
              },
            ),
          ),
        ),
    ]);
  }
}

// ── Usuarios ─────────────────────────────────────────────────────────────────

class _UsersSection extends StatelessWidget {
  const _UsersSection();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<AdminViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return Column(children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
        child: Row(children: [
          for (final r in [('all', 'Todos'), ('cliente', 'Clientes'), ('proveedor', 'Negocios')])
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilterChip(
                label: Text(r.$2),
                selected: vm.userRole == r.$1,
                onSelected: (_) {
                  vm.userRole = r.$1;
                  vm.loadUsers();
                },
              ),
            ),
        ]),
      ),
      if (vm.usersLoading)
        const Expanded(child: Center(child: CircularProgressIndicator()))
      else
        Expanded(
          child: RefreshIndicator(
            onRefresh: vm.loadUsers,
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
              itemCount: vm.users.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (_, i) {
                final u = vm.users[i] as Map;
                final id = u['id'] as int? ?? 0;
                final name = u['full_name']?.toString() ?? u['name']?.toString() ?? '—';
                final email = u['email']?.toString() ?? '';
                final role = u['role']?.toString() ?? '';
                final status = u['status']?.toString() ?? 'active';
                final suspended = status == 'suspended';

                return ListTile(
                  leading: CircleAvatar(
                    backgroundColor: suspended ? scheme.errorContainer : scheme.primaryContainer,
                    child: Text(
                      name.isNotEmpty ? name[0].toUpperCase() : '?',
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        color: suspended ? scheme.onErrorContainer : scheme.onPrimaryContainer,
                      ),
                    ),
                  ),
                  title: Text(name, style: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700)),
                  subtitle: Text('$email · $role', style: textTheme.bodySmall),
                  trailing: suspended
                      ? TextButton(onPressed: () => vm.activateUser(id), child: const Text('Activar'))
                      : TextButton(
                          onPressed: () => _suspendDialog(context, vm, id, name),
                          style: TextButton.styleFrom(foregroundColor: scheme.error),
                          child: const Text('Suspender'),
                        ),
                );
              },
            ),
          ),
        ),
    ]);
  }

  void _suspendDialog(BuildContext context, AdminViewModel vm, int id, String name) {
    final ctrl = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Suspender a $name'),
        content: TextField(
          controller: ctrl,
          decoration: const InputDecoration(labelText: 'Motivo'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancelar')),
          FilledButton(
            onPressed: () {
              Navigator.pop(ctx);
              vm.suspendUser(id, ctrl.text.trim().isEmpty ? 'Suspendido por administrador' : ctrl.text.trim());
            },
            child: const Text('Suspender'),
          ),
        ],
      ),
    );
  }
}

// ── Reportes ──────────────────────────────────────────────────────────────────

class _ReportsSection extends StatelessWidget {
  const _ReportsSection();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<AdminViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    if (vm.reportsLoading) return const Center(child: CircularProgressIndicator());

    final r = vm.reportsData;
    if (r == null) {
      return Center(
        child: FilledButton.icon(
          onPressed: vm.loadReports,
          icon: const Icon(Icons.refresh_rounded),
          label: const Text('Cargar reportes'),
        ),
      );
    }

    final cats = r['top_categories'] as List? ?? [];
    final queries = r['top_queries'] as List? ?? [];

    return RefreshIndicator(
      onRefresh: vm.loadReports,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('Del último mes', style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant)),
          const SizedBox(height: 16),
          Text('Categorías más buscadas', style: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          if (cats.isEmpty)
            Text('Sin datos', style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant))
          else
            ...cats.asMap().entries.map((e) {
              final item = e.value as Map;
              final max = (cats.first as Map)['searches'] as num? ?? 1;
              final count = item['searches'] as num? ?? 0;
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    SizedBox(width: 22, child: Text('${e.key + 1}', style: const TextStyle(fontWeight: FontWeight.w600))),
                    Expanded(child: Text(item['category_name']?.toString() ?? '—')),
                    Text('$count', style: const TextStyle(fontWeight: FontWeight.w800)),
                  ]),
                  const SizedBox(height: 4),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: max > 0 ? count / max : 0,
                      minHeight: 6,
                      backgroundColor: scheme.surfaceContainerHighest,
                    ),
                  ),
                ]),
              );
            }),
          const SizedBox(height: 24),
          Text('Términos más buscados', style: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          if (queries.isEmpty)
            Text('Sin datos', style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant))
          else
            ...queries.take(15).map((q) {
              final item = q as Map;
              return ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text(item['query']?.toString() ?? '—'),
                trailing: Text('${item['searches']}', style: const TextStyle(fontWeight: FontWeight.w800)),
              );
            }),
        ],
      ),
    );
  }
}

// ── Soporte ───────────────────────────────────────────────────────────────────

class _SupportSection extends StatelessWidget {
  const _SupportSection();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<AdminViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return Column(children: [
      SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
        child: Row(children: [
          for (final s in [('all', 'Todos'), ('abierto', 'Abiertos'), ('resuelto', 'Resueltos')])
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: ActionChip(
                label: Text(s.$2),
                onPressed: () => vm.loadTickets(status: s.$1),
              ),
            ),
        ]),
      ),
      if (vm.ticketsLoading)
        const Expanded(child: Center(child: CircularProgressIndicator()))
      else
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => vm.loadTickets(),
            child: vm.tickets.isEmpty
                ? Center(
                    child: Text('Sin tickets', style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant)))
                : ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                    itemCount: vm.tickets.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, i) {
                      final t = vm.tickets[i];
                      final subject = t['subject']?.toString() ?? '—';
                      final status = t['status']?.toString() ?? '';
                      final user = (t['user'] as Map?)?['full_name']?.toString() ?? '—';
                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor: status == 'abierto' ? scheme.errorContainer : scheme.surfaceContainerHighest,
                          child: Icon(
                            status == 'abierto' ? Icons.support_agent_rounded : Icons.check_circle_outline_rounded,
                            size: 18,
                            color: status == 'abierto' ? scheme.onErrorContainer : scheme.onSurfaceVariant,
                          ),
                        ),
                        title: Text(subject, maxLines: 1, overflow: TextOverflow.ellipsis,
                            style: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700)),
                        subtitle: Text(user),
                        trailing: Chip(
                          label: Text(status, style: const TextStyle(fontSize: 11)),
                          padding: EdgeInsets.zero,
                          visualDensity: VisualDensity.compact,
                        ),
                      );
                    },
                  ),
          ),
        ),
    ]);
  }
}
