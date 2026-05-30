import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:chamba_app/data/api/catalog_api.dart';
import 'package:chamba_app/data/api/listing_api.dart';
import 'package:chamba_app/data/api/search_api.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:chamba_app/presentation/common/widgets/service_card.dart';
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
        listingApi: ctx.read<ListingApi>(),
      )
        ..loadCategories()
        ..loadFeatured(),
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
  final _searchCtrl = TextEditingController();
  final _focusNode = FocusNode();

  @override
  void dispose() {
    _searchCtrl.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _doSearch(BuscarViewModel vm) {
    vm.setKeyword(_searchCtrl.text.trim());
    vm.search();
    _focusNode.unfocus();
  }

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<BuscarViewModel>();

    return CustomScrollView(
      slivers: [
        // ── App bar con buscador ──
        SliverPersistentHeader(
          pinned: true,
          delegate: _SearchBarDelegate(
            vm: vm,
            ctrl: _searchCtrl,
            focus: _focusNode,
            onSearch: () => _doSearch(vm),
          ),
        ),

        // ── Sección de destacados ──
        if (vm.featured.isNotEmpty || vm.loadingFeatured)
          SliverToBoxAdapter(
            child: _FeaturedSection(vm: vm),
          ),

        // ── Categorías grid ──
        if (!vm.loadingCategories && vm.categories.isNotEmpty)
          SliverToBoxAdapter(
            child: _CategoriesSection(vm: vm, onSearch: () => _doSearch(vm)),
          ),

        // ── Resultados / estado vacío ──
        if (vm.loadingSearch)
          const SliverFillRemaining(child: Center(child: CircularProgressIndicator()))
        else if (!vm.hasSearched)
          const SliverFillRemaining(child: _HintState())
        else if (vm.results.isEmpty)
          const SliverFillRemaining(child: _EmptyState())
        else ...[
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
            sliver: SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 14),
                child: Row(children: [
                  Text(
                    '${vm.results.length} resultado${vm.results.length != 1 ? 's' : ''}',
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppTheme.textPrimary),
                  ),
                  if (vm.selectedCategoryId != null) ...[
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        vm.categories.where((c) => c.id == vm.selectedCategoryId).map((c) => c.name).firstOrNull ?? '',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.primary),
                      ),
                    ),
                  ],
                ]),
              ),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 32),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: 0.68,
              ),
              delegate: SliverChildBuilderDelegate(
                (ctx, i) {
                  final r = vm.results[i];
                  final card = ListingCard.fromJson(r);
                  return ServiceCard(listing: card);
                },
                childCount: vm.results.length,
              ),
            ),
          ),
        ],
      ],
    );
  }
}

// ─── Barra de búsqueda persistente ───────────────────────────────────────────

class _SearchBarDelegate extends SliverPersistentHeaderDelegate {
  const _SearchBarDelegate({
    required this.vm,
    required this.ctrl,
    required this.focus,
    required this.onSearch,
  });

  final BuscarViewModel vm;
  final TextEditingController ctrl;
  final FocusNode focus;
  final VoidCallback onSearch;

  @override
  double get minExtent => 68;
  @override
  double get maxExtent => 68;

  @override
  bool shouldRebuild(covariant SliverPersistentHeaderDelegate oldDelegate) => false;

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return Container(
      color: AppTheme.primary,
      padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
      child: Row(children: [
        Expanded(
          child: Container(
            height: 48,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              boxShadow: [
                BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, 2)),
              ],
            ),
            child: TextField(
              controller: ctrl,
              focusNode: focus,
              decoration: InputDecoration(
                hintText: 'Electricistas, plomeros, pintores…',
                hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                prefixIcon: const Icon(Icons.search_rounded, color: AppTheme.textSecondary, size: 20),
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 14),
                fillColor: Colors.transparent,
                filled: true,
              ),
              textInputAction: TextInputAction.search,
              onChanged: vm.setKeyword,
              onSubmitted: (_) => onSearch(),
            ),
          ),
        ),
        const SizedBox(width: 10),
        GestureDetector(
          onTap: onSearch,
          child: Container(
            height: 48,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white.withValues(alpha: 0.3)),
            ),
            child: const Center(
              child: Text('Buscar', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 14)),
            ),
          ),
        ),
      ]),
    );
  }
}

// ─── Sección de destacados (slider) ──────────────────────────────────────────

class _FeaturedSection extends StatefulWidget {
  const _FeaturedSection({required this.vm});
  final BuscarViewModel vm;

  @override
  State<_FeaturedSection> createState() => _FeaturedSectionState();
}

class _FeaturedSectionState extends State<_FeaturedSection> {
  final int _active = 0;
  final _scrollCtrl = ScrollController();

  @override
  void dispose() {
    _scrollCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final items = widget.vm.featured;
    final loading = widget.vm.loadingFeatured;

    return Container(
      color: AppTheme.primary,
      padding: const EdgeInsets.only(bottom: 20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        // Header
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
          child: Row(children: [
            const Icon(Icons.workspace_premium_rounded, size: 18, color: Color(0xFFFBBF24)),
            const SizedBox(width: 6),
            const Text(
              'Destacados',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: -0.3),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text('Patrocinado', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Colors.white70)),
            ),
          ]),
        ),

