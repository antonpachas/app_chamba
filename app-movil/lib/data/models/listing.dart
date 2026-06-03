class Listing {
  const Listing({
    required this.serviceId,
    required this.title,
    this.slug,
    this.listingRef,
    this.description,
    this.categoryId,
    this.categoryName,
    this.providerProfileId,
    this.providerName,
    this.whatsapp,
    this.contactPhone,
    this.addressText,
    this.districtName,
    this.provinceName,
    this.departmentName,
    this.avgRating,
    this.totalReviews,
    this.basePrice,
    this.priceType,
    this.coverImageUrl,
    this.images = const [],
    this.distanceKm,
    this.isPro = false,
    this.isVerified = false,
    this.latitude,
    this.longitude,
    this.isOpenNow,
    this.listingType,
  });

  final int serviceId;
  final String title;
  final String? slug;
  final String? listingRef;
  final String? description;
  final int? categoryId;
  final String? categoryName;
  final int? providerProfileId;
  final String? providerName;
  final String? whatsapp;
  final String? contactPhone;
  final String? addressText;
  final String? districtName;
  final String? provinceName;
  final String? departmentName;
  final double? avgRating;
  final int? totalReviews;
  final double? basePrice;
  final String? priceType;
  final String? coverImageUrl;
  final List<String> images;
  final double? distanceKm;
  final bool isPro;
  final bool isVerified;
  final double? latitude;
  final double? longitude;
  final bool? isOpenNow;
  final String? listingType;

  String get routeId => slug ?? listingRef ?? '$serviceId';

  String get locationLine {
    final parts = [districtName, provinceName, departmentName].whereType<String>().toList();
    return parts.isNotEmpty ? parts.join(' · ') : '';
  }

  String get priceLabel {
    if (basePrice == null) return 'Consultar';
    final s = basePrice! % 1 == 0
        ? basePrice!.toInt().toString()
        : basePrice!.toStringAsFixed(2);
    return 'S/ $s';
  }

  bool get hasContact => (whatsapp?.isNotEmpty ?? false) || (contactPhone?.isNotEmpty ?? false);

  factory Listing.fromJson(Map<String, dynamic> j) {
    final imgs = (j['images'] as List?)?.cast<String>() ?? [];
    return Listing(
      serviceId:         (j['service_id'] as num?)?.toInt() ?? 0,
      title:             j['title'] as String? ?? '',
      slug:              j['slug'] as String?,
      listingRef:        j['listing_ref'] as String?,
      description:       j['description'] as String?,
      categoryId:        (j['category_id'] as num?)?.toInt(),
      categoryName:      j['category_name'] as String?,
      providerProfileId: (j['provider_profile_id'] as num?)?.toInt(),
      providerName:      j['provider_name'] as String?,
      whatsapp:          j['whatsapp'] as String?,
      contactPhone:      j['contact_phone'] as String?,
      addressText:       j['address_text'] as String?,
      districtName:      j['district_name'] as String?,
      provinceName:      j['province_name'] as String?,
      departmentName:    j['department_name'] as String?,
      avgRating:         (j['avg_rating'] as num?)?.toDouble(),
      totalReviews:      (j['total_reviews'] as num?)?.toInt(),
      basePrice:         (j['base_price'] as num?)?.toDouble(),
      priceType:         j['price_type'] as String?,
      coverImageUrl:     j['cover_image_url'] as String?,
      images:            imgs,
      distanceKm:        (j['distance_km'] as num?)?.toDouble(),
      isPro:             j['is_pro'] == true,
      isVerified:        j['is_verified'] == true,
      latitude:          (j['provider_latitude'] as num?)?.toDouble(),
      longitude:         (j['provider_longitude'] as num?)?.toDouble(),
      isOpenNow:         j['is_open_now'] as bool?,
      listingType:       j['listing_type'] as String?,
    );
  }
}
