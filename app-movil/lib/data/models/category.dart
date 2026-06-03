class Category {
  const Category({required this.id, required this.name, this.slug});

  final int id;
  final String name;
  final String? slug;

  factory Category.fromJson(Map<String, dynamic> j) => Category(
    id:   j['id'] as int,
    name: j['name'] as String? ?? '',
    slug: j['slug'] as String?,
  );
}
