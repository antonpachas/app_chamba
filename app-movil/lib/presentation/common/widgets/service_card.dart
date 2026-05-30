import 'dart:ui';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

// ─── Íconos por categoría (igual que en el web) ───────────────────────────────
const _categoryIcons = <String, IconData>{
  'electricidad': Icons.electrical_services_rounded,
  'electric': Icons.electrical_services_rounded,
  'plomer': Icons.plumbing_rounded,
  'gasfit': Icons.water_drop_rounded,
  'carpint': Icons.carpenter_rounded,
  'pintur': Icons.format_paint_rounded,
  'limpie': Icons.cleaning_services_rounded,
  'jardin': Icons.grass_rounded,
  'mecán': Icons.build_rounded,
  'mecani': Icons.build_rounded,
  'transport': Icons.local_shipping_rounded,
  'cerraj': Icons.lock_rounded,
  'tecnol': Icons.computer_rounded,
  'belleza': Icons.face_retouching_natural_rounded,
  'construcc': Icons.construction_rounded,
  'mudanz': Icons.local_shipping_rounded,
  'repar': Icons.handyman_rounded,
};

IconData _iconForCategory(String? name) {
  if (name == null) return Icons.home_repair_service_rounded;
  final lower = name.toLowerCase();
  for (final e in _categoryIcons.entries) {
    if (lower.contains(e.key)) return e.value;
  }
  return Icons.home_repair_service_rounded;
}

// ─── Colores de placeholder por inicial ──────────────────────────────────────
const _placeholderGradients = [
  [Color(0xFF1E3A5F), Color(0xFF2563EB)],
  [Color(0xFF064E3B), Color(0xFF059669)],
  [Color(0xFF3B0764), Color(0xFF7C3AED)],
  [Color(0xFF7C2D12), Color(0xFFEA580C)],
  [Color(0xFF0C4A6E), Color(0xFF0284C7)],
  [Color(0xFF1A2E05), Color(0xFF4D7C0F)],
  [Color(0xFF450A0A), Color(0xFFDC2626)],
  [Color(0xFF1C1917), Color(0xFF78716C)],
];

List<Color> _gradientForId(int id) {
  return _placeholderGradients[id % _placeholderGradients.length];
}

// ─── ServiceCard — réplica del componente web ─────────────────────────────────

class ServiceCard extends StatelessWidget {
  const ServiceCard({
    super.key,
    required this.listing,
    this.featured = false,
    this.compact = false,
  });

  final ListingCard listing;
  final bool featured;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final isFeatured = featured || listing.isFeatured;
    final id = listing.id;

    return GestureDetector(
      onTap: () => context.push('/listing/$id'),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppTheme.border),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          // Imagen 4:3
          AspectRatio(
            aspectRatio: compact ? 16 / 9 : 4 / 3,
            child: _ImageSection(listing: listing, isFeatured: isFeatured),
          ),
          // Info
          _InfoSection(listing: listing),
        ]),
      ),
    );
  }
}

// ─── Sección de imagen con overlays ─────────────────────────────────────────

class _ImageSection extends StatelessWidget {
  const _ImageSection({required this.listing, required this.isFeatured});
  final ListingCard listing;
  final bool isFeatured;

  @override
  Widget build(BuildContext context) {
    final hasImage = listing.thumbnailUrl != null && listing.thumbnailUrl!.isNotEmpty;
    final gradColors = _gradientForId(listing.id);

    return Stack(fit: StackFit.expand, children: [
      // Imagen o placeholder atractivo
      if (hasImage)
        CachedNetworkImage(
          imageUrl: listing.thumbnailUrl!,
          fit: BoxFit.cover,
          placeholder: (_, _) => _GradientPlaceholder(colors: gradColors, category: listing.categoryName),
          errorWidget: (_, _, _) => _GradientPlaceholder(colors: gradColors, category: listing.categoryName),
        )
      else
        _GradientPlaceholder(colors: gradColors, category: listing.categoryName),

      // Gradiente inferior (siempre presente para legibilidad)
      Positioned.fill(
        child: DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              stops: const [0.45, 1.0],
              colors: [Colors.transparent, Colors.black.withValues(alpha: 0.55)],
            ),
          ),
        ),
      ),

      // Badge Destacado (amber, top-left)
      if (isFeatured)
        Positioned(
          top: 10, left: 10,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Color(0xFFF59E0B), Color(0xFFD97706)]),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: 6, offset: const Offset(0, 2))],
            ),
            child: const Row(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.star_rounded, size: 11, color: Colors.white),
              SizedBox(width: 3),
              Text('Destacado', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: 0.3)),
            ]),
          ),
        ),

      // Rating (bottom-left sobre imagen)
      if (listing.avgRating != null && listing.avgRating! > 0)
        Positioned(
          bottom: 10, left: 10,
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.star_rounded, size: 14, color: Color(0xFFFBBF24)),
            const SizedBox(width: 3),
            Text(
              listing.avgRating!.toStringAsFixed(1),
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white),
            ),
            if (listing.reviewsCount != null && listing.reviewsCount! > 0)
              Text(
                ' (${listing.reviewsCount})',
                style: TextStyle(fontSize: 11, color: Colors.white.withValues(alpha: 0.8)),
              ),
          ]),
        ),
    ]);
  }
}

// ─── Placeholder degradado atractivo (cuando no hay foto) ────────────────────

class _GradientPlaceholder extends StatelessWidget {
  const _GradientPlaceholder({required this.colors, this.category});
  final List<Color> colors;
  final String? category;

