class CategoryModel {
  CategoryModel({required this.id, required this.name, required this.slug});

  final int id;
  final String name;
  final String slug;

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: CategoryModel._jsonInt(json['id']),
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
    );
  }

  static int _jsonInt(dynamic v) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.parse(v.toString());
  }
}
