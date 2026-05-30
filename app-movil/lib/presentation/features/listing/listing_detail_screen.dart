import 'package:cached_network_image/cached_network_image.dart';
import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:chamba_app/data/api/client_api.dart';
import 'package:chamba_app/data/api/listing_api.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:chamba_app/presentation/common/widgets/error_banner.dart';
import 'package:chamba_app/presentation/view_models/listing_detail_vm.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

class ListingDetailScreen extends StatelessWidget {
  const ListingDetailScreen({super.key, required this.listingId});
  final String listingId;

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => ListingDetailViewModel(
        listingApi: ctx.read<ListingApi>(),
        clientApi: ctx.read<ClientApi>(),
      )..load(listingId),
      child: const _DetailBody(),
    );
  }
}

class _DetailBody extends StatelessWidget {
  const _DetailBody();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<ListingDetailViewModel>();

    if (vm.loading && vm.detail == null) {
      return Scaffold(
        backgroundColor: AppTheme.background,
        appBar: AppBar(title: const Text('Cargando…')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (vm.error != null && vm.detail == null) {
      return Scaffold(
        backgroundColor: AppTheme.background,
        appBar: AppBar(title: const Text('Error')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              const Icon(Icons.error_outline_rounded, size: 56, color: AppTheme.error),
              const SizedBox(height: 16),
              Text(vm.error!, textAlign: TextAlign.center, style: const TextStyle(color: AppTheme.textSecondary)),
              const SizedBox(height: 16),
              FilledButton(onPressed: () => vm.load(vm.detail?.id), child: const Text('Reintentar')),
            ]),
          ),
        ),
      );
    }

    final d = vm.detail!;
    return Scaffold(
      backgroundColor: AppTheme.background,
      body: CustomScrollView(
        slivers: [
          _ImageHeader(detail: d),
          SliverToBoxAdapter(child: _DetailContent(vm: vm, detail: d)),
        ],
      ),
    );
  }
}

// ─── Galería de imágenes ──────────────────────────────────────────────────────

class _ImageHeader extends StatefulWidget {
  const _ImageHeader({required this.detail});
  final ListingDetail detail;

  @override
  State<_ImageHeader> createState() => _ImageHeaderState();
}

class _ImageHeaderState extends State<_ImageHeader> {
  int _current = 0;
  final _pc = PageController();

  @override
  void dispose() { _pc.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    final images = widget.detail.images;

    return SliverAppBar(
      expandedHeight: 260,
      pinned: true,
      backgroundColor: AppTheme.primary,
      leading: const BackButton(color: Colors.white),
      title: images.isEmpty ? Text(widget.detail.title, maxLines: 1,
          style: const TextStyle(fontSize: 16, color: Colors.white)) : null,
      flexibleSpace: FlexibleSpaceBar(
        background: images.isEmpty
            ? Container(
                color: const Color(0xFF1D5FAE),
                child: const Icon(Icons.home_repair_service_rounded, size: 80, color: Colors.white30),
              )
            : Stack(fit: StackFit.expand, children: [
                PageView.builder(
                  controller: _pc,
                  itemCount: images.length,
                  onPageChanged: (i) => setState(() => _current = i),
                  itemBuilder: (_, i) => CachedNetworkImage(
                    imageUrl: images[i],
                    fit: BoxFit.cover,
                    placeholder: (_, _) => Container(color: AppTheme.primary.withValues(alpha: 0.3)),
                    errorWidget: (_, _, _) => Container(
                      color: const Color(0xFF1D5FAE),
                      child: const Icon(Icons.broken_image_outlined, size: 48, color: Colors.white30),
                    ),
                  ),
                ),
                // Gradiente inferior
                Positioned(
                  bottom: 0, left: 0, right: 0, height: 80,
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.bottomCenter,
                        end: Alignment.topCenter,
                        colors: [Colors.black.withValues(alpha: 0.5), Colors.transparent],
                      ),
                    ),
                  ),
                ),
                if (images.length > 1)
                  Positioned(
                    bottom: 14, left: 0, right: 0,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(images.length, (i) => AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        margin: const EdgeInsets.symmetric(horizontal: 3),
                        width: _current == i ? 18 : 7,
                        height: 7,
                        decoration: BoxDecoration(
                          color: _current == i ? Colors.white : Colors.white54,
                          borderRadius: BorderRadius.circular(4),
                        ),
                      )),
                    ),
                  ),
              ]),
      ),
    );
  }
}

// ─── Contenido del detalle ────────────────────────────────────────────────────

class _DetailContent extends StatefulWidget {
  const _DetailContent({required this.vm, required this.detail});
  final ListingDetailViewModel vm;
  final ListingDetail detail;

