import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class ChambaApp extends StatelessWidget {
  const ChambaApp({super.key, required this.router});

  final GoRouter router;

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'Chamba',
      theme: AppTheme.light(),
      routerConfig: router,
      debugShowCheckedModeBanner: false,
    );
  }
}
