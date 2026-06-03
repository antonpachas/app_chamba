class User {
  const User({
    required this.id,
    required this.fullName,
    required this.email,
    required this.role,
    required this.status,
    this.phone,
    this.avatarUrl,
  });

  final int id;
  final String fullName;
  final String email;
  final String role;
  final String status;
  final String? phone;
  final String? avatarUrl;

  bool get isCliente   => role == 'cliente';
  bool get isProveedor => role == 'proveedor';
  bool get isAdmin     => role == 'admin';
  bool get isActive    => status == 'activo';

  factory User.fromJson(Map<String, dynamic> j) => User(
    id:        j['id'] as int,
    fullName:  j['full_name'] as String? ?? '',
    email:     j['email'] as String? ?? '',
    role:      j['role'] as String? ?? 'cliente',
    status:    j['status'] as String? ?? 'activo',
    phone:     j['phone'] as String?,
    avatarUrl: j['avatar_url'] as String?,
  );
}
