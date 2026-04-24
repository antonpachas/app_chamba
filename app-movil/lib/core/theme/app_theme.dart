import 'package:flutter/material.dart';

/// Tema visual global (Material 3): colores, tipografía y componentes.
class AppTheme {
  AppTheme._();

  static const Color _seed = Color(0xFF0D5C63);

  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(
      seedColor: _seed,
      brightness: Brightness.light,
      dynamicSchemeVariant: DynamicSchemeVariant.expressive,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: scheme.surfaceContainerLow,
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 3,
        centerTitle: false,
        backgroundColor: scheme.surface,
        foregroundColor: scheme.onSurface,
        surfaceTintColor: scheme.surfaceTint,
        titleTextStyle: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w700,
          letterSpacing: -0.3,
          color: scheme.onSurface,
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        elevation: 8,
        height: 72,
        indicatorColor: scheme.primaryContainer,
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return IconThemeData(color: scheme.onPrimaryContainer, size: 26);
          }
          return IconThemeData(color: scheme.onSurfaceVariant, size: 24);
        }),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: scheme.surface,
        surfaceTintColor: scheme.surfaceTint,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        clipBehavior: Clip.antiAlias,
        margin: EdgeInsets.zero,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          elevation: 0,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16, letterSpacing: 0.2),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: scheme.surfaceContainerHighest.withValues(alpha: 0.45),
        hintStyle: TextStyle(color: scheme.onSurfaceVariant.withValues(alpha: 0.85)),
        labelStyle: TextStyle(color: scheme.onSurfaceVariant, fontWeight: FontWeight.w500),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: scheme.outlineVariant),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: scheme.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: scheme.error),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      chipTheme: ChipThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        side: BorderSide(color: scheme.outlineVariant),
        labelStyle: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 13,
          color: scheme.onSurfaceVariant,
        ),
        secondaryLabelStyle: TextStyle(color: scheme.onSecondaryContainer),
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 0),
      ),
      listTileTheme: ListTileThemeData(
        iconColor: scheme.primary,
        titleTextStyle: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 16,
          color: scheme.onSurface,
        ),
        subtitleTextStyle: TextStyle(
          fontSize: 13,
          height: 1.35,
          color: scheme.onSurfaceVariant,
        ),
      ),
      dividerTheme: DividerThemeData(color: scheme.outlineVariant.withValues(alpha: 0.5)),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }
}
