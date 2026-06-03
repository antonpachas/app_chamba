import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'chamba_app.dart';
import 'core/config/app_env.dart';
import 'core/network/api_client.dart';
import 'core/router/app_router.dart';
import 'core/storage/token_storage.dart';
import 'data/repositories/auth_repository.dart';
import 'data/repositories/client_repository.dart';
import 'data/repositories/geo_repository.dart';
import 'data/repositories/listing_repository.dart';
import 'data/repositories/provider_repository.dart';
import 'data/repositories/search_repository.dart';
import 'providers/catalog_provider.dart';
import 'providers/geo_provider.dart';
import 'providers/search_provider.dart';
import 'providers/session_provider.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Bloquea orientación portrait
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.light,
  ));

  // Dependencias
  final tokenStorage = TokenStorage();
  final apiClient    = ApiClient(tokenStorage);

  final authRepo     = AuthRepository(apiClient, tokenStorage);
  final searchRepo   = SearchRepository(apiClient);
  final geoRepo      = GeoRepository(apiClient);
  final listingRepo  = ListingRepository(apiClient);
  final clientRepo   = ClientRepository(apiClient);
  final providerRepo = ProviderRepository(apiClient);

  final session      = SessionProvider(authRepo, tokenStorage);
  final router       = createAppRouter(session);

  // Muestra la URL en debug para verificar la configuración
  assert(() {
    debugPrint('[BuscaPE] API: ${AppEnv.apiBaseUrl}');
    return true;
  }());

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: session),
        Provider.value(value: authRepo),
        Provider.value(value: listingRepo),
        Provider.value(value: clientRepo),
        Provider.value(value: providerRepo),
        ChangeNotifierProvider(create: (_) => SearchProvider(searchRepo)),
        ChangeNotifierProvider(create: (_) => GeoProvider(geoRepo)),
        ChangeNotifierProvider(create: (_) => CatalogProvider(searchRepo)),
      ],
      child: BuscaPeApp(router: router),
    ),
  );
}
