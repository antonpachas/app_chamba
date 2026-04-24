import 'package:flutter/material.dart';

class PlaceholderTab extends StatelessWidget {
  const PlaceholderTab({
    super.key,
    required this.title,
    required this.message,
    this.icon = Icons.upcoming_rounded,
  });

  final String title;
  final String message;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: scheme.secondaryContainer.withValues(alpha: 0.75),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                'Próximamente',
                style: textTheme.labelLarge?.copyWith(
                  color: scheme.onSecondaryContainer,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.3,
                ),
              ),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    scheme.primaryContainer.withValues(alpha: 0.9),
                    scheme.surfaceContainerHighest.withValues(alpha: 0.5),
                  ],
                ),
                boxShadow: [
                  BoxShadow(
                    color: scheme.primary.withValues(alpha: 0.1),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Icon(icon, size: 48, color: scheme.onPrimaryContainer),
            ),
            const SizedBox(height: 22),
            Text(
              title,
              style: textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900, letterSpacing: -0.3),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: textTheme.bodyLarge?.copyWith(
                color: scheme.onSurfaceVariant,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 22),
            FilledButton.tonalIcon(
              onPressed: null,
              icon: const Icon(Icons.notifications_active_outlined),
              label: const Text('Te avisaremos cuando esté listo'),
            ),
          ],
        ),
      ),
    );
  }
}
