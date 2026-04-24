import 'package:flutter/material.dart';

/// Tema visual global (Material 3): colores cálidos, buen contraste y controles fáciles de pulsar.
class AppTheme {
  AppTheme._();

  /// Verde azulado claro: confianza, servicios locales, legible para mucha gente.
  static const Color _seed = Color(0xFF0F766E);

  static ThemeData light() {
    final base = ColorScheme.fromSeed(
      seedColor: _seed,
      brightness: Brightness.light,
      dynamicSchemeVariant: DynamicSchemeVariant.expressive,
    );
    final scheme = base.copyWith(
      surfaceTint: Colors.transparent,
    );

    final textTheme = ThemeData(useMaterial3: true, colorScheme: scheme).textTheme.apply(
          bodyColor: scheme.onSurface,
          displayColor: scheme.onSurface,
        );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      materialTapTargetSize: MaterialTapTargetSize.padded,
      visualDensity: VisualDensity.standard,
      scaffoldBackgroundColor: scheme.surfaceContainerLowest,
      textTheme: textTheme.copyWith(
        bodyLarge: textTheme.bodyLarge?.copyWith(fontSize: 16, height: 1.45),
        bodyMedium: textTheme.bodyMedium?.copyWith(fontSize: 15, height: 1.45),
        titleLarge: textTheme.titleLarge?.copyWith(
          fontWeight: FontWeight.w800,
          letterSpacing: -0.4,
        ),
        titleMedium: textTheme.titleMedium?.copyWith(
          fontWeight: FontWeight.w700,
          letterSpacing: -0.2,
        ),
      ),
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 2,
        centerTitle: false,
        backgroundColor: scheme.surface,
        foregroundColor: scheme.onSurface,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: TextStyle(
          fontSize: 22,
          fontWeight: FontWeight.w800,
          letterSpacing: -0.5,
          color: scheme.onSurface,
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        elevation: 0,
        height: 76,
        backgroundColor: scheme.surface,
        indicatorColor: scheme.primaryContainer,
        surfaceTintColor: Colors.transparent,
        shadowColor: scheme.shadow.withValues(alpha: 0.08),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return IconThemeData(color: scheme.onPrimaryContainer, size: 26);
          }
          return IconThemeData(color: scheme.onSurfaceVariant, size: 24);
        }),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final w = states.contains(WidgetState.selected) ? FontWeight.w800 : FontWeight.w600;
          final s = states.contains(WidgetState.selected) ? 12.5 : 12.0;
          return TextStyle(fontSize: s, fontWeight: w, letterSpacing: 0.1);
        }),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: scheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(22),
          side: BorderSide(color: scheme.outlineVariant.withValues(alpha: 0.55)),
        ),
        clipBehavior: Clip.antiAlias,
        margin: EdgeInsets.zero,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(54),
          elevation: 0,
          shadowColor: scheme.primary.withValues(alpha: 0.35),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          textStyle: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, letterSpacing: 0.15),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(50),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          side: BorderSide(color: scheme.outline.withValues(alpha: 0.9)),
          textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          minimumSize: const Size(48, 48),
          textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: scheme.surfaceContainerHighest.withValues(alpha: 0.35),
        hintStyle: TextStyle(color: scheme.onSurfaceVariant.withValues(alpha: 0.8)),
        labelStyle: TextStyle(color: scheme.onSurfaceVariant, fontWeight: FontWeight.w600, fontSize: 15),
        floatingLabelStyle: TextStyle(color: scheme.primary, fontWeight: FontWeight.w700),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: scheme.outlineVariant.withValues(alpha: 0.9)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: scheme.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: scheme.error),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      ),
      chipTheme: ChipThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        side: BorderSide(color: scheme.outlineVariant.withValues(alpha: 0.7)),
        labelStyle: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 13,
          color: scheme.onSurfaceVariant,
        ),
        secondaryLabelStyle: TextStyle(color: scheme.onSecondaryContainer),
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 0),
      ),
      listTileTheme: ListTileThemeData(
        iconColor: scheme.primary,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
        titleTextStyle: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 16,
          color: scheme.onSurface,
        ),
        subtitleTextStyle: TextStyle(
          fontSize: 14,
          height: 1.35,
          color: scheme.onSurfaceVariant,
        ),
      ),
      dividerTheme: DividerThemeData(color: scheme.outlineVariant.withValues(alpha: 0.45)),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        elevation: 4,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: ButtonStyle(
          visualDensity: VisualDensity.comfortable,
          padding: WidgetStateProperty.all(const EdgeInsets.symmetric(horizontal: 12, vertical: 14)),
          textStyle: WidgetStateProperty.all(
            const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
          ),
        ),
      ),
    );
  }
}
