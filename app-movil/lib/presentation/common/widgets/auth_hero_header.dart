import 'package:flutter/material.dart';

/// Cabecera con gradiente para pantallas de acceso (login / registro).
class AuthHeroHeader extends StatelessWidget {
  const AuthHeroHeader({
    super.key,
    required this.title,
    required this.subtitle,
    this.compact = false,
  });

  final String title;
  final String subtitle;
  final bool compact;

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
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(compact ? 24 : 32)),
        boxShadow: [
          BoxShadow(
            color: scheme.primary.withValues(alpha: compact ? 0.18 : 0.28),
            blurRadius: compact ? 18 : 28,
            offset: Offset(0, compact ? 10 : 14),
          ),
        ],
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(20, compact ? 6 : 12, 20, compact ? 18 : 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: EdgeInsets.all(compact ? 10 : 12),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(compact ? 14 : 18),
                      border: Border.all(color: Colors.white.withValues(alpha: 0.25)),
                    ),
                    child: Icon(Icons.handyman_rounded, color: scheme.onPrimary, size: compact ? 24 : 28),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    'Chamba',
                    style: (compact ? textTheme.titleLarge : textTheme.headlineSmall)?.copyWith(
                      color: scheme.onPrimary,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -0.5,
                    ),
                  ),
                ],
              ),
              SizedBox(height: compact ? 14 : 22),
              Text(
                title,
                style: (compact ? textTheme.titleLarge : textTheme.headlineMedium)?.copyWith(
                  color: scheme.onPrimary,
                  fontWeight: FontWeight.w800,
                  height: 1.15,
                  letterSpacing: -0.4,
                ),
              ),
              SizedBox(height: compact ? 6 : 10),
              Text(
                subtitle,
                style: textTheme.bodyMedium?.copyWith(
                  color: scheme.onPrimary.withValues(alpha: 0.92),
                  height: 1.35,
                  fontWeight: FontWeight.w500,
                  fontSize: compact ? 14 : 16,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