  @override
  State<_DetailContent> createState() => _DetailContentState();
}

class _DetailContentState extends State<_DetailContent> {
  bool _showContact = false;
  bool _showReview = false;
  int _reviewRating = 5;
  final _msgCtrl = TextEditingController();
  final _reviewCtrl = TextEditingController();
  String _channel = 'plataforma';

  @override
  void dispose() { _msgCtrl.dispose(); _reviewCtrl.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    final vm = widget.vm;
    final d = widget.detail;
    final session = context.watch<SessionViewModel>();
    final isCliente = session.user?.isCliente ?? false;
    final isLogged = session.canAccessHome && !session.isGuest;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [

        // ── Cabecera ──
        _Card(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          if (d.categoryName != null) ...[
            _CategoryBadge(d.categoryName!),
            const SizedBox(height: 8),
          ],
          Text(d.title, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: AppTheme.textPrimary, height: 1.2)),
          const SizedBox(height: 10),
          _InfoRow(icon: Icons.storefront_rounded, label: d.providerName ?? 'Negocio'),
          if (d.locationLabel.isNotEmpty) ...[
            const SizedBox(height: 5),
            _InfoRow(icon: Icons.place_rounded, label: d.locationLabel),
          ],
        ])),

        const SizedBox(height: 10),

        // ── Precio + rating ──
        if (d.priceLabel.isNotEmpty || (d.avgRating != null && d.avgRating! > 0))
          _Card(child: Row(children: [
            if (d.priceLabel.isNotEmpty)
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Precio', style: TextStyle(fontSize: 11, color: AppTheme.textSecondary, fontWeight: FontWeight.w600)),
                const SizedBox(height: 3),
                Text(d.priceLabel, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppTheme.primary)),
              ])),
            if (d.avgRating != null && d.avgRating! > 0)
              _RatingChip(rating: d.avgRating!, count: d.reviewsCount ?? 0),
          ])),

        // ── Descripción ──
        if (d.description != null && d.description!.isNotEmpty) ...[
          const SizedBox(height: 10),
          _Card(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const _SectionLabel('Descripción'),
            const SizedBox(height: 8),
            Text(d.description!, style: const TextStyle(fontSize: 14, color: AppTheme.textSecondary, height: 1.6)),
          ])),
        ],

        // ── Dirección + Horarios ──
        if (d.addressText != null || d.openHours.isNotEmpty) ...[
          const SizedBox(height: 10),
          _Card(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            if (d.addressText != null && d.addressText!.isNotEmpty) ...[
              const _SectionLabel('Dirección'),
              const SizedBox(height: 6),
              _InfoRow(icon: Icons.location_on_rounded, label: d.addressText!),
              if (d.openHours.isNotEmpty) const SizedBox(height: 14),
            ],
            if (d.openHours.isNotEmpty) ...[
              const _SectionLabel('Horario de atención'),
              const SizedBox(height: 8),
              ...d.openHours.map((h) => _HourRow(hour: h)),
            ],
          ])),
        ],

        // ── Contacto directo ──
        if (!d.guestPreview && (d.whatsapp != null || d.contactPhone != null)) ...[
          const SizedBox(height: 10),
          _Card(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            const _SectionLabel('Contactar directamente'),
            const SizedBox(height: 10),
            if (d.whatsapp != null && d.whatsapp!.isNotEmpty)
              _ContactButton(
                icon: Icons.chat_rounded,
                label: 'WhatsApp: ${d.whatsapp}',
                color: const Color(0xFF25D366),
                onTap: () => _launchWhatsApp(d.whatsapp!),
              ),
            if (d.contactPhone != null && d.contactPhone!.isNotEmpty) ...[
              if (d.whatsapp != null && d.whatsapp!.isNotEmpty) const SizedBox(height: 8),
              _ContactButton(
                icon: Icons.call_rounded,
                label: 'Llamar: ${d.contactPhone}',
                color: AppTheme.primary,
                onTap: () => _launchPhone(d.contactPhone!),
              ),
            ],
          ])),
        ],

        // ── Solicitud (cliente) ──
        const SizedBox(height: 10),
        if (isCliente) ...[
          if (vm.sendOk != null)
            _SuccessBanner(vm.sendOk!)
          else ...[
            if (vm.sendError != null) ...[
              ErrorBanner(message: vm.sendError!),
              const SizedBox(height: 8),
            ],
            if (!_showContact)
              FilledButton.icon(
                onPressed: () => setState(() => _showContact = true),
                icon: const Icon(Icons.send_rounded),
                label: const Text('Solicitar contacto'),
              )
            else
              _ContactForm(
                msgCtrl: _msgCtrl,
                channel: _channel,
                onChannelChanged: (c) => setState(() => _channel = c),
                loading: vm.sending,
                onSend: () => vm.sendRequest(contactChannel: _channel, message: _msgCtrl.text),
                onCancel: () => setState(() => _showContact = false),
              ),
          ],
        ] else if (!isLogged)
          OutlinedButton.icon(
            onPressed: () {},
            icon: const Icon(Icons.login_rounded),
            label: const Text('Inicia sesión para contactar'),
          ),

        // ── Reseñas ──
        const SizedBox(height: 16),
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(
            'Reseñas (${d.reviews.length})',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppTheme.textPrimary),
          ),
          if (isCliente)
            TextButton.icon(
              onPressed: () => setState(() => _showReview = !_showReview),
              icon: Icon(_showReview ? Icons.close_rounded : Icons.rate_review_rounded, size: 18),
              label: Text(_showReview ? 'Cancelar' : 'Dejar reseña'),
            ),
        ]),

        if (_showReview && isCliente) ...[
          const SizedBox(height: 10),
          _ReviewForm(
            ctrl: _reviewCtrl,
            rating: _reviewRating,
            onRatingChanged: (r) => setState(() => _reviewRating = r),
            loading: vm.reviewSending,
            error: vm.reviewError,
            ok: vm.reviewOk,
            onSubmit: () => vm.submitReview(rating: _reviewRating, comment: _reviewCtrl.text.trim()),
          ),
        ],

        const SizedBox(height: 8),
        if (d.reviews.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 16),
            child: Center(
              child: Text('Sin reseñas aún.', style: TextStyle(fontSize: 14, color: Colors.grey.shade400)),
            ),
          )
        else
          ...d.reviews.map((r) => Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: _ReviewCard(review: r),
          )),

      ]),
    );
  }

  void _launchWhatsApp(String phone) async {
    final number = phone.replaceAll(RegExp(r'\D'), '');
    final uri = Uri.parse('https://wa.me/$number');
    if (await canLaunchUrl(uri)) launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  void _launchPhone(String phone) async {
    final uri = Uri(scheme: 'tel', path: phone.replaceAll(RegExp(r'\D'), ''));
    if (await canLaunchUrl(uri)) launchUrl(uri);
  }
}

