import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/provider_repository.dart';
import '../shared/widgets/error_view.dart';

class ProviderListingsScreen extends StatefulWidget {
  const ProviderListingsScreen({super.key});

  @override
  State<ProviderListingsScreen> createState() => _ProviderListingsScreenState();
}

class _ProviderListingsScreenState extends State<ProviderListingsScreen> {
  List<Map<String, dynamic>> _listings = [];
  bool _loading = true;
  String? _error;
  // id del listing que está procesando cambio de estado
  int? _togglingId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      _listings = await context.read<ProviderRepository>().myListings();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _toggleActive(Map<String, dynamic> listing) async {
    final id = listing['id'] as int;
    final isActive = listing['is_active'] == true;
    setState(() => _togglingId = id);
    try {
      await context.read<ProviderRepository>().setListingActive(
        id, active: !isActive,
      );
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _togglingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mis anuncios'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _load,
            tooltip: 'Actualizar',
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _listings.isEmpty
                  ? _EmptyState()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listings.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 10),
                        itemBuilder: (_, i) => _ListingTile(
                          listing: _listings[i],
                          toggling: _togglingId == (_listings[i]['id'] as int?),
                          onToggle: () => _toggleActive(_listings[i]),
                        ),
                      ),
                    ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final created = await context.push<bool>('/provider/listings/new');
          if (created == true && mounted) _load();
        },
        icon: const Icon(Icons.add),
        label: const Text('Nuevo anuncio'),
        backgroundColor: const Color(0xFF003874),
        foregroundColor: Colors.white,
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(32),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        const Icon(Icons.campaign_outlined, size: 72, color: Color(0xFFCBD5E1)),
        const SizedBox(height: 16),
        const Text('Aún no tienes anuncios',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700,
                color: Color(0xFF0B1C30))),
        const SizedBox(height: 8),
        const Text('Toca el botón para publicar tu primer anuncio',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Color(0xFF64748B))),
      ]),
    ),
  );
}

class _ListingTile extends StatelessWidget {
  const _ListingTile({
    required this.listing,
    required this.toggling,
    required this.onToggle,
  });

  final Map<String, dynamic> listing;
  final bool toggling;
  final VoidCallback onToggle;

  @override
  Widget build(BuildContext context) {
    final title      = listing['title'] as String? ?? 'Sin título';
    final isActive   = listing['is_active'] == true;
    final category   = listing['category_name'] as String?
        ?? (listing['category'] as Map?)?['name'] as String?
        ?? '';
    final price      = listing['base_price'];
    final priceType  = listing['price_type'] as String? ?? '';
    final coverUrl   = listing['cover_image_url'] as String?
        ?? (listing['images'] as List?)?.firstOrNull as String?;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          // Miniatura
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: Container(
              width: 72, height: 72,
              color: const Color(0xFFE8EEF6),
              child: coverUrl != null
                  ? Image.network(coverUrl, fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => const Icon(
                          Icons.storefront_outlined, color: Color(0xFFA0B8D4)))
                  : const Icon(Icons.storefront_outlined,
                      color: Color(0xFFA0B8D4)),
            ),
          ),
          const SizedBox(width: 14),
          // Info
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              if (category.isNotEmpty)
                Text(category, style: const TextStyle(
                    fontSize: 11, color: Color(0xFF64748B),
                    fontWeight: FontWeight.w500)),
              const SizedBox(height: 2),
              Text(title,
                  maxLines: 2, overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 14,
                      fontWeight: FontWeight.w700, color: Color(0xFF0B1C30))),
              const SizedBox(height: 4),
              if (price != null)
                Text('S/ $price ${priceType.isNotEmpty ? "($priceType)" : ""}',
                    style: const TextStyle(fontSize: 13,
                        fontWeight: FontWeight.w700, color: Color(0xFF003874))),
              const SizedBox(height: 8),
              // Badge estado
              Row(children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                  decoration: BoxDecoration(
                    color: isActive
                        ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(isActive ? 'Activo' : 'Pausado',
                      style: TextStyle(
                          fontSize: 11, fontWeight: FontWeight.w700,
                          color: isActive
                              ? const Color(0xFF16A34A)
                              : const Color(0xFF64748B))),
                ),
              ]),
            ]),
          ),
          // Toggle
          toggling
              ? const SizedBox(width: 32, height: 32,
                  child: CircularProgressIndicator(strokeWidth: 2))
              : Switch.adaptive(
                  value: isActive,
                  onChanged: (_) => onToggle(),
                  activeTrackColor: const Color(0xFF003874),
                ),
        ]),
      ),
    );
  }
}
