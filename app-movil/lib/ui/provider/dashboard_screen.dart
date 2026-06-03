import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/provider_repository.dart';
import '../shared/widgets/error_view.dart';

class ProviderDashboardScreen extends StatefulWidget {
  const ProviderDashboardScreen({super.key});

  @override
  State<ProviderDashboardScreen> createState() => _ProviderDashboardScreenState();
}

class _ProviderDashboardScreenState extends State<ProviderDashboardScreen> {
  Map<String, dynamic>? _data;
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
      _data = await context.read<ProviderRepository>().dashboard();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);

    final d = _data ?? {};
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text('Mi panel', style: TextStyle(fontSize: 22,
              fontWeight: FontWeight.w900, color: Color(0xFF0B1C30))),
          const SizedBox(height: 20),
          Row(children: [
            _Stat(label: 'Anuncios activos',
                value: '${d['active_listings'] ?? 0}',
                icon: Icons.campaign_outlined, color: const Color(0xFF003874)),
            const SizedBox(width: 12),
            _Stat(label: 'Solicitudes nuevas',
                value: '${d['new_requests'] ?? 0}',
                icon: Icons.inbox_outlined, color: const Color(0xFF16A34A)),
          ]),
          const SizedBox(height: 12),
          Row(children: [
            _Stat(label: 'Total solicitudes',
                value: '${d['total_requests'] ?? 0}',
                icon: Icons.people_outline, color: const Color(0xFF0284C7)),
            const SizedBox(width: 12),
            _Stat(label: 'Valoración',
                value: d['avg_rating'] != null
                    ? '${(d['avg_rating'] as num).toStringAsFixed(1)} ★'
                    : '—',
                icon: Icons.star_outline_rounded, color: const Color(0xFFF59E0B)),
          ]),
          const SizedBox(height: 28),
          const Text('Accesos rápidos', style: TextStyle(fontSize: 16,
              fontWeight: FontWeight.w800, color: Color(0xFF0B1C30))),
          const SizedBox(height: 12),
          _QuickLink(icon: Icons.campaign_outlined,
              label: 'Mis anuncios', subtitle: 'Gestiona tus fichas'),
          const SizedBox(height: 10),
          _QuickLink(icon: Icons.inbox_outlined,
              label: 'Solicitudes recibidas', subtitle: 'Clientes que te contactaron'),
        ],
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value,
      required this.icon, required this.color});
  final String label, value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(height: 8),
        Text(value, style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900,
            color: color)),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
      ]),
    ),
  );
}

class _QuickLink extends StatelessWidget {
  const _QuickLink({required this.icon, required this.label, required this.subtitle});
  final IconData icon;
  final String label, subtitle;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      border: Border.all(color: const Color(0xFFE2E8F0)),
    ),
    child: Row(children: [
      Container(width: 44, height: 44,
        decoration: BoxDecoration(color: const Color(0xFFDBEAFF),
            borderRadius: BorderRadius.circular(10)),
        child: Icon(icon, color: const Color(0xFF003874))),
      const SizedBox(width: 14),
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
        Text(subtitle, style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
      ]),
      const Spacer(),
      const Icon(Icons.chevron_right, color: Color(0xFFCBD5E1)),
    ]),
  );
}
