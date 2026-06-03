import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../data/models/listing.dart';
import '../../data/repositories/listing_repository.dart';
import '../../data/repositories/client_repository.dart';
import '../../providers/session_provider.dart';
import '../shared/widgets/error_view.dart';

class ListingDetailScreen extends StatefulWidget {
  const ListingDetailScreen({super.key, required this.listingId});
  final String listingId;

  @override
  State<ListingDetailScreen> createState() => _ListingDetailScreenState();
}

class _ListingDetailScreenState extends State<ListingDetailScreen> {
  Listing? _listing;
  bool _loading = true;
  String? _error;
  int _imgIndex = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      _listing = await context.read<ListingRepository>().detail(widget.listingId);
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _openWhatsApp() async {
    final wa = _listing?.whatsapp;
    if (wa == null) return;
    final clean = wa.replaceAll(RegExp(r'\D'), '');
    final uri = Uri.parse('https://wa.me/$clean');
    if (await canLaunchUrl(uri)) launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  Future<void> _callPhone() async {
    final phone = _listing?.contactPhone;
    if (phone == null) return;
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) launchUrl(uri);
  }

  Future<void> _openMaps() async {
    final l = _listing;
    if (l == null) return;
    Uri uri;
    if (l.latitude != null && l.longitude != null) {
      uri = Uri.parse('https://maps.google.com/?q=${l.latitude},${l.longitude}');
    } else {
      final q = Uri.encodeComponent('${l.providerName} ${l.locationLine}');
      uri = Uri.parse('https://maps.google.com/?q=$q');
    }
    if (await canLaunchUrl(uri)) launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  Future<void> _contact() async {
    final session = context.read<SessionProvider>();
    if (!session.isAuthenticated) {
      context.push('/login');
      return;
    }
    final l = _listing;
    if (l == null) return;
    try {
      await context.read<ClientRepository>().contact(
        serviceId: l.serviceId,
        channel: l.whatsapp != null ? 'whatsapp' : 'telefono',
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Solicitud enviada correctamente')));
      }
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
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _listing == null
                  ? const ErrorView(message: 'Anuncio no encontrado')
                  : _Body(
                      listing: _listing!,
                      imgIndex: _imgIndex,
                      onImgIndex: (i) => setState(() => _imgIndex = i),
                      onWhatsApp: _openWhatsApp,
                      onCall: _callPhone,
                      onMaps: _openMaps,
                      onContact: _contact,
                    ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({
    required this.listing,
    required this.imgIndex,
    required this.onImgIndex,
    required this.onWhatsApp,
    required this.onCall,
    required this.onMaps,
    required this.onContact,
  });

  final Listing listing;
  final int imgIndex;
  final ValueChanged<int> onImgIndex;
  final VoidCallback onWhatsApp;
  final VoidCallback onCall;
  final VoidCallback onMaps;
  final VoidCallback onContact;

  @override
  Widget build(BuildContext context) {
    final images = listing.images.isNotEmpty ? listing.images : <String>[];

    return CustomScrollView(slivers: [
      SliverAppBar(
        expandedHeight: 260,
        pinned: true,
        flexibleSpace: FlexibleSpaceBar(
          background: images.isEmpty
              ? Container(color: const Color(0xFFE8EEF6),
                  child: const Icon(Icons.storefront_outlined,
                      size: 72, color: Color(0xFFA0B8D4)))
              : PageView.builder(
                  itemCount: images.length,
                  onPageChanged: onImgIndex,
                  itemBuilder: (_, i) => CachedNetworkImage(
                    imageUrl: images[i], fit: BoxFit.cover,
                    errorWidget: (_, _, _) => Container(color: const Color(0xFFE8EEF6)),
                  ),
                ),
        ),
      ),
      SliverToBoxAdapter(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            if (listing.categoryName != null)
              Text(listing.categoryName!,
                  style: const TextStyle(fontSize: 12, color: Color(0xFF64748B),
                      fontWeight: FontWeight.w600)),
            const SizedBox(height: 4),
            Text(listing.title,
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800,
                    color: Color(0xFF0B1C30))),
            const SizedBox(height: 8),
            if (listing.providerName != null)
              Row(children: [
                const Icon(Icons.storefront_outlined, size: 16, color: Color(0xFF64748B)),
                const SizedBox(width: 4),
                Text(listing.providerName!,
                    style: const TextStyle(fontSize: 14, color: Color(0xFF64748B))),
              ]),
            const SizedBox(height: 6),
            if (listing.locationLine.isNotEmpty)
              Row(children: [
                const Icon(Icons.location_on_outlined, size: 16, color: Color(0xFF64748B)),
                const SizedBox(width: 4),
                Expanded(child: Text(listing.locationLine,
                    style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)))),
                GestureDetector(
                  onTap: onMaps,
                  child: const Text('Ver en mapa',
                      style: TextStyle(fontSize: 12, color: Color(0xFF003874),
                          fontWeight: FontWeight.w600)),
                ),
              ]),
            const SizedBox(height: 16),
            // Rating
            if ((listing.totalReviews ?? 0) > 0)
              Row(children: [
                ...List.generate(5, (i) => Icon(
                  i < (listing.avgRating ?? 0).round()
                      ? Icons.star_rounded : Icons.star_outline_rounded,
                  size: 20, color: const Color(0xFFF59E0B))),
                const SizedBox(width: 6),
                Text('${listing.avgRating?.toStringAsFixed(1)} '
                    '(${listing.totalReviews} reseñas)',
                    style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
              ]),
            const Divider(height: 32),
            // Precio
            Row(children: [
              const Text('Precio:', style: TextStyle(fontSize: 14,
                  fontWeight: FontWeight.w700, color: Color(0xFF64748B))),
              const SizedBox(width: 8),
              Text(listing.priceLabel,
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900,
                      color: Color(0xFF003874))),
            ]),
            const Divider(height: 32),
            // Descripción
            if (listing.description != null) ...[
              const Text('Descripción', style: TextStyle(fontSize: 16,
                  fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              Text(listing.description!,
                  style: const TextStyle(fontSize: 14, height: 1.6,
                      color: Color(0xFF374151))),
              const SizedBox(height: 24),
            ],
            // Botones de contacto
            const Text('Contactar', style: TextStyle(fontSize: 16,
                fontWeight: FontWeight.w800)),
            const SizedBox(height: 12),
            if (listing.whatsapp != null)
              _ContactBtn(
                icon: Icons.chat_outlined,
                label: 'WhatsApp',
                color: const Color(0xFF22C55E),
                onTap: onWhatsApp,
              ),
            if (listing.contactPhone != null) ...[
              const SizedBox(height: 10),
              _ContactBtn(
                icon: Icons.phone_outlined,
                label: 'Llamar',
                color: const Color(0xFF003874),
                onTap: onCall,
              ),
            ],
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: onContact,
              icon: const Icon(Icons.send_outlined),
              label: const Text('Enviar solicitud por la app'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48)),
            ),
            const SizedBox(height: 40),
          ]),
        ),
      ),
    ]);
  }
}

class _ContactBtn extends StatelessWidget {
  const _ContactBtn({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => FilledButton.icon(
    onPressed: onTap,
    icon: Icon(icon),
    label: Text(label),
    style: FilledButton.styleFrom(
      backgroundColor: color,
      minimumSize: const Size.fromHeight(48),
    ),
  );
}
