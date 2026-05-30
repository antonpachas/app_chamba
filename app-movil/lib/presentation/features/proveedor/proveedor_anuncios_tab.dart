import 'package:chamba_app/data/api/provider_api.dart';
import 'package:chamba_app/presentation/view_models/provider_listings_vm.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class ProveedorAnunciosTab extends StatelessWidget {
  const ProveedorAnunciosTab({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => ProviderListingsViewModel(api: ctx.read<ProviderApi>())..load(),
      child: const _AnunciosBody(),
    );
  }
}

class _AnunciosBody extends StatelessWidget {
  const _AnunciosBody();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<ProviderListingsViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    if (vm.loading) return const Center(child: CircularProgressIndicator());

    if (vm.items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(shape: BoxShape.circle, color: scheme.secondaryContainer),
              child: Icon(Icons.add_business_rounded, size: 48, color: scheme.onSecondaryContainer),
            ),
            const SizedBox(height: 20),
            Text('Sin anuncios', style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
            const SizedBox(height: 8),
            Text('Publica tu primer anuncio para que los clientes te encuentren.',
                textAlign: TextAlign.center,
                style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant)),
          ]),
        ),
      );
    }

    return Column(children: [
      if (vm.ok != null)
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
          child: Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.green.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.green.shade200),
            ),
            child: Row(children: [
              Icon(Icons.check_circle_rounded, color: Colors.green.shade700, size: 18),
              const SizedBox(width: 8),
              Text(vm.ok!, style: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.w600)),
            ]),
          ),
        ),
      Expanded(
        child: RefreshIndicator(
          onRefresh: vm.load,
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
            itemCount: vm.items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (_, i) => _ListingCard(listing: vm.items[i], vm: vm),
          ),
        ),
      ),
    ]);
  }
}

class _ListingCard extends StatelessWidget {
  const _ListingCard({required this.listing, required this.vm});
  final Map<String, dynamic> listing;
  final ProviderListingsViewModel vm;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;
    final id = listing['id'] as int? ?? 0;
    final title = listing['title']?.toString() ?? '';
    final status = listing['status']?.toString() ?? 'hidden';
    final category = (listing['category'] as Map?)?['name']?.toString() ?? listing['category_name']?.toString() ?? '';
    final price = listing['base_price'];
    final priceType = listing['price_type']?.toString() ?? '';
    final reviews = listing['reviews_count'] as int? ?? 0;
    final rating = listing['avg_rating']?.toString() ?? '';
    final isActive = status == 'active';

    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(22),
        side: isActive
            ? BorderSide(color: Colors.green.withValues(alpha: 0.5), width: 1.2)
            : BorderSide(color: scheme.outlineVariant.withValues(alpha: 0.55)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                if (category.isNotEmpty)
                  Text(category, style: textTheme.labelSmall?.copyWith(color: scheme.primary, fontWeight: FontWeight.w700)),
                const SizedBox(height: 2),
                Text(title, style: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                if (price != null)
                  Text(
                    priceType == 'cotizar' ? 'A cotizar' : 'S/ $price',
                    style: textTheme.bodySmall?.copyWith(fontWeight: FontWeight.w600),
                  ),
              ]),
            ),
            Switch(
              value: isActive,
              onChanged: (_) => vm.toggleStatus(id, status),
            ),
          ]),
          const SizedBox(height: 8),
          Row(children: [
            Icon(
              isActive ? Icons.visibility_rounded : Icons.visibility_off_rounded,
              size: 14,
              color: isActive ? Colors.green : scheme.onSurfaceVariant,
            ),
            const SizedBox(width: 4),
            Text(
              isActive ? 'Publicado' : 'Oculto',
              style: textTheme.bodySmall?.copyWith(
                color: isActive ? Colors.green.shade700 : scheme.onSurfaceVariant,
                fontWeight: FontWeight.w600,
              ),
            ),
            const Spacer(),
            if (rating.isNotEmpty && rating != '0.00') ...[
              Icon(Icons.star_rounded, size: 14, color: Colors.amber.shade700),
              const SizedBox(width: 4),
              Text('$rating ($reviews reseñas)', style: textTheme.bodySmall),
            ],
          ]),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: () => context.push('/listing/$id'),
            icon: const Icon(Icons.open_in_new_rounded, size: 16),
            label: const Text('Ver anuncio público'),
          ),
        ]),
      ),
    );
  }
}
