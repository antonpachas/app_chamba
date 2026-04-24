import 'package:flutter/material.dart';

/// Cabecera con gradiente para pantallas de acceso (login / registro).
class AuthHeroHeader extends StatelessWidget {
  const AuthHeroHeader({
    super.key,
    required this.title,
    required this.subtitle,
  });

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(24, 40, 24, 36),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            scheme.primary,
            Color.lerp(scheme.primary, scheme.tertiary, 0.45)!,
          ],
        ),
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(28)),
        boxShadow: [
          BoxShadow(
            color: scheme.primary.withValues(alpha: 0.35),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(Icons.handyman_rounded, color: scheme.onPrimary, size: 28),
              ),
              const SizedBox(width: 12),
              Text(
                'Chamba',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: scheme.onPrimary,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.5,
                    ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Text(
            title,
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                  color: scheme.onPrimary,
                  fontWeight: FontWeight.w700,
                  height: 1.15,
                ),
          ),
          const SizedBox(height: 8),
          Text(
            subtitle,
            style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                  color: scheme.onPrimary.withValues(alpha: 0.88),
                  height: 1.35,
                ),
          ),
        ],
      ),
    );
  }
}
