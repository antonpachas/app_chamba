import 'package:go_router/go_router.dart';
import '../../ui/admin/admin_screen.dart';
import '../../ui/admin/users_screen.dart';
import '../../ui/admin/listings_screen.dart';
import '../../ui/auth/splash_screen.dart';
import '../../ui/auth/login_screen.dart';
import '../../ui/auth/register_screen.dart';
import '../../ui/auth/forgot_password_screen.dart';
import '../../ui/cliente/requests_screen.dart';
import '../../ui/cliente/favorites_screen.dart';
import '../../ui/negocio/listings_screen.dart';
import '../../ui/negocio/requests_screen.dart';
import '../../ui/negocio/profile_screen.dart';
import '../../ui/negocio/listing_form_screen.dart';
import '../../ui/negocio/locations_screen.dart';
import '../../ui/shared/anuncio/listing_detail_screen.dart';
import '../../ui/shared/cuenta/profile_screen.dart';
import '../../ui/shared/cuenta/profile_edit_screen.dart';
import '../../ui/shared/notifications/notifications_screen.dart';
import '../../ui/shared/request/request_detail_screen.dart';
import '../../ui/shared/soporte/support_screen.dart';
import '../../ui/shared/shell/main_shell.dart';
import '../../providers/session_provider.dart';

const _guestPaths = ['/login', '/register', '/forgot-password', '/home'];

GoRouter createAppRouter(SessionProvider session) => GoRouter(
  initialLocation: '/splash',
  refreshListenable: session,
  redirect: (_, state) {
    final loc  = state.matchedLocation;
    final auth = session.isAuthenticated;

    if (loc == '/splash') return null;

    if (auth && (loc == '/login' || loc == '/register')) return '/home';

    final isPublic = _guestPaths.contains(loc) || loc.startsWith('/listing');
    if (!auth && !isPublic) return '/login';

    return null;
  },
  routes: [
    GoRoute(path: '/splash',           builder: (_, _) => const SplashScreen()),
    GoRoute(path: '/login',            builder: (_, _) => const LoginScreen()),
    GoRoute(path: '/register',         builder: (_, _) => const RegisterScreen()),
    GoRoute(path: '/forgot-password',  builder: (_, _) => const ForgotPasswordScreen()),
    GoRoute(path: '/home',             builder: (_, _) => const MainShell()),

    // Detalle de anuncio (público)
    GoRoute(
      path: '/listing/:id',
      builder: (_, s) => ListingDetailScreen(
          listingId: s.pathParameters['id'] ?? ''),
    ),

    // Cuenta
    GoRoute(path: '/account/profile',  builder: (_, _) => const ProfileScreen()),
    GoRoute(path: '/account/edit',     builder: (_, _) => const ProfileEditScreen()),

    // Notificaciones (shared)
    GoRoute(path: '/notifications',    builder: (_, _) => const NotificationsScreen()),

    // Soporte
    GoRoute(path: '/support',          builder: (_, _) => const SupportScreen()),

    // Detalle de solicitud (shared)
    GoRoute(
      path: '/requests/:id',
      builder: (_, s) => RequestDetailScreen(
          requestId: int.tryParse(s.pathParameters['id'] ?? '') ?? 0),
    ),

    // Cliente
    GoRoute(path: '/client/requests',  builder: (_, _) => const ClientRequestsScreen()),
    GoRoute(path: '/client/favorites', builder: (_, _) => const ClientFavoritesScreen()),

    // Proveedor
    GoRoute(path: '/provider/profile',  builder: (_, _) => const ProviderProfileScreen()),
    GoRoute(path: '/provider/listings', builder: (_, _) => const ProviderListingsScreen()),
    GoRoute(path: '/provider/listings/new',
        builder: (_, _) => const ListingFormScreen()),
    GoRoute(
      path: '/provider/listings/:id/edit',
      builder: (_, s) {
        // Listing data passed via extra
        final listing = s.extra as Map<String, dynamic>?;
        return ListingFormScreen(listing: listing);
      },
    ),
    GoRoute(path: '/provider/requests',   builder: (_, _) => const ProviderRequestsScreen()),
    GoRoute(path: '/provider/locations',  builder: (_, _) => const ProviderLocationsScreen()),

    // Admin
    GoRoute(path: '/admin',            builder: (_, _) => const AdminScreen()),
    GoRoute(path: '/admin/users',      builder: (_, _) => const AdminUsersScreen()),
    GoRoute(path: '/admin/listings',   builder: (_, _) => const AdminListingsScreen()),
  ],
);
