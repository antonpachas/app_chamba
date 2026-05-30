int _jsonInt(dynamic v) {
  if (v is int) return v;
  if (v is num) return v.toInt();
  return int.parse(v.toString());
}

class ProviderProfileModel {
  ProviderProfileModel({
    required this.id,
    this.businessName,
    this.description,
    this.whatsapp,
    this.contactPhone,
    this.addressText,
    required this.districtId,
    required this.isVerified,
    this.avgRating,
    required this.totalReviews,
  });

  final int id;
  final String? businessName;
  final String? description;
  final String? whatsapp;
  final String? contactPhone;
  final String? addressText;
  final int districtId;
  final bool isVerified;
  final String? avgRating;
  final int totalReviews;

  factory ProviderProfileModel.fromJson(Map<String, dynamic> json) {
    return ProviderProfileModel(
      id: _jsonInt(json['id']),
      businessName: json['business_name'] as String?,
      description: json['description'] as String?,
      whatsapp: json['whatsapp'] as String?,
      contactPhone: json['contact_phone'] as String?,
      addressText: json['address_text'] as String?,
      districtId: _jsonInt(json['district_id']),
      isVerified: json['is_verified'] == true || json['is_verified'] == 1,
      avgRating: json['avg_rating']?.toString(),
      totalReviews: _jsonInt(json['total_reviews'] ?? 0),
    );
  }
}

class UserModel {
  UserModel({
    required this.id,
    required this.fullName,
    required this.email,
    this.phone,
    required this.role,
    required this.status,
    this.providerProfile,
  });

  final int id;
  final String fullName;
  final String email;
  final String? phone;
  final String role;
  final String status;
  final ProviderProfileModel? providerProfile;

  bool get isCliente => role == 'cliente';
  bool get isProveedor => role == 'proveedor';
  bool get isAdmin => role == 'admin';

  factory UserModel.fromJson(Map<String, dynamic> json) {
    Map<String, dynamic>? profileMap;
    final raw = json['provider_profile'];
    if (raw is Map<String, dynamic>) {
      profileMap = raw;
    }

    return UserModel(
      id: _jsonInt(json['id']),
      fullName: json['full_name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String?,
      role: json['role'] as String? ?? 'cliente',
      status: json['status'] as String? ?? 'activo',
      providerProfile: profileMap != null ? ProviderProfileModel.fromJson(profileMap) : null,
    );
  }
}
