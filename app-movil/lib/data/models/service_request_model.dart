/// Solicitud de contacto enviada por un cliente.
class ClientRequest {
  const ClientRequest({
    required this.id,
    required this.status,
    this.message,
    this.contactChannel,
    this.serviceTitle,
    this.categoryName,
    this.providerName,
    this.serviceId,
    this.messagesCount = 0,
    this.createdAt,
  });

  final int id;
  final String status;
  final String? message;
  final String? contactChannel;
  final String? serviceTitle;
  final String? categoryName;
  final String? providerName;
  final int? serviceId;
  final int messagesCount;
  final DateTime? createdAt;

  factory ClientRequest.fromJson(Map<String, dynamic> j) {
    final service = j['service'] as Map<String, dynamic>?;
    return ClientRequest(
      id: _parseInt(j['id']),
      status: j['status']?.toString() ?? 'nuevo',
      message: j['message']?.toString(),
      contactChannel: j['contact_channel']?.toString(),
      serviceTitle: service?['title']?.toString() ?? j['service_title']?.toString(),
      categoryName: (service?['category'] as Map?)?['name']?.toString() ?? j['category_name']?.toString(),
      providerName: j['provider_name']?.toString(),
      serviceId: service?['id'] != null ? _parseInt(service!['id']) : null,
      messagesCount: _parseInt(j['messages_count']),
      createdAt: j['created_at'] != null ? DateTime.tryParse(j['created_at'].toString()) : null,
    );
  }

  String get statusLabel => switch (status) {
    'nuevo' => 'Nuevo',
    'visto' => 'Visto',
    'cerrado' => 'Atendido',
    'cancelado' => 'Cancelado',
    _ => status,
  };
}

/// Solicitud recibida por un proveedor.
class ReceivedRequest {
  const ReceivedRequest({
    required this.id,
    required this.status,
    this.message,
    this.contactChannel,
    this.serviceTitle,
    this.categoryName,
    this.serviceId,
    this.clientName,
    this.clientPhone,
    this.messagesCount = 0,
    this.createdAt,
  });

  final int id;
  final String status;
  final String? message;
  final String? contactChannel;
  final String? serviceTitle;
  final String? categoryName;
  final int? serviceId;
  final String? clientName;
  final String? clientPhone;
  final int messagesCount;
  final DateTime? createdAt;

  factory ReceivedRequest.fromJson(Map<String, dynamic> j) {
    final service = j['service'] as Map<String, dynamic>?;
    final client = j['client'] as Map<String, dynamic>?;
    return ReceivedRequest(
      id: _parseInt(j['id']),
      status: j['status']?.toString() ?? 'nuevo',
      message: j['message']?.toString(),
      contactChannel: j['contact_channel']?.toString(),
      serviceTitle: service?['title']?.toString() ?? j['service_title']?.toString(),
      categoryName: (service?['category'] as Map?)?['name']?.toString() ?? j['category_name']?.toString(),
      serviceId: service?['id'] != null ? _parseInt(service!['id']) : null,
      clientName: client?['name']?.toString() ?? j['client_name']?.toString(),
      clientPhone: client?['phone']?.toString() ?? j['client_phone']?.toString(),
      messagesCount: _parseInt(j['messages_count']),
      createdAt: j['created_at'] != null ? DateTime.tryParse(j['created_at'].toString()) : null,
    );
  }

  String get statusLabel => switch (status) {
    'nuevo' => 'Nuevo',
    'visto' => 'Visto',
    'cerrado' => 'Atendido',
    'cancelado' => 'Cancelado',
    _ => status,
  };

  bool get canClose => status == 'nuevo' || status == 'visto';
}

int _parseInt(dynamic v) {
  if (v == null) return 0;
  if (v is int) return v;
  return int.tryParse(v.toString()) ?? 0;
}
