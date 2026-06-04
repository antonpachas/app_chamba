import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';
import '../shared/widgets/error_view.dart';

class AdminListingsScreen extends StatefulWidget {
  const AdminListingsScreen({super.key});

  @override
  State<AdminListingsScreen> createState() => _AdminListingsScreenState();
}

class _AdminListingsScreenState extends State<AdminListingsScreen> {
  List<Map<String, dynamic>> _listings = [];
  bool _loading = true;
  String? _error;
  int? _actingId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    final api = context.read<ApiClient>();
    try {
      final data = await api.get(Endpoints.adminListings);
      _listings = ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _hide(int id, String title) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Ocultar anuncio'),
        content: Text('¿Ocultar "$title"? Ya no será visible al público.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar')),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: const Color(0xFFDC2626)),
            child: const Text('Ocultar'),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    _act(id, () => context.read<ApiClient>().post(Endpoints.adminListingHide(id)));
  }

  Future<void> _act(int id, Future<dynamic> Function() action) async {
    setState(() => _actingId = id);
    try {
      await action();
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _actingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Anuncios'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _listings.isEmpty
                  ? const Center(child: Text('No hay anuncios.'))
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: _listings.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (_, i) {
                          final l = _listings[i];
                          final id = (l['id'] as num?)?.toInt() ?? 0;
                          return _ListingTile(
                            listing: l,
                            acting: _actingId == id,
                            onHide: () => _hide(id, l['title'] as String? ?? ''),
                          );
                        },
                      ),
                    ),
    );
  }
}

class _ListingTile extends StatelessWidget {
  const _ListingTile({
    required this.listing,
    required this.acting,
    required this.onHide,
  });

  final Map<String, dynamic> listing;
  final bool acting;
  final VoidCallback onHide;

  @override
  Widget build(BuildContext context) {
    final title    = listing['title'] as String? ?? '—';
    final provider = listing['provider_name'] as String?
        ?? (listing['provider'] as Map?)?['razon_social'] as String?
        ?? '';
    final category = listing['category_name'] as String?
        ?? (listing['category'] as Map?)?['name'] as String? ?? '';
    final isActive = listing['is_active'] == true;
    final isHidden = listing['hidden_at'] != null;

    Color statusColor = isHidden
        ? const Color(0xFFDC2626)
        : isActive
            ? const Color(0xFF16A34A)
            : const Color(0xFF64748B);
    String statusLabel = isHidden ? 'Oculto' : isActive ? 'Activo' : 'Pausado';

    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700,
          fontSize: 14), maxLines: 1, overflow: TextOverflow.ellipsis),
      subtitle: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (provider.isNotEmpty)
          Text(provider, style: const TextStyle(fontSize: 12,
              color: Color(0xFF64748B))),
        const SizedBox(height: 4),
        Row(children: [
          if (category.isNotEmpty) ...[
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: const Color(0xFFE8EEF6),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(category, style: const TextStyle(fontSize: 11,
                  color: Color(0xFF003874), fontWeight: FontWeight.w600)),
            ),
            const SizedBox(width: 6),
          ],
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              color: statusColor.withAlpha(25),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(statusLabel, style: TextStyle(fontSize: 11,
                color: statusColor, fontWeight: FontWeight.w700)),
          ),
        ]),
      ]),
      trailing: acting
          ? const SizedBox(width: 24, height: 24,
              child: CircularProgressIndicator(strokeWidth: 2))
          : !isHidden
              ? IconButton(
                  icon: const Icon(Icons.visibility_off_outlined,
                      color: Color(0xFFDC2626)),
                  tooltip: 'Ocultar anuncio',
                  onPressed: onHide,
                )
              : const Icon(Icons.visibility_off_rounded,
                  color: Color(0xFFCBD5E1), size: 20),
    );
  }
}