  @override
  Widget build(BuildContext context) {
    final icon = _iconForCategory(category);

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: colors,
        ),
      ),
      child: Stack(fit: StackFit.expand, children: [
        // Círculo decorativo de fondo
        Positioned(
          right: -30, top: -30,
          child: Container(
            width: 120, height: 120,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.06),
            ),
          ),
        ),
        Positioned(
          left: -20, bottom: -20,
          child: Container(
            width: 90, height: 90,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.05),
            ),
          ),
        ),
        // Ícono central
        Center(
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
            ),
            child: Icon(icon, size: 36, color: Colors.white.withValues(alpha: 0.9)),
          ),
        ),
      ]),
    );
  }
}

// ─── Sección de información ───────────────────────────────────────────────────

class _InfoSection extends StatelessWidget {
  const _InfoSection({required this.listing});
  final ListingCard listing;

  @override
  Widget build(BuildContext context) {
    final loc = [listing.districtName, listing.provinceName].where((e) => e != null && e.isNotEmpty).join(' · ');

    return Padding(
      padding: const EdgeInsets.all(12),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (listing.categoryName != null)
          Text(
            listing.categoryName!,
            style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary, fontWeight: FontWeight.w500),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        const SizedBox(height: 3),
        Text(
          listing.title,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppTheme.textPrimary, height: 1.25),
        ),
        if (listing.providerName != null) ...[
          const SizedBox(height: 4),
          Text(
            listing.providerName!,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
          ),
        ],
        if (loc.isNotEmpty) ...[
          const SizedBox(height: 3),
          Row(children: [
            const Icon(Icons.place_outlined, size: 12, color: AppTheme.textSecondary),
            const SizedBox(width: 2),
            Expanded(
              child: Text(loc, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
            ),
          ]),
        ],
        const SizedBox(height: 8),
        const Divider(height: 1, color: AppTheme.border),
        const SizedBox(height: 8),
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(
            listing.priceLabel.isEmpty ? 'Consultar' : listing.priceLabel,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppTheme.textPrimary),
          ),
          const Row(children: [
            Text('Ver detalle', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.primary)),
            SizedBox(width: 2),
            Icon(Icons.arrow_forward_rounded, size: 13, color: AppTheme.primary),
          ]),
        ]),
      ]),
    );
  }
}

// ─── FeaturedCard (para el slider de destacados) ─────────────────────────────

class FeaturedCard extends StatelessWidget {
  const FeaturedCard({super.key, required this.listing});
  final ListingCard listing;

  @override
  Widget build(BuildContext context) {
    final hasImage = listing.thumbnailUrl != null && listing.thumbnailUrl!.isNotEmpty;
    final gradColors = _gradientForId(listing.id);

    return GestureDetector(
      onTap: () => context.push('/listing/${listing.id}'),
      child: Container(
        width: 240,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
              color: AppTheme.primary.withValues(alpha: 0.2),
              blurRadius: 16,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Stack(fit: StackFit.expand, children: [
          // Imagen o placeholder
          if (hasImage)
            CachedNetworkImage(
              imageUrl: listing.thumbnailUrl!,
              fit: BoxFit.cover,
              placeholder: (_, _) => _GradientPlaceholder(colors: gradColors, category: listing.categoryName),
              errorWidget: (_, _, _) => _GradientPlaceholder(colors: gradColors, category: listing.categoryName),
            )
          else
            _GradientPlaceholder(colors: gradColors, category: listing.categoryName),

          // Gradiente fuerte abajo para texto legible
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  stops: const [0.3, 1.0],
                  colors: [Colors.transparent, Colors.black.withValues(alpha: 0.80)],
                ),
              ),
            ),
          ),

          // Badge Patrocinado
          Positioned(
            top: 10, left: 10,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(20),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 6, sigmaY: 6),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  color: Colors.black.withValues(alpha: 0.35),
                  child: const Row(mainAxisSize: MainAxisSize.min, children: [
                    Icon(Icons.workspace_premium_rounded, size: 11, color: Color(0xFFFBBF24)),
                    SizedBox(width: 3),
                    Text('Patrocinado', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Colors.white, letterSpacing: 0.3)),
                  ]),
                ),
              ),
            ),
          ),

          // Contenido inferior
          Positioned(
            bottom: 0, left: 0, right: 0,
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                if (listing.categoryName != null)
                  Text(
                    listing.categoryName!,
                    style: const TextStyle(fontSize: 10, color: Colors.white70, fontWeight: FontWeight.w600, letterSpacing: 0.3),
                  ),
                const SizedBox(height: 3),
                Text(
                  listing.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white, height: 1.2),
                ),
                const SizedBox(height: 5),
                Row(children: [
                  if (listing.avgRating != null && listing.avgRating! > 0) ...[
                    const Icon(Icons.star_rounded, size: 13, color: Color(0xFFFBBF24)),
                    const SizedBox(width: 3),
                    Text(listing.avgRating!.toStringAsFixed(1),
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white)),
                    const SizedBox(width: 8),
                  ],
                  const Icon(Icons.place_outlined, size: 12, color: Colors.white60),
                  const SizedBox(width: 2),
                  Expanded(
                    child: Text(
                      [listing.districtName, listing.provinceName].where((e) => e != null && e.isNotEmpty).join(', '),
                      maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 11, color: Colors.white70),
                    ),
                  ),
                ]),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.25)),
                  ),
                  child: Text(
                    listing.priceLabel.isEmpty ? 'Consultar' : listing.priceLabel,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Colors.white),
                  ),
                ),
              ]),
            ),
          ),
        ]),
      ),
    );
  }
}
