import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';
import '../shared/widgets/error_view.dart';

class AdminScreen extends StatefulWidget {
  const AdminScreen({super.key});

  @override
  State<AdminScreen> createState() => _AdminScreenState();
}

class _AdminScreenState extends State<AdminScreen> {
  Map<String, dynamic>? _stats;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await context.read<ApiClient>().get(Endpoints.adminDashboard);
      _stats = data as Map<String, dynamic>?;
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Admin · Panel'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(20),
                    children: [
                      const Text('Resumen del sistema',
                          style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900)),
                      const SizedBox(height: 16),
                      _StatsGrid(stats: _stats ?? {}),
                      const SizedBox(height: 24),
                      const Text('Acciones rápidas',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                      const SizedBox(height: 12),
                      _AdminAction(
                        icon: Icons.campaign_outlined,
                        label: 'Moderación de anuncios',
                        subtitle: 'Revisar y gestionar anuncios activos',
                        color: const Color(0xFF0284C7),
                        onTap: () => _openWebAction(context, 'moderación'),
                      ),
                      const SizedBox(height: 10),
                      _AdminAction(
                        icon: Icons.people_outline,
                        label: 'Usuarios',
                        subtitle: 'Suspender, activar y gestionar cuentas',
                        color: const Color(0xFF7C3AED),
                        onTap: () => _openWebAction(context, 'usuarios'),
                      ),
                      const SizedBox(height: 10),
                      _AdminAction(
                        icon: Icons.settings_outlined,
                        label: 'Configuración',
                        subtitle: 'Ajustes y parámetros del sistema',
                        color: const Color(0xFF64748B),
                        onTap: () => _openWebAction(context, 'configuración'),
                      ),
                      const SizedBox(height: 10),
                      _AdminAction(
                        icon: Icons.bar_chart_outlined,
                        label: 'Reportes',
                        subtitle: 'Estadísticas y búsquedas populares',
                        color: const Color(0xFF16A34A),
                        onTap: () => _openWebAction(context, 'reportes'),
                      ),
                    ],
                  ),
                ),
    );
  }

  void _openWebAction(BuildContext context, String section) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Abrir $section'),
        content: Text(
          'Para gestionar $section de forma completa, '
          'usa el panel web desde tu computadora.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cerrar'),
          ),
        ],
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  const _StatsGrid({required this.stats});
  final Map<String, dynamic> stats;

  @override
  Widget build(BuildContext context) {
    final items = [
      (label: 'Usuarios', key: 'total_users', icon: Icons.people_outline,
          color: const Color(0xFF7C3AED)),
      (label: 'Negocios', key: 'total_providers', icon: Icons.storefront_outlined,
          color: const Color(0xFF003874)),
      (label: 'Anuncios activos', key: 'active_listings', icon: Icons.campaign_outlined,
          color: const Color(0xFF0284C7)),
      (label: 'Solicitudes hoy', key: 'requests_today', icon: Icons.inbox_outlined,
          color: const Color(0xFF16A34A)),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.5,
      children: items.map((item) {
        final value = stats[item.key];
        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Icon(item.icon, color: item.color, size: 22),
            const Spacer(),
            Text(value?.toString() ?? '—',
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900,
                    color: item.color)),
            Text(item.label,
                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
          ]),
        );
      }).toList(),
    );
  }
}

class _AdminAction extends StatelessWidget {
  const _AdminAction({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String label, subtitle;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Card(
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(children: [
          Container(
            width: 46, height: 46,
            decoration: BoxDecoration(
              color: color.withAlpha(25),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color),
          ),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: const TextStyle(fontSize: 15,
                fontWeight: FontWeight.w700)),
            Text(subtitle, style: const TextStyle(fontSize: 12,
                color: Color(0xFF64748B))),
          ])),
          const Icon(Icons.chevron_right, color: Color(0xFFCBD5E1)),
        ]),
      ),
    ),
  );
}