        // Cards
        SizedBox(
          height: 210,
          child: loading
              ? const Center(child: CircularProgressIndicator(color: Colors.white54, strokeWidth: 2.5))
              : ListView.separated(
                  controller: _scrollCtrl,
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: items.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 12),
                  itemBuilder: (_, i) => FeaturedCard(listing: items[i]),
                ),
        ),

        // Dots
        if (items.length > 1)
          Padding(
            padding: const EdgeInsets.only(top: 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(items.length, (i) => AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: _active == i ? 18 : 6,
                height: 6,
                decoration: BoxDecoration(
                  color: _active == i ? Colors.white : Colors.white38,
                  borderRadius: BorderRadius.circular(3),
                ),
              )),
            ),
          ),
      ]),
    );
  }
}

// ─── Sección de categorías (grid) ────────────────────────────────────────────

class _CategoriesSection extends StatelessWidget {
  const _CategoriesSection({required this.vm, required this.onSearch});
  final BuscarViewModel vm;
  final VoidCallback onSearch;

  static const _catIcons = <String, IconData>{
    'electr': Icons.electrical_services_rounded,
    'plomer': Icons.plumbing_rounded,
    'gasfit': Icons.water_drop_rounded,
    'carpin': Icons.carpenter_rounded,
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
    'segur': Icons.security_rounded,
    'cocin': Icons.restaurant_rounded,
    'foto': Icons.camera_alt_rounded,
    'dise': Icons.design_services_rounded,
    'salud': Icons.medical_services_rounded,
    'mascot': Icons.pets_rounded,
    'event': Icons.celebration_rounded,
    'enseñ': Icons.school_rounded,
  };

  static IconData _icon(String name) {
    final lower = name.toLowerCase();
    for (final e in _catIcons.entries) {
      if (lower.contains(e.key)) return e.value;
    }
    return Icons.home_repair_service_rounded;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 18, 16, 12),
          child: Text(
            'Categorías',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppTheme.textPrimary, letterSpacing: -0.2),
          ),
        ),

        // Todos + categorías en grid wrap
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 18),
          child: Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              // "Todas"
              _CatChip(
                icon: Icons.apps_rounded,
                label: 'Todas',
                selected: vm.selectedCategoryId == null,
                onTap: () {
                  vm.setCategory(null);
                  onSearch();
                },
              ),
              // Máximo 11 categorías (+ "Ver más" implícito en scroll)
              for (final cat in vm.categories.take(11))
                _CatChip(
                  icon: _icon(cat.name),
                  label: cat.name,
                  selected: vm.selectedCategoryId == cat.id,
                  onTap: () {
                    vm.setCategory(cat.id);
                    onSearch();
                  },
                ),
            ],
          ),
        ),

        const Divider(height: 1),
      ]),
    );
  }
}

class _CatChip extends StatelessWidget {
  const _CatChip({required this.icon, required this.label, required this.selected, required this.onTap});
  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: selected ? AppTheme.primary : AppTheme.background,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: selected ? AppTheme.primary : AppTheme.border, width: selected ? 2 : 1),
          boxShadow: selected
              ? [BoxShadow(color: AppTheme.primary.withValues(alpha: 0.25), blurRadius: 8, offset: const Offset(0, 3))]
              : [],
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 16, color: selected ? Colors.white : AppTheme.primary),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: selected ? Colors.white : AppTheme.textPrimary,
            ),
          ),
        ]),
      ),
    );
  }
}

// ─── Estados vacíos ───────────────────────────────────────────────────────────

class _HintState extends StatelessWidget {
  const _HintState();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            padding: const EdgeInsets.all(28),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF003874), Color(0xFF1D6EE8)],
              ),
              boxShadow: [BoxShadow(color: AppTheme.primary.withValues(alpha: 0.3), blurRadius: 20, offset: const Offset(0, 8))],
            ),
            child: const Icon(Icons.search_rounded, size: 52, color: Colors.white),
          ),
          const SizedBox(height: 24),
          const Text(
            'Encuentra el servicio\nque necesitas',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppTheme.textPrimary, height: 1.2, letterSpacing: -0.3),
          ),
          const SizedBox(height: 10),
          const Text(
            'Escribe una palabra clave o selecciona\nuna categoría para empezar.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: AppTheme.textSecondary, height: 1.55),
          ),
        ]),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.orange.shade50,
              border: Border.all(color: Colors.orange.shade200),
            ),
            child: Icon(Icons.search_off_rounded, size: 48, color: Colors.orange.shade400),
          ),
          const SizedBox(height: 20),
          const Text('Sin resultados', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppTheme.textPrimary)),
          const SizedBox(height: 8),
          const Text(
            'Intenta con otras palabras o cambia la categoría.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: AppTheme.textSecondary, height: 1.5),
          ),
        ]),
      ),
    );
  }
}
