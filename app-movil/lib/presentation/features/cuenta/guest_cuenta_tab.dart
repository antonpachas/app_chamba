import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class GuestCuentaTab extends StatelessWidget {
  const GuestCuentaTab({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.read<SessionViewModel>();

    return Column(children: [
      // Header
      Container(
        width: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppTheme.primary, Color(0xFF1D5FAE)],
          ),
        ),
        padding: const EdgeInsets.fromLTRB(24, 40, 24, 32),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.person_outline_rounded, color: Colors.white, size: 32),
          ),
          const SizedBox(height: 16),
          const Text(
            'Modo invitado',
            style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: -0.5),
          ),
          const SizedBox(height: 6),
          const Text(
            'Explora sin cuenta — inicia sesión para más funciones.',
            style: TextStyle(fontSize: 14, color: Colors.white70, height: 1.4),
          ),
        ]),
      ),

      Expanded(
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            // Qué puedes hacer
            _FeatureItem(icon: Icons.search_rounded, title: 'Buscar servicios', desc: 'Explora negocios y proveedores cerca de ti.'),
            const SizedBox(height: 10),
            _FeatureItem(icon: Icons.favorite_border_rounded, title: 'Guardar favoritos', desc: 'Requiere cuenta.', locked: true),
            const SizedBox(height: 10),
            _FeatureItem(icon: Icons.send_rounded, title: 'Contactar negocios', desc: 'Requiere cuenta de cliente.', locked: true),
            const SizedBox(height: 10),
            _FeatureItem(icon: Icons.storefront_rounded, title: 'Publicar tu negocio', desc: 'Requiere cuenta de proveedor.', locked: true),

            const SizedBox(height: 28),

            FilledButton.icon(
              onPressed: () async {
                await session.exitGuestMode();
                if (context.mounted) context.go('/login');
              },
              icon: const Icon(Icons.login_rounded),
              label: const Text('Iniciar sesión'),
            ),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: () async {
                await session.exitGuestMode();
                if (context.mounted) context.go('/register');
              },
              icon: const Icon(Icons.person_add_alt_1_rounded),
              label: const Text('Crear cuenta gratis'),
            ),
            const SizedBox(height: 20),
            Center(
              child: TextButton(
                onPressed: () async {
                  await session.logout();
                  if (context.mounted) context.go('/login');
                },
                child: const Text('Salir del modo invitado'),
              ),
            ),
          ],
        ),
      ),
    ]);
  }
}

class _FeatureItem extends StatelessWidget {
  const _FeatureItem({required this.icon, required this.title, required this.desc, this.locked = false});
  final IconData icon;
  final String title;
  final String desc;
  final bool locked;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppTheme.border),
      ),
      child: Row(children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: locked ? AppTheme.background : const Color(0xFFEFF6FF),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(
            locked ? Icons.lock_rounded : icon,
            color: locked ? AppTheme.textSecondary : AppTheme.primary,
            size: 20,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: TextStyle(
            fontSize: 14, fontWeight: FontWeight.w700,
            color: locked ? AppTheme.textSecondary : AppTheme.textPrimary,
          )),
          Text(desc, style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
        ])),
        if (!locked) const Icon(Icons.check_circle_rounded, color: AppTheme.success, size: 18),
      ]),
    );
  }
}
