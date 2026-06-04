import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';
import '../shared/widgets/error_view.dart';

class AdminUsersScreen extends StatefulWidget {
  const AdminUsersScreen({super.key});

  @override
  State<AdminUsersScreen> createState() => _AdminUsersScreenState();
}

class _AdminUsersScreenState extends State<AdminUsersScreen> {
  List<Map<String, dynamic>> _users = [];
  bool _loading = true;
  String? _error;
  int? _actingId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    final api = context.read<ApiClient>();
    try {
      final data = await api.get(Endpoints.adminUsers);
      _users = ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _toggleSuspend(Map<String, dynamic> user) async {
    final id      = user['id'] as int;
    final active  = user['status'] == 'activo';
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(active ? 'Suspender usuario' : 'Activar usuario'),
        content: Text(active
            ? '¿Suspender a ${user['full_name']}?'
            : '¿Activar a ${user['full_name']}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar')),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
                backgroundColor: active
                    ? const Color(0xFFDC2626) : const Color(0xFF16A34A)),
            child: Text(active ? 'Suspender' : 'Activar'),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    setState(() => _actingId = id);
    final api = context.read<ApiClient>();
    try {
      final endpoint = active
          ? Endpoints.adminUserSuspend(id)
          : Endpoints.adminUserActivate(id);
      await api.post(endpoint);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _actingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Usuarios'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _users.isEmpty
                  ? const Center(child: Text('No hay usuarios.'))
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: _users.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (_, i) {
                          final u = _users[i];
                          final id = u['id'] as int? ?? 0;
                          return _UserTile(
                            user: u,
                            acting: _actingId == id,
                            onToggle: () => _toggleSuspend(u),
                          );
                        },
                      ),
                    ),
    );
  }
}

class _UserTile extends StatelessWidget {
  const _UserTile({
    required this.user,
    required this.acting,
    required this.onToggle,
  });

  final Map<String, dynamic> user;
  final bool acting;
  final VoidCallback onToggle;

  @override
  Widget build(BuildContext context) {
    final name   = user['full_name'] as String? ?? '—';
    final email  = user['email'] as String? ?? '';
    final role   = user['role'] as String? ?? '';
    final status = user['status'] as String? ?? '';
    final active = status == 'activo';

    Color roleColor = switch (role) {
      'admin'     => const Color(0xFF7C3AED),
      'proveedor' => const Color(0xFF003874),
      _           => const Color(0xFF16A34A),
    };

    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      leading: CircleAvatar(
        backgroundColor: const Color(0xFFDBEAFF),
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: const TextStyle(fontWeight: FontWeight.w800,
              color: Color(0xFF003874)),
        ),
      ),
      title: Text(name,
          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
      subtitle: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(email, style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
        const SizedBox(height: 4),
        Row(children: [
          _Chip(label: _roleLabel(role), color: roleColor),
          const SizedBox(width: 6),
          _Chip(
            label: active ? 'Activo' : 'Suspendido',
            color: active ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
          ),
        ]),
      ]),
      trailing: acting
          ? const SizedBox(width: 24, height: 24,
              child: CircularProgressIndicator(strokeWidth: 2))
          : role != 'admin'
              ? IconButton(
                  icon: Icon(
                    active ? Icons.block_rounded : Icons.check_circle_outline,
                    color: active
                        ? const Color(0xFFDC2626) : const Color(0xFF16A34A),
                  ),
                  tooltip: active ? 'Suspender' : 'Activar',
                  onPressed: onToggle,
                )
              : null,
    );
  }

  String _roleLabel(String role) => switch (role) {
    'admin'     => 'Admin',
    'proveedor' => 'Negocio',
    _           => 'Cliente',
  };
}

class _Chip extends StatelessWidget {
  const _Chip({required this.label, required this.color});
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
    decoration: BoxDecoration(
      color: color.withAlpha(25),
      borderRadius: BorderRadius.circular(20),
    ),
    child: Text(label,
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: color)),
  );
}
