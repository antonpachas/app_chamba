import 'package:chamba_app/chamba_app.dart';
import 'package:chamba_app/core/router/app_router.dart';
import 'package:chamba_app/data/api/api_client.dart';
import 'package:chamba_app/data/api/auth_api.dart';
import 'package:chamba_app/data/api/catalog_api.dart';
import 'package:chamba_app/data/api/admin_api.dart';
import 'package:chamba_app/data/api/client_api.dart';
import 'package:chamba_app/data/api/listing_api.dart';
import 'package:chamba_app/data/api/provider_api.dart';
import 'package:chamba_app/data/api/search_api.dart';
import 'package:chamba_app/data/local/token_storage.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final prefs = await SharedPreferences.getInstance();
  final tokenStorage = TokenStorage();
  final apiClient = ApiClient(tokenStorage);
  final dio = apiClient.dio;

  final authApi = AuthApi(dio);
  final session = SessionViewModel(authApi: authApi, tokenStorage: tokenStorage, prefs: prefs);
  final router = createAppRouter(session);

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: session),
        Provider.value(value: authApi),
        Provider.value(value: CatalogApi(dio)),
        Provider.value(value: SearchApi(dio)),
        Provider.value(value: ProviderApi(dio)),
        Provider.value(value: ClientApi(dio)),
        Provider.value(value: ListingApi(dio)),
        Provider.value(value: AdminApi(dio)),
      ],
      child: ChambaApp(router: router),
    ),
  );
}
