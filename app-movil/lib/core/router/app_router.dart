import 'package:chamba_app/presentation/features/auth/login_screen.dart';
import 'package:chamba_app/presentation/features/auth/register_screen.dart';
import 'package:chamba_app/presentation/features/home/home_shell_screen.dart';
import 'package:chamba_app/presentation/features/splash/splash_screen.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

GoRouter createAppRouter(SessionViewModel session) {
  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: session,
    redirect: (BuildContext context, GoRouterState state) {
      final loc = state.matchedLocation;
      if (!session.isReady && loc != '/splash') {
        return '/splash';
      }
      if (loc == '/splash') {
        return null;
      }
      if (!session.canAccessHome && loc != '/login' && loc != '/register') {
        return '/login';
      }
      if (session.canAccessHome && (loc == '/login' || loc == '/register')) {
        return '/home';
      }
      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/home',
        builder: (context, state) => const HomeShellScreen(),
      ),
    ],
  );
}
