import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../data/models/listing.dart';
import '../../../data/repositories/listing_repository.dart';
import '../../../data/repositories/client_repository.dart';
import '../../../providers/session_provider.dart';
import '../../shared/widgets/error_view.dart';

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
  bool? _isFavorite;
  bool _togglingFav = false;

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
    final Uri uri;
    if (l.latitude != null && l.longitude != null) {
      uri = Uri(
        scheme: 'https',
        host: 'www.google.com',
        path: '/maps/search/',
        queryParameters: {
          'api': '1',
          'query': '${l.latitude},${l.longitude}',
        },
      );
    } else {
      final q = [l.providerName, l.locationLine]
          .where((s) => s != null && s.isNotEmpty)
          .join(' ');
      uri = Uri(
        scheme: 'https',
        host: 'www.google.com',
        path: '/maps/search/',
        queryParameters: {'api': '1', 'query': q},
      );
    }
    if (await canLaunchUrl(uri)) launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  Future<void> _toggleFavorite() async {
    final session = context.read<SessionProvider>();
    if (!session.isAuthenticated) {
      context.push('/login');
      return;
    }
    final l = _listing;
    if (l == null || _togglingFav) return;
    setState(() => _togglingFav = true);
    try {
      final nowFav = await context.read<ClientRepository>().toggleFavorite(
        serviceId: l.serviceId,
      );
      if (mounted) setState(() { _isFavorite = nowFav; _togglingFav = false; });
    } catch (_) {
      if (mounted) setState(() => _togglingFav = false);
    }
  }

  Future<void> _contact() async {
    final session = context.read<SessionProvider>();
    if (!session.isAuthenticated) {
      context.push('/login');
      return;
    }
    final l = _listing;
    if (l == null) return;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => _ContactSheet(
        listing: l,
        clientRepo: context.read<ClientRepository>(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        top: false,
        child: _loading
            ? const SafeArea(child: Center(child: CircularProgressIndicator()))
            : _error != null
                ? SafeArea(child: ErrorView(message: _error!, onRetry: _load))
                : _listing == null
                    ? const SafeArea(child: ErrorView(message: 'Anuncio no encontrado'))
                    : _Body(
                        listing: _listing!,
                        imgIndex: _imgIndex,
                        onImgIndex: (i) => setState(() => _imgIndex = i),
                        onWhatsApp: _openWhatsApp,
                        onCall: _callPhone,
                        onMaps: _openMaps,
                        onContact: _contact,
                        isFavorite: _isFavorite,
                        togglingFav: _togglingFav,
                        onFavorite: _toggleFavorite,
                      ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Bottom sheet de contacto
// ---------------------------------------------------------------------------
class _ContactSheet extends StatefulWidget {
  const _ContactSheet({required this.listing, required this.clientRepo});
  final Listing listing;
  final ClientRepository clientRepo;

  @override
  State<_ContactSheet> createState() => _ContactSheetState();
}

class _ContactSheetState extends State<_ContactSheet> {
  final _msgCtrl = TextEditingController();
  String _channel = 'app';
  bool _sending = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    // Preseleccionar canal según disponibilidad
    if (widget.listing.whatsapp != null) {
      _channel = 'whatsapp';
    } else if (widget.listing.contactPhone != null) {
      _channel = 'telefono';
    }
  }

  @override
  void dispose() {
    _msgCtrl.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final msg = _msgCtrl.text.trim();
    if (msg.isEmpty) {
      setState(() => _error = 'Escribe un mensaje antes de enviar');
      return;
    }
    setState(() { _sending = true; _error = null; });
    try {
      await widget.clientRepo.contact(
        serviceId: widget.listing.serviceId,
        channel: _channel,
        message: msg,
      );
      if (mounted) {
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Solicitud enviada correctamente')));
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _sending = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    final l = widget.listing;

    return Padding(
      padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + bottom),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
        // Handle
        Center(
          child: Container(
            width: 40, height: 4,
            margin: const EdgeInsets.only(bottom: 16),
            decoration: BoxDecoration(
              color: const Color(0xFFCBD5E1),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ),

        const Text('Enviar solicitud',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800,
                color: Color(0xFF0B1C30))),
        const SizedBox(height: 4),
        Text('a ${l.providerName ?? l.title}',
            style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
        const SizedBox(height: 20),

        // Canal de contacto
        if (l.whatsapp != null || l.contactPhone != null) ...[
          const Text('Canal preferido',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600,
                  color: Color(0xFF64748B))),
          const SizedBox(height: 8),
          Wrap(spacing: 8, children: [
            if (l.whatsapp != null)
              ChoiceChip(
                label: const Text('WhatsApp'),
                selected: _channel == 'whatsapp',
                onSelected: (_) => setState(() => _channel = 'whatsapp'),
              ),
            if (l.contactPhone != null)
              ChoiceChip(
                label: const Text('Teléfono'),
                selected: _channel == 'telefono',
                onSelected: (_) => setState(() => _channel = 'telefono'),
              ),
            ChoiceChip(
              label: const Text('En la app'),
              selected: _channel == 'app',
              onSelected: (_) => setState(() => _channel = 'app'),
            ),
          ]),
          const SizedBox(height: 16),
        ],

        // Mensaje
        TextField(
          controller: _msgCtrl,
          maxLines: 4,
          textCapitalization: TextCapitalization.sentences,
          decoration: InputDecoration(
            hintText: '¿En qué te puede ayudar este negocio? Describe tu necesidad...',
            hintMaxLines: 3,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            contentPadding: const EdgeInsets.all(14),
          ),
        ),
        if (_error != null) ...[
          const SizedBox(height: 8),
          Text(_error!, style: const TextStyle(color: Color(0xFFDC2626), fontSize: 13)),
        ],
        const SizedBox(height: 16),

        // Botón enviar
        SizedBox(
          width: double.infinity,
          height: 50,
          child: FilledButton.icon(
            onPressed: _sending ? null : _send,
            icon: _sending
                ? const SizedBox(width: 18, height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.send_outlined),
            label: Text(_sending ? 'Enviando...' : 'Enviar solicitud'),
            style: FilledButton.styleFrom(backgroundColor: const Color(0xFF003874)),
          ),
        ),
      ]),
    );
  }
}

// ---------------------------------------------------------------------------
// Body principal
// ---------------------------------------------------------------------------
class _Body extends StatelessWidget {
  const _Body({
    required this.listing,
    required this.imgIndex,
    required this.onImgIndex,
    required this.onWhatsApp,
    required this.onCall,
    required this.onMaps,
    required this.onContact,
    required this.isFavorite,
    required this.togglingFav,
    required this.onFavorite,
  });

  final Listing listing;
  final int imgIndex;
  final ValueChanged<int> onImgIndex;
  final VoidCallback onWhatsApp;
  final VoidCallback onCall;
  final VoidCallback onMaps;
  final VoidCallback onContact;
  final bool? isFavorite;
  final bool togglingFav;
  final VoidCallback onFavorite;

  @override
  Widget build(BuildContext context) {
    final images = listing.images.isNotEmpty ? listing.images : <String>[];
    final bottomPad = MediaQuery.of(context).padding.bottom;

    return CustomScrollView(slivers: [
      SliverAppBar(
        expandedHeight: 260,
        pinned: true,
        foregroundColor: Colors.white,
        backgroundColor: const Color(0xFF003874),
        leading: Builder(
          builder: (ctx) => Padding(
            padding: const EdgeInsets.all(8),
            child: InkWell(
              borderRadius: BorderRadius.circular(20),
              onTap: () => Navigator.of(ctx).maybePop(),
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.black38,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.arrow_back_rounded,
                    color: Colors.white, size: 22),
              ),
            ),
          ),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.all(8),
            child: togglingFav
                ? const SizedBox(
                    width: 36, height: 36,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: Colors.white))
                : InkWell(
                    borderRadius: BorderRadius.circular(20),
                    onTap: onFavorite,
                    child: Container(
                      decoration: const BoxDecoration(
                        color: Colors.black38,
                        shape: BoxShape.circle,
                      ),
                      padding: const EdgeInsets.all(6),
                      child: Icon(
                        (isFavorite ?? false)
                            ? Icons.favorite_rounded
                            : Icons.favorite_border_rounded,
                        color: (isFavorite ?? false)
                            ? const Color(0xFFEF4444)
                            : Colors.white,
                        size: 22,
                      ),
                    ),
                  ),
          ),
        ],
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
          padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + bottomPad),
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
