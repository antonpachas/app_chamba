class Category {
  const Category({required this.id, required this.name, this.slug});

  final int id;
  final String name;
  final String? slug;

  factory Category.fromJson(Map<String, dynamic> j) => Category(
    id:   j['id'] is int ? j['id'] as int : int.tryParse(j['id'].toString()) ?? 0,
    name: j['name'] as String? ?? '',
    slug: j['slug'] as String?,
  );
}