// ─── Componentes visuales ─────────────────────────────────────────────────────

class _Card extends StatelessWidget {
  const _Card({required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.border),
      ),
      child: child,
    );
  }
}

class _CategoryBadge extends StatelessWidget {
  const _CategoryBadge(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(6)),
      child: Text(
        text.toUpperCase(),
        style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppTheme.accent, letterSpacing: 0.8),
      ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppTheme.textSecondary, letterSpacing: 0.3),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.label});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(children: [
      Icon(icon, size: 16, color: AppTheme.primary),
      const SizedBox(width: 8),
      Expanded(child: Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppTheme.textPrimary))),
    ]);
  }
}

class _RatingChip extends StatelessWidget {
  const _RatingChip({required this.rating, required this.count});
  final double rating;
  final int count;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(color: const Color(0xFFFFFBEB), borderRadius: BorderRadius.circular(24),
          border: Border.all(color: const Color(0xFFFDE68A))),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        const Icon(Icons.star_rounded, size: 16, color: AppTheme.gold),
        const SizedBox(width: 4),
        Text('${rating.toStringAsFixed(1)}  ($count)',
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF92400E))),
      ]),
    );
  }
}

class _HourRow extends StatelessWidget {
  const _HourRow({required this.hour});
  final Map<String, dynamic> hour;

  @override
  Widget build(BuildContext context) {
    final day = hour['day_label']?.toString() ?? hour['day']?.toString() ?? '';
    final open = hour['open_time']?.toString() ?? hour['from']?.toString() ?? '';
    final close = hour['close_time']?.toString() ?? hour['to']?.toString() ?? '';
    final closed = hour['is_closed'] == true || hour['closed'] == true;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(children: [
        SizedBox(width: 90, child: Text(day, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
        Text(
          closed ? 'Cerrado' : '$open – $close',
          style: TextStyle(fontSize: 13, color: closed ? AppTheme.error : AppTheme.textPrimary),
        ),
      ]),
    );
  }
}

class _ContactButton extends StatelessWidget {
  const _ContactButton({required this.icon, required this.label, required this.color, required this.onTap});
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color.withValues(alpha: 0.08),
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(width: 10),
            Expanded(child: Text(label, style: TextStyle(fontWeight: FontWeight.w700, color: color, fontSize: 14))),
            Icon(Icons.arrow_forward_ios_rounded, size: 14, color: color.withValues(alpha: 0.6)),
          ]),
        ),
      ),
    );
  }
}

