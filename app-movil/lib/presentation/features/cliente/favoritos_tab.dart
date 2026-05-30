import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:chamba_app/data/api/client_api.dart';
import 'package:chamba_app/presentation/common/widgets/service_card.dart';
import 'package:chamba_app/presentation/view_models/favorites_vm.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class FavoritosTab extends StatelessWidget {
  const FavoritosTab({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => FavoritesViewModel(api: ctx.read<ClientApi>())..load(),
      child: const _FavoritosBody(),
    );
  }
}

class _FavoritosBody extends StatelessWidget {
  const _FavoritosBody();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<FavoritesViewModel>();

    if (vm.loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (vm.items.isEmpty) {
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
              child: const Icon(Icons.favorite_border_rounded, size: 48, color: Colors.white),
            ),
            const SizedBox(height: 24),
            const Text('Sin favoritos', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppTheme.textPrimary)),
            const SizedBox(height: 8),
            const Text(
              'Guarda los negocios que más te gusten\npara encontrarlos rápidamente.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 14, color: AppTheme.textSecondary, height: 1.55),
            ),
          ]),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: vm.load,
      child: GridView.builder(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          childAspectRatio: 0.68,
        ),
        itemCount: vm.items.length,
        itemBuilder: (context, i) {
          final card = vm.items[i];
          return Stack(children: [
            ServiceCard(listing: card),
            // Botón de quitar favorito
            Positioned(
              top: 8, right: 8,
              child: GestureDetector(
                onTap: card.providerProfileId != null ? () => vm.toggleFavorite(card.providerProfileId!) : null,
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.15), blurRadius: 6)],
                  ),
                  child: const Icon(Icons.favorite_rounded, color: Colors.red, size: 18),
                ),
              ),
            ),
          ]);
        },
      ),
    );
  }
}
