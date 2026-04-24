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
              padding: const EdgeInsets.all(22),
              decoration: BoxDecoration(
                color: scheme.primaryContainer.withValues(alpha: 0.65),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 44, color: scheme.onPrimaryContainer),
            ),
            const SizedBox(height: 22),
            Text(
              title,
              style: textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 10),
            Text(
              message,
              textAlign: TextAlign.center,
              style: textTheme.bodyLarge?.copyWith(
                color: scheme.onSurfaceVariant,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 20),
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
