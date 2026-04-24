import 'package:chamba_app/data/api/catalog_api.dart';
import 'package:chamba_app/data/api/search_api.dart';
import 'package:chamba_app/presentation/common/widgets/error_banner.dart';
import 'package:chamba_app/presentation/features/cliente/buscar_view_model.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class BuscarTab extends StatelessWidget {
  const BuscarTab({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => BuscarViewModel(
        catalogApi: ctx.read<CatalogApi>(),
        searchApi: ctx.read<SearchApi>(),
      )..loadCategories(),
      child: const _BuscarBody(),
    );
  }
}

class _BuscarBody extends StatefulWidget {
  const _BuscarBody();

  @override
  State<_BuscarBody> createState() => _BuscarBodyState();
}

class _BuscarBodyState extends State<_BuscarBody> {
  final _keyword = TextEditingController();

  @override
  void dispose() {
    _keyword.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<BuscarViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 8),
          child: Card(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.tune_rounded, color: scheme.primary, size: 22),
                      const SizedBox(width: 8),
                      Text(
                        'Encuentra un servicio',
                        style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Filtra por rubro y palabras clave.',
                    style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                  ),
                  const SizedBox(height: 16),
                  if (vm.loadingCategories)
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: const LinearProgressIndicator(minHeight: 6),
                    )
                  else ...[
                    Text('Categoría', style: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        FilterChip(
                          label: const Text('Todas'),
                          selected: vm.selectedCategoryId == null,
                          showCheckmark: true,
                          avatar: Icon(
                            Icons.apps_rounded,
                            size: 18,
                            color: vm.selectedCategoryId == null ? scheme.onSecondaryContainer : scheme.onSurfaceVariant,
                          ),
                          onSelected: vm.loadingSearch ? null : (_) => vm.setCategory(null),
                        ),
                        for (final c in vm.categories)
                          FilterChip(
                            label: Text(c.name),
                            selected: vm.selectedCategoryId == c.id,
                            showCheckmark: true,
                            onSelected: vm.loadingSearch ? null : (_) => vm.setCategory(c.id),
                          ),
                      ],
                    ),
                  ],
                  const SizedBox(height: 14),
                  TextField(
                    controller: _keyword,
                    decoration: const InputDecoration(
                      labelText: '¿Qué necesitas?',
                      hintText: 'Ej. llanta, electricista, melamina…',
                      prefixIcon: Icon(Icons.search_rounded),
                    ),
                    textInputAction: TextInputAction.search,
                    onChanged: vm.setKeyword,
                    onSubmitted: (_) {
                      vm.setKeyword(_keyword.text);
                      vm.search();
                    },
                  ),
                  const SizedBox(height: 14),
                  FilledButton.icon(
                    onPressed: vm.loadingSearch
                        ? null
                        : () {
                            vm.setKeyword(_keyword.text);
                            vm.search();
                          },
                    icon: vm.loadingSearch
                        ? SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              color: scheme.onPrimary,
                            ),
                          )
                        : const Icon(Icons.travel_explore_rounded),
                    label: const Text('Buscar cerca de ti'),
                  ),
                  if (vm.error != null) ...[
                    const SizedBox(height: 12),
                    ErrorBanner(message: vm.error!),
                  ],
                ],
              ),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 8),
          child: Row(
            children: [
              Text(
                'Resultados',
                style: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
              ),
              const Spacer(),
              if (vm.hasSearched)
                Text(
                  '${vm.results.length} encontrados',
                  style: textTheme.labelMedium?.copyWith(color: scheme.onSurfaceVariant),
                ),
            ],
          ),
        ),
        Expanded(
          child: _ResultsBody(vm: vm),
        ),
      ],
    );
  }
}

class _ResultsBody extends StatelessWidget {
  const _ResultsBody({required this.vm});

  final BuscarViewModel vm;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    if (!vm.hasSearched) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.manage_search_rounded, size: 56, color: scheme.outline),
              const SizedBox(height: 16),
              Text(
                'Busca por categoría o palabra',
                style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                'Te mostraremos proveedores y servicios que coincidan con lo que necesitas.',
                textAlign: TextAlign.center,
                style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant, height: 1.4),
              ),
            ],
          ),
        ),
      );
    }

    if (vm.results.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.search_off_rounded, size: 56, color: scheme.outline),
              const SizedBox(height: 16),
              Text(
                'Sin resultados',
                style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 8),
              Text(
                'Prueba otra categoría o cambia las palabras clave.',
                textAlign: TextAlign.center,
                style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant, height: 1.4),
              ),
            ],
          ),
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
      itemCount: vm.results.length,
      separatorBuilder: (context, index) => const SizedBox(height: 10),
      itemBuilder: (context, i) {
        final r = vm.results[i];
        final title = r['title']?.toString() ?? '';
        final provider = r['provider_name']?.toString() ?? '';
        final district = r['district_name']?.toString() ?? '';
        final province = r['province_name']?.toString() ?? '';
        final rating = r['avg_rating']?.toString() ?? '';
        final category = r['category_name']?.toString() ?? '';
        final price = r['base_price']?.toString();
        final priceType = r['price_type']?.toString() ?? '';

        return Card(
          child: InkWell(
            borderRadius: BorderRadius.circular(20),
            onTap: () {
              // Próximo: detalle + contacto
            },
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: scheme.secondaryContainer,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: Icon(Icons.home_repair_service_rounded, color: scheme.onSecondaryContainer),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800, height: 1.2),
                            ),
                            if (category.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                category,
                                style: textTheme.labelLarge?.copyWith(
                                  color: scheme.primary,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      if (rating.isNotEmpty && rating != '0.00')
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: scheme.tertiaryContainer,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.star_rounded, size: 18, color: scheme.onTertiaryContainer),
                              const SizedBox(width: 4),
                              Text(
                                rating,
                                style: textTheme.labelLarge?.copyWith(
                                  fontWeight: FontWeight.w800,
                                  color: scheme.onTertiaryContainer,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Icon(Icons.storefront_rounded, size: 18, color: scheme.onSurfaceVariant),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          provider.isEmpty ? 'Proveedor' : provider,
                          style: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Icon(Icons.place_outlined, size: 18, color: scheme.onSurfaceVariant),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          [district, province].where((e) => e.isNotEmpty).join(' · '),
                          style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                        ),
                      ),
                    ],
                  ),
                  if (price != null && price.isNotEmpty && price != 'null') ...[
                    const SizedBox(height: 10),
                    Text(
                      _priceLabel(price, priceType),
                      style: textTheme.labelLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: scheme.onSurface,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  static String _priceLabel(String price, String type) {
    final t = switch (type) {
      'fijo' => 'Precio',
      'desde' => 'Desde',
      'cotizar' => 'A cotizar',
      _ => 'Precio',
    };
    if (type == 'cotizar') return 'A cotizar en sitio';
    return '$t: S/ $price';
  }
}
