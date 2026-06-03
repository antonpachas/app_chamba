class ServiceRequest {
  const ServiceRequest({
    required this.id,
    required this.status,
    this.message,
    this.contactChannel,
    this.providerServiceId,
    this.listingTitle,
    this.providerName,
    this.clientName,
    this.createdAt,
  });

  final int id;
  final String status;
  final String? message;
  final String? contactChannel;
  final int? providerServiceId;
  final String? listingTitle;
  final String? providerName;
  final String? clientName;
  final DateTime? createdAt;

  bool get isNew    => status == 'nuevo';
  bool get isSeen   => status == 'visto';
  bool get isClosed => status == 'cerrado';

  String get statusLabel => switch (status) {
    'nuevo'    => 'Nuevo',
    'visto'    => 'Visto',
    'cerrado'  => 'Cerrado',
    'cancelado'=> 'Cancelado',
    _          => status,
  };

  factory ServiceRequest.fromJson(Map<String, dynamic> j) => ServiceRequest(
    id:               (j['id'] as num?)?.toInt() ?? 0,
    status:           j['status'] as String? ?? 'nuevo',
    message:          j['message'] as String?,
    contactChannel:   j['contact_channel'] as String?,
    providerServiceId:(j['provider_service_id'] as num?)?.toInt(),
    listingTitle:     j['listing_title'] as String?
                      ?? (j['service'] as Map?)?['title'] as String?,
    providerName:     j['provider_name'] as String?,
    clientName:       j['client_name'] as String?,
    createdAt:        j['created_at'] != null
                      ? DateTime.tryParse(j['created_at'] as String)
                      : null,
  );
}
