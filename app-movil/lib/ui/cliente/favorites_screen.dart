import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/client_repository.dart';
import '../shared/widgets/error_view.dart';

class ClientFavoritesScreen extends StatefulWidget {
  const ClientFavoritesScreen({super.key});

  @override
  State<ClientFavoritesScreen> createState() => _ClientFavoritesScreenState();
}

class _ClientFavoritesScreenState extends State<ClientFavoritesScreen> {
  List<Map<String, dynamic>> _favorites = [];
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
      _favorites = await context.read<ClientRepository>().myFavorites();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _remove(Map<String, dynamic> fav) async {
    try {
      final profileId = (fav['provider_profile_id'] as num?)?.toInt();
      final serviceId = (fav['provider_service_id'] as num?)?.toInt();
      await context.read<ClientRepository>().toggleFavorite(
        profileId: profileId, serviceId: serviceId,
      );
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString())));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Favoritos')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _favorites.isEmpty
                  ? const _EmptyState()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: _favorites.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 10),
                        itemBuilder: (_, i) => _FavoriteTile(
                          fav: _favorites[i],
                          onRemove: () => _remove(_favorites[i]),
                          onTap: () {
                            final serviceId =
                                _favorites[i]['provider_service_id'];
                            if (serviceId != null) {
                              context.push('/listing/$serviceId');
                            }
                          },
                        ),
                      ),
                    ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) => const Center(
    child: Padding(
      padding: EdgeInsets.all(32),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.favorite_border_rounded, size: 72, color: Color(0xFFCBD5E1)),
        SizedBox(height: 16),
        Text('Aún no tienes favoritos',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
        SizedBox(height: 8),
        Text('Guarda negocios tocando el ♡ en sus anuncios.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Color(0xFF64748B))),
      ]),
    ),
  );
}

class _FavoriteTile extends StatelessWidget {
  const _FavoriteTile({
    required this.fav,
    required this.onRemove,
    required this.onTap,
  });

  final Map<String, dynamic> fav;
  final VoidCallback onRemove;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final title    = fav['listing_title'] as String?
        ?? fav['service_title'] as String? ?? 'Anuncio';
    final provider = fav['provider_name'] as String? ?? '';
    final cover    = fav['cover_image_url'] as String?;
    final location = [
      fav['district_name'] as String?,
      fav['province_name'] as String?,
    ].whereType<String>().join(' · ');

    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Container(
                width: 64, height: 64,
                color: const Color(0xFFE8EEF6),
                child: cover != null
                    ? CachedNetworkImage(imageUrl: cover, fit: BoxFit.cover,
                        errorWidget: (_, _, _) => const Icon(
                            Icons.storefront_outlined, color: Color(0xFFA0B8D4)))
                    : const Icon(Icons.storefront_outlined,
                        color: Color(0xFFA0B8D4)),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title,
                    maxLines: 2, overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 14,
                        fontWeight: FontWeight.w700, color: Color(0xFF0B1C30))),
                if (provider.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(provider,
                      style: const TextStyle(fontSize: 12,
                          color: Color(0xFF64748B))),
                ],
                if (location.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Row(children: [
                    const Icon(Icons.location_on_outlined, size: 12,
                        color: Color(0xFF94A3B8)),
                    const SizedBox(width: 2),
                    Expanded(child: Text(location,
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 11,
                            color: Color(0xFF94A3B8)))),
                  ]),
                ],
              ]),
            ),
            IconButton(
              icon: const Icon(Icons.favorite_rounded,
                  color: Color(0xFFEF4444)),
              onPressed: onRemove,
              tooltip: 'Quitar favorito',
            ),
          ]),
        ),
      ),
    );
  }
}
