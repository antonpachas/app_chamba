class Department {
  const Department({required this.id, required this.name});
  final int id;
  final String name;
  factory Department.fromJson(Map<String, dynamic> j) =>
      Department(id: j['id'] as int, name: j['name'] as String? ?? '');
}

class Province {
  const Province({required this.id, required this.name, required this.departmentId});
  final int id;
  final String name;
  final int departmentId;
  factory Province.fromJson(Map<String, dynamic> j) => Province(
    id: j['id'] as int,
    name: j['name'] as String? ?? '',
    departmentId: j['department_id'] as int? ?? 0,
  );
}

class District {
  const District({required this.id, required this.name, required this.provinceId, this.ubigeo});
  final int id;
  final String name;
  final int provinceId;
  final String? ubigeo;
  factory District.fromJson(Map<String, dynamic> j) => District(
    id:         j['id'] as int,
    name:       j['name'] as String? ?? '',
    provinceId: j['province_id'] as int? ?? 0,
    ubigeo:     j['ubigeo'] as String?,
  );
}
