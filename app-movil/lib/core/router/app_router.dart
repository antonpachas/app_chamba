import 'package:chamba_app/presentation/features/admin/admin_panel_screen.dart';
import 'package:chamba_app/presentation/features/auth/forgot_password_screen.dart';
import 'package:chamba_app/presentation/features/auth/login_screen.dart';
import 'package:chamba_app/presentation/features/auth/register_screen.dart';
import 'package:chamba_app/presentation/features/auth/reset_password_screen.dart';
import 'package:chamba_app/presentation/features/home/home_shell_screen.dart';
import 'package:chamba_app/presentation/features/listing/listing_detail_screen.dart';
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
      if (!session.isReady && loc != '/splash') return '/splash';
      if (loc == '/splash') return null;
      if (!session.canAccessHome &&
          loc != '/login' &&
          loc != '/register' &&
          loc != '/forgot-password' &&
          loc != '/reset-password') {
        return '/login';
      }
      if (session.canAccessHome && (loc == '/login' || loc == '/register')) return '/home';
      if (loc == '/admin' && !(session.user?.isAdmin ?? false)) return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/register', builder: (_, __) => const RegisterScreen()),
      GoRoute(path: '/forgot-password', builder: (_, __) => const ForgotPasswordScreen()),
      GoRoute(
        path: '/reset-password',
        builder: (_, state) => ResetPasswordScreen(
          initialEmail: state.uri.queryParameters['email'] ?? '',
          initialToken: state.uri.queryParameters['token'] ?? '',
        ),
      ),
      GoRoute(path: '/home', builder: (_, __) => const HomeShellScreen()),
      GoRoute(
        path: '/listing/:id',
        builder: (_, state) => ListingDetailScreen(listingId: state.pathParameters['id'] ?? ''),
      ),
      GoRoute(path: '/admin', builder: (_, __) => const AdminPanelScreen()),
    ],
  );
}
