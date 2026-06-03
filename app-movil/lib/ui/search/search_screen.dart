import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/catalog_provider.dart';
import '../../providers/search_provider.dart';
import '../../providers/session_provider.dart';
import '../shared/widgets/listing_card.dart';
import '../shared/widgets/error_view.dart';
import 'widgets/search_bar_widget.dart';
import 'widgets/geo_filter_sheet.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CatalogProvider>().ensureLoaded();
      context.read<SearchProvider>().loadFeatured();
    });
  }

  void _runSearch() => context.read<SearchProvider>().search();

  void _openGeoFilter() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (_) => const GeoFilterSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FF),
      body: SafeArea(
        child: Column(
          children: [
            // Header + buscador
            Container(
              color: const Color(0xFF003874),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Busca PE',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                      letterSpacing: -0.3,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Encuentra negocios cerca de ti',
                    style: TextStyle(fontSize: 13, color: Color(0xFFBFD7F5)),
                  ),
                  const SizedBox(height: 14),
                  SearchBarWidget(
                    onSearch: _runSearch,
                    onGeoTap: _openGeoFilter,
                  ),
                ],
              ),
            ),
            // Categorías
            const _CategoryChips(),
            // Resultados
            const Expanded(child: _Results()),
          ],
        ),
      ),
    );
  }
}

class _CategoryChips extends StatelessWidget {
  const _CategoryChips();

  @override
  Widget build(BuildContext context) {
    final catalog = context.watch<CatalogProvider>();
    final search = context.watch<SearchProvider>();

    return SizedBox(
      height: 44,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        children: [
          FilterChip(
            label: const Text('Todo'),
            selected: search.categoryId == null,
            onSelected: (_) {
              context.read<SearchProvider>().setCategory(null);
              context.read<SearchProvider>().search();
            },
          ),
          const SizedBox(width: 6),
          ...catalog.categories.map(
            (cat) => Padding(
              padding: const EdgeInsets.only(right: 6),
              child: FilterChip(
                label: Text(cat.name),
                selected: search.categoryId == cat.id,
                onSelected: (_) {
                  context.read<SearchProvider>().setCategory(cat.id);
                  context.read<SearchProvider>().search();
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Results extends StatelessWidget {
  const _Results();

  @override
  Widget build(BuildContext context) {
    final search = context.watch<SearchProvider>();
    final session = context.watch<SessionProvider>();

    if (search.status == SearchStatus.loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (search.status == SearchStatus.error) {
      return ErrorView(
        message: search.error ?? 'Error al buscar',
        onRetry: () {
          context.read<SearchProvider>().search();
        },
      );
    }

    // Estado inicial: mostrar destacados
    if (search.status == SearchStatus.idle) {
      return _Featured(featured: search.featured);
    }

    if (search.results.isEmpty) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.search_off_rounded, size: 56, color: Color(0xFFCBD5E1)),
            SizedBox(height: 12),
            Text(
              'Sin resultados para tu búsqueda',
              style: TextStyle(color: Color(0xFF64748B), fontSize: 15),
            ),
          ],
        ),
      );
    }

    return Column(
      children: [
        // Banner guest
        if (search.isGuest && session.isGuest)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            color: const Color(0xFFFFF7ED),
            child: Row(
              children: [
                const Icon(
                  Icons.info_outline,
                  color: Color(0xFFB45309),
                  size: 18,
                ),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'Inicia sesión para ver más resultados y contactar',
                    style: TextStyle(fontSize: 12, color: Color(0xFFB45309)),
                  ),
                ),
                TextButton(
                  onPressed: () => context.push('/login'),
                  child: const Text('Entrar'),
                ),
              ],
            ),
          ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${search.total} resultado${search.total == 1 ? '' : 's'}',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF64748B),
                ),
              ),
              _SortButton(),
            ],
          ),
        ),
        Expanded(
          child: GridView.builder(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 0.72,
            ),
            itemCount: search.results.length,
            itemBuilder: (_, i) {
              final listing = search.results[i];
              return ListingCard(
                listing: listing,
                onTap: () => context.push('/listing/${listing.routeId}'),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _Featured extends StatelessWidget {
  const _Featured({required this.featured});
  final List featured;

  @override
  Widget build(BuildContext context) {
    if (featured.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.storefront_outlined,
              size: 64,
              color: Color(0xFFCBD5E1),
            ),
            const SizedBox(height: 16),
            const Text(
              'Buscá lo que necesitás',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: Color(0xFF64748B),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Tiendas, talleres, servicios y más',
              style: TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
            ),
          ],
        ),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Text(
            'Destacados',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Color(0xFF0B1C30),
            ),
          ),
        ),
        Expanded(
          child: GridView.builder(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 0.72,
            ),
            itemCount: featured.length,
            itemBuilder: (_, i) {
              final l = featured[i];
              return ListingCard(
                listing: l,
                onTap: () => context.push('/listing/${l.routeId}'),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _SortButton extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final search = context.watch<SearchProvider>();
    return PopupMenuButton<String>(
      initialValue: search.useGps ? 'nearest' : 'recent',
      onSelected: (v) {
        context.read<SearchProvider>().setSort(v);
        context.read<SearchProvider>().search();
      },
      itemBuilder: (_) => const [
        PopupMenuItem(value: 'recent', child: Text('Más recientes')),
        PopupMenuItem(value: 'nearest', child: Text('Más cercanos')),
        PopupMenuItem(value: 'rating', child: Text('Mejor valorados')),
      ],
      child: const Row(
        children: [
          Icon(Icons.sort_rounded, size: 18, color: Color(0xFF003874)),
          SizedBox(width: 4),
          Text(
            'Ordenar',
            style: TextStyle(
              fontSize: 13,
              color: Color(0xFF003874),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
