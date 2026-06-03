import 'package:go_router/go_router.dart';
import '../../ui/auth/splash_screen.dart';
import '../../ui/auth/login_screen.dart';
import '../../ui/auth/register_screen.dart';
import '../../ui/listing/listing_detail_screen.dart';
import '../../ui/shell/main_shell.dart';
import '../../providers/session_provider.dart';

GoRouter createAppRouter(SessionProvider session) => GoRouter(
  initialLocation: '/splash',
  refreshListenable: session,
  redirect: (_, state) {
    final loc = state.matchedLocation;
    final auth = session.isAuthenticated;
    final authPaths = ['/login', '/register'];

    if (loc == '/splash') return null;
    if (!auth && !authPaths.contains(loc) && loc != '/home') return '/login';
    if (auth && authPaths.contains(loc)) return '/home';
    return null;
  },
  routes: [
    GoRoute(path: '/splash',   builder: (_, __) => const SplashScreen()),
    GoRoute(path: '/login',    builder: (_, __) => const LoginScreen()),
    GoRoute(path: '/register', builder: (_, __) => const RegisterScreen()),
    GoRoute(path: '/home',     builder: (_, __) => const MainShell()),
    GoRoute(
      path: '/listing/:id',
      builder: (_, s) => ListingDetailScreen(
          listingId: s.pathParameters['id'] ?? ''),
    ),
  ],
);
