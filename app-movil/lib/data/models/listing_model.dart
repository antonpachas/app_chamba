/// Tarjeta de anuncio (resultado de búsqueda).
class ListingCard {
  const ListingCard({
    required this.id,
    required this.title,
    this.categoryName,
    this.providerName,
    this.districtName,
    this.provinceName,
    this.avgRating,
    this.reviewsCount,
    this.basePrice,
    this.priceType,
    this.thumbnailUrl,
    this.isFeatured = false,
    this.providerProfileId,
  });

  final int id;
  final String title;
  final String? categoryName;
  final String? providerName;
  final String? districtName;
  final String? provinceName;
  final double? avgRating;
  final int? reviewsCount;
  final double? basePrice;
  final String? priceType;
  final String? thumbnailUrl;
  final bool isFeatured;
  final int? providerProfileId;

  factory ListingCard.fromJson(Map<String, dynamic> j) {
    // La API de búsqueda devuelve `service_id`, la de detalle devuelve `id`
    final rawId = j['service_id'] ?? j['id'];
    return ListingCard(
      id: _parseInt(rawId),
      title: j['title']?.toString() ?? '',
      categoryName: j['category_name']?.toString(),
      providerName: j['provider_name']?.toString(),
      districtName: j['district_name']?.toString(),
      provinceName: j['province_name']?.toString(),
      avgRating: _parseDouble(j['avg_rating']),
      reviewsCount: j['reviews_count'] != null ? _parseInt(j['reviews_count'])
          : j['total_reviews'] != null ? _parseInt(j['total_reviews']) : null,
      basePrice: _parseDouble(j['base_price']),
      priceType: j['price_type']?.toString(),
      thumbnailUrl: j['thumbnail_url']?.toString()
          ?? j['cover_image_url']?.toString()
          ?? j['image_url']?.toString(),
      isFeatured: j['is_featured'] == true || j['is_featured'] == 1
          || j['listing_type']?.toString() == 'promocion',
      providerProfileId: j['provider_profile_id'] != null
          ? _parseInt(j['provider_profile_id'])
          : j['provider_id'] != null ? _parseInt(j['provider_id']) : null,
    );
  }

  String get priceLabel {
    if (priceType == 'cotizar') return 'A cotizar';
    if (basePrice == null) return '';
    final prefix = priceType == 'desde' ? 'Desde ' : '';
    return '${prefix}S/ ${basePrice!.toStringAsFixed(2)}';
  }
}

/// Detalle completo de un anuncio.
class ListingDetail {
  const ListingDetail({
    required this.id,
    required this.title,
    this.description,
    this.categoryName,
    this.providerName,
    this.providerProfileId,
    this.districtName,
    this.provinceName,
    this.departmentName,
    this.addressText,
    this.avgRating,
    this.reviewsCount,
    this.basePrice,
    this.priceType,
    this.images = const [],
    this.contactPhone,
    this.whatsapp,
    this.openHours = const [],
    this.reviews = const [],
    this.isFeatured = false,
    this.status,
    this.guestPreview = false,
  });

  final int id;
  final String title;
  final String? description;
  final String? categoryName;
  final String? providerName;
  final int? providerProfileId;
  final String? districtName;
  final String? provinceName;
  final String? departmentName;
  final String? addressText;
  final double? avgRating;
  final int? reviewsCount;
  final double? basePrice;
  final String? priceType;
  final List<String> images;
  final String? contactPhone;
  final String? whatsapp;
  final List<Map<String, dynamic>> openHours;
  final List<ReviewModel> reviews;
  final bool isFeatured;
  final String? status;
  final bool guestPreview;

  factory ListingDetail.fromJson(Map<String, dynamic> j) {
    final imgRaw = j['images'] as List<dynamic>? ?? [];
    final images = imgRaw.map((e) {
      if (e is String) return e;
      if (e is Map) return (e['url'] ?? e['path'] ?? '').toString();
      return '';
    }).where((s) => s.isNotEmpty).toList();

    final hoursRaw = j['open_hours'] as List<dynamic>? ?? j['business_hours'] as List<dynamic>? ?? [];
    final openHours = hoursRaw.whereType<Map>().map((e) => e.cast<String, dynamic>()).toList();

    final reviewsRaw = j['reviews'] as List<dynamic>? ?? [];
    final reviews = reviewsRaw.whereType<Map>().map((e) => ReviewModel.fromJson(e.cast<String, dynamic>())).toList();

    final provider = j['provider'] as Map<String, dynamic>?;

    return ListingDetail(
      id: _parseInt(j['id']),
      title: j['title']?.toString() ?? '',
      description: j['description']?.toString(),
      categoryName: j['category_name']?.toString() ?? (j['category'] as Map?)?['name']?.toString(),
      providerName: j['provider_name']?.toString() ?? provider?['business_name']?.toString(),
      providerProfileId: j['provider_profile_id'] != null
          ? _parseInt(j['provider_profile_id'])
          : provider?['id'] != null
              ? _parseInt(provider!['id'])
              : null,
      districtName: j['district_name']?.toString() ?? (j['district'] as Map?)?['name']?.toString(),
      provinceName: j['province_name']?.toString() ?? (j['province'] as Map?)?['name']?.toString(),
      departmentName: j['department_name']?.toString(),
      addressText: j['address_text']?.toString() ?? provider?['address_text']?.toString(),
      avgRating: _parseDouble(j['avg_rating']),
      reviewsCount: j['reviews_count'] != null ? _parseInt(j['reviews_count']) : reviews.length,
      basePrice: _parseDouble(j['base_price']),
      priceType: j['price_type']?.toString(),
      images: images,
      contactPhone: j['contact_phone']?.toString() ?? provider?['contact_phone']?.toString(),
      whatsapp: j['whatsapp']?.toString() ?? provider?['whatsapp']?.toString(),
      openHours: openHours,
      reviews: reviews,
      isFeatured: j['is_featured'] == true || j['is_featured'] == 1,
      status: j['status']?.toString(),
      guestPreview: j['guest_preview'] == true || j['guest_preview'] == 1,
    );
  }

  String get priceLabel {
    if (priceType == 'cotizar') return 'A cotizar en sitio';
    if (basePrice == null) return '';
    final prefix = priceType == 'desde' ? 'Desde ' : '';
    return '${prefix}S/ ${basePrice!.toStringAsFixed(2)}';
  }

  String get locationLabel {
    return [districtName, provinceName, departmentName]
        .where((e) => e != null && e.isNotEmpty)
        .join(', ');
  }
}

/// Reseña de un servicio.
class ReviewModel {
  const ReviewModel({
    required this.id,
    required this.rating,
    this.comment,
    this.authorName,
    this.createdAt,
  });

  final int id;
  final int rating;
  final String? comment;
  final String? authorName;
  final DateTime? createdAt;

  factory ReviewModel.fromJson(Map<String, dynamic> j) {
    return ReviewModel(
      id: _parseInt(j['id']),
      rating: _parseInt(j['rating']),
      comment: j['comment']?.toString(),
      authorName: j['author_name']?.toString() ?? j['client_name']?.toString(),
      createdAt: j['created_at'] != null ? DateTime.tryParse(j['created_at'].toString()) : null,
    );
  }
}

int _parseInt(dynamic v) {
  if (v == null) return 0;
  if (v is int) return v;
  return int.tryParse(v.toString()) ?? 0;
}

double? _parseDouble(dynamic v) {
  if (v == null) return null;
  if (v is double) return v;
  if (v is int) return v.toDouble();
  return double.tryParse(v.toString());
}
