import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import '../../../data/models/listing.dart';

class ListingCard extends StatelessWidget {
  const ListingCard({super.key, required this.listing, required this.onTap});
  final Listing listing;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(color: Colors.black.withAlpha(8),
                blurRadius: 8, offset: const Offset(0, 2)),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          _Image(listing: listing),
          _Info(listing: listing),
        ]),
      ),
    );
  }
}

class _Image extends StatelessWidget {
  const _Image({required this.listing});
  final Listing listing;

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 4 / 3,
      child: Stack(fit: StackFit.expand, children: [
        listing.coverImageUrl != null
            ? CachedNetworkImage(
                imageUrl: listing.coverImageUrl!,
                fit: BoxFit.cover,
                errorWidget: (_, _, _) => _placeholder(),
                placeholder: (_, _)   => _placeholder(),
              )
            : _placeholder(),
        // Badge Destacado / Pro
        Positioned(
          top: 8, left: 8,
          child: listing.listingType == 'promocion'
              ? _badge('Destacado', const Color(0xFFF59E0B))
              : listing.isPro
                  ? _badge('Pro', const Color(0xFF003874))
                  : const SizedBox.shrink(),
        ),
        // Distancia
        if (listing.distanceKm != null)
          Positioned(
            bottom: 8, right: 8,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: Colors.black54,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                '${listing.distanceKm!.toStringAsFixed(1)} km',
                style: const TextStyle(color: Colors.white, fontSize: 11,
                    fontWeight: FontWeight.w600),
              ),
            ),
          ),
      ]),
    );
  }

  Widget _placeholder() => Container(
    color: const Color(0xFFE8EEF6),
    child: const Icon(Icons.storefront_outlined, color: Color(0xFFA0B8D4), size: 40),
  );

  Widget _badge(String label, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
    decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(20)),
    child: Text(label, style: const TextStyle(color: Colors.white,
        fontSize: 10, fontWeight: FontWeight.w700)),
  );
}

class _Info extends StatelessWidget {
  const _Info({required this.listing});
  final Listing listing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (listing.categoryName != null)
          Text(listing.categoryName!,
              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B),
                  fontWeight: FontWeight.w500)),
        const SizedBox(height: 2),
        Text(listing.title,
            maxLines: 2, overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700,
                color: Color(0xFF0B1C30))),
        const SizedBox(height: 4),
        if (listing.locationLine.isNotEmpty)
          Row(children: [
            const Icon(Icons.location_on_outlined, size: 13, color: Color(0xFF64748B)),
            const SizedBox(width: 2),
            Expanded(child: Text(listing.locationLine,
                maxLines: 1, overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)))),
          ]),
        const SizedBox(height: 8),
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(listing.priceLabel,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800,
                  color: Color(0xFF0B1C30))),
          if ((listing.totalReviews ?? 0) > 0)
            Row(children: [
              const Icon(Icons.star_rounded, size: 14, color: Color(0xFFF59E0B)),
              const SizedBox(width: 2),
              Text(listing.avgRating!.toStringAsFixed(1),
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
              Text(' (${listing.totalReviews})',
                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
            ]),
        ]),
      ]),
    );
  }
}