class _SuccessBanner extends StatelessWidget {
  const _SuccessBanner(this.message);
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDF4),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFBBF7D0)),
      ),
      child: Row(children: [
        const Icon(Icons.check_circle_rounded, color: AppTheme.success),
        const SizedBox(width: 10),
        Expanded(child: Text(message, style: const TextStyle(color: Color(0xFF166534), fontWeight: FontWeight.w600, fontSize: 14))),
      ]),
    );
  }
}

class _ContactForm extends StatelessWidget {
  const _ContactForm({
    required this.msgCtrl, required this.channel, required this.onChannelChanged,
    required this.loading, required this.onSend, required this.onCancel,
  });
  final TextEditingController msgCtrl;
  final String channel;
  final ValueChanged<String> onChannelChanged;
  final bool loading;
  final VoidCallback onSend;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    return _Card(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      const _SectionLabel('¿Cómo prefieres ser contactado?'),
      const SizedBox(height: 10),
      SegmentedButton<String>(
        segments: const [
          ButtonSegment(value: 'plataforma', icon: Icon(Icons.chat_bubble_outline_rounded), label: Text('Chat')),
          ButtonSegment(value: 'whatsapp', icon: Icon(Icons.chat_rounded), label: Text('WhatsApp')),
          ButtonSegment(value: 'llamada', icon: Icon(Icons.call_outlined), label: Text('Llamada')),
        ],
        selected: {channel},
        onSelectionChanged: (s) => onChannelChanged(s.first),
      ),
      const SizedBox(height: 12),
      TextField(
        controller: msgCtrl,
        maxLines: 3,
        decoration: const InputDecoration(labelText: 'Mensaje (opcional)', hintText: 'Describe lo que necesitas…'),
      ),
      const SizedBox(height: 12),
      Row(children: [
        Expanded(
          child: FilledButton(
            onPressed: loading ? null : onSend,
            child: loading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : const Text('Enviar solicitud'),
          ),
        ),
        const SizedBox(width: 8),
        OutlinedButton(onPressed: onCancel, child: const Text('Cancelar')),
      ]),
    ]));
  }
}

class _ReviewForm extends StatelessWidget {
  const _ReviewForm({
    required this.ctrl, required this.rating, required this.onRatingChanged,
    required this.loading, this.error, this.ok, required this.onSubmit,
  });
  final TextEditingController ctrl;
  final int rating;
  final ValueChanged<int> onRatingChanged;
  final bool loading;
  final String? error;
  final String? ok;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return _Card(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      const _SectionLabel('Tu calificación'),
      const SizedBox(height: 10),
      Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: List.generate(5, (i) => GestureDetector(
          onTap: () => onRatingChanged(i + 1),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 6),
            child: Icon(
              i < rating ? Icons.star_rounded : Icons.star_outline_rounded,
              size: 36,
              color: i < rating ? AppTheme.gold : AppTheme.border,
            ),
          ),
        )),
      ),
      const SizedBox(height: 12),
      TextField(controller: ctrl, maxLines: 3, decoration: const InputDecoration(labelText: 'Comentario (opcional)')),
      if (error != null) ...[const SizedBox(height: 8), ErrorBanner(message: error!)],
      if (ok != null) ...[const SizedBox(height: 8), _SuccessBanner(ok!)],
      const SizedBox(height: 12),
      FilledButton(
        onPressed: loading ? null : onSubmit,
        child: loading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : const Text('Publicar reseña'),
      ),
    ]));
  }
}

class _ReviewCard extends StatelessWidget {
  const _ReviewCard({required this.review});
  final ReviewModel review;

  @override
  Widget build(BuildContext context) {
    return _Card(child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      CircleAvatar(
        radius: 18,
        backgroundColor: const Color(0xFFDBEAFF),
        child: Text(
          (review.authorName?.isNotEmpty == true) ? review.authorName![0].toUpperCase() : '?',
          style: const TextStyle(fontWeight: FontWeight.w800, color: AppTheme.primary, fontSize: 14),
        ),
      ),
      const SizedBox(width: 10),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(child: Text(review.authorName ?? 'Cliente', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14))),
          Row(children: List.generate(5, (i) => Icon(
            i < review.rating ? Icons.star_rounded : Icons.star_outline_rounded,
            size: 14, color: i < review.rating ? AppTheme.gold : AppTheme.border,
          ))),
        ]),
        if (review.comment != null && review.comment!.isNotEmpty) ...[
          const SizedBox(height: 5),
          Text(review.comment!, style: const TextStyle(fontSize: 13, color: AppTheme.textSecondary, height: 1.45)),
        ],
      ])),
    ]));
  }
}
