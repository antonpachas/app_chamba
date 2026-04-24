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
    final textTheme = Theme.of(context).textTheme;

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            scheme.primary,
            Color.lerp(scheme.primary, scheme.tertiary, 0.4)!,
            Color.lerp(scheme.primary, Colors.black, 0.18)!,
          ],
          stops: const [0.0, 0.55, 1.0],
        ),
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(32)),
        boxShadow: [
          BoxShadow(
            color: scheme.primary.withValues(alpha: 0.28),
            blurRadius: 28,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(22, 12, 22, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: Colors.white.withValues(alpha: 0.25)),
                    ),
                    child: Icon(Icons.handyman_rounded, color: scheme.onPrimary, size: 28),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    'Chamba',
                    style: textTheme.headlineSmall?.copyWith(
                      color: scheme.onPrimary,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -0.6,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 22),
              Text(
                title,
                style: textTheme.headlineMedium?.copyWith(
                  color: scheme.onPrimary,
                  fontWeight: FontWeight.w800,
                  height: 1.12,
                  letterSpacing: -0.6,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                subtitle,
                style: textTheme.bodyLarge?.copyWith(
                  color: scheme.onPrimary.withValues(alpha: 0.92),
                  height: 1.4,
                  fontWeight: FontWeight.w500,
                  fontSize: 16,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
