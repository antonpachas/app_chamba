class Endpoints {
  Endpoints._();

  // Auth
  static const login = '/auth/login';
  static const register = '/auth/register';
  static const logout = '/auth/logout';
  static const me = '/auth/me';
  static const updateMe   = '/me';
  static const uploadAvatar = '/me/avatar';
  static const forgotPassword = '/auth/forgot-password';
  static const resetPassword = '/auth/reset-password';

  // Búsqueda pública
  static const searchListings = '/listings/search';
  static String listingDetail(String id) => '/listings/$id';
  static const homeFeatured = '/listings/home-featured';

  // Catálogo
  static const categories = '/categories';
  static const categorySuggestions = '/category-suggestions';

  // Geo
  static const departments = '/geo/departments';
  static const provinces = '/geo/provinces';
  static const districts = '/geo/districts';

  // Cliente
  static const clientRequests = '/client/service-requests';
  static String clientRequestDetail(int id) => '/client/service-requests/$id';
  static String closeClientRequest(int id) => '/client/service-requests/$id/close';
  static const favorites = '/client/favorites';
  static const favoritesToggle = '/client/favorites/toggle';
  static const reviews = '/client/reviews';
  static const reviewStatus = '/client/reviews/status';

  // Proveedor
  static const providerProfile = '/provider/profile';
  static const providerServices = '/provider/services';
  static String providerServiceDetail(int id) => '/provider/services/$id';
  static String providerServiceStatus(int id) => '/provider/services/$id/status';
  static String providerServiceImages(int id) => '/provider/services/$id/images';
  static const providerRequests = '/provider/service-requests';
  static String providerRequestStatus(int id) => '/provider/service-requests/$id/status';
  static const providerDashboard = '/provider/dashboard';
  static const providerLocations              = '/provider/locations';
  static String providerLocationDetail(int id) => '/provider/locations/$id';
  static const providerNotifications = '/provider/notifications';
  static String providerRequestEvidence(int id) => '/provider/service-requests/$id/evidence';

  // Notificaciones usuario
  static const notifications = '/me/notifications';
  static String markNotificationRead(int id) => '/me/notifications/$id/read';

  // Mensajes de solicitud
  static String requestMessages(int id) => '/service-requests/$id/messages';

  // Soporte
  static const support        = '/support-tickets';
  static String supportDetail(int id) => '/support-tickets/$id';

  // Admin
  static const adminDashboard = '/admin/dashboard';
  static const adminUsers = '/admin/users';
  static String adminUserSuspend(int id) => '/admin/users/$id/suspend';
  static String adminUserActivate(int id) => '/admin/users/$id/activate';
  static const adminListings = '/admin/listings';
  static String adminListingHide(int id) => '/admin/listings/$id/hide';
  static const adminSettings = '/admin/settings';
  static const adminCategories = '/admin/categories';
}
