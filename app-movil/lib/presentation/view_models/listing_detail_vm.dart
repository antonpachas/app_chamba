import 'package:chamba_app/data/api/client_api.dart';
import 'package:chamba_app/data/api/listing_api.dart';
import 'package:chamba_app/data/models/listing_model.dart';
import 'package:flutter/foundation.dart';

class ListingDetailViewModel extends ChangeNotifier {
  ListingDetailViewModel({required this.listingApi, required this.clientApi});

  final ListingApi listingApi;
  final ClientApi clientApi;

  ListingDetail? detail;
  bool loading = false;
  String? error;

  bool sending = false;
  String? sendError;
  String? sendOk;

  bool reviewSending = false;
  String? reviewError;
  String? reviewOk;

  Future<void> load(dynamic id) async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      detail = await listingApi.show(id);
    } catch (e) {
      error = e.toString();
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<bool> sendRequest({
    required String contactChannel,
    String? message,
  }) async {
    if (detail == null) return false;
    sending = true;
    sendError = null;
    sendOk = null;
    notifyListeners();
    try {
      await clientApi.createServiceRequest(
        providerServiceId: detail!.id,
        contactChannel: contactChannel,
        message: message,
      );
      sendOk = '¡Solicitud enviada! El negocio se pondrá en contacto contigo.';
      notifyListeners();
      return true;
    } catch (e) {
      sendError = e.toString();
      notifyListeners();
      return false;
    } finally {
      sending = false;
      notifyListeners();
    }
  }

  Future<bool> submitReview({required int rating, String? comment}) async {
    if (detail == null) return false;
    reviewSending = true;
    reviewError = null;
    reviewOk = null;
    notifyListeners();
    try {
      await listingApi.storeReview(
        providerServiceId: detail!.id,
        rating: rating,
        comment: comment,
      );
      reviewOk = 'Reseña publicada. ¡Gracias!';
      notifyListeners();
      await load(detail!.id);
      return true;
    } catch (e) {
      reviewError = e.toString();
      notifyListeners();
      return false;
    } finally {
      reviewSending = false;
      notifyListeners();
    }
  }
}
