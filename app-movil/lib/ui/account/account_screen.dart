import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/session_provider.dart';

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionProvider>();

    if (session.isGuest) return _GuestView();
    return _AuthView(session: session);
  }
}

class _GuestView extends StatelessWidget {
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Cuenta')),
    body: Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.person_outline, size: 72, color: Color(0xFFCBD5E1)),
          const SizedBox(height: 16),
          const Text('Inicia sesión para acceder a tu cuenta',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 16, color: Color(0xFF64748B))),
          const SizedBox(height: 28),
          FilledButton(
            onPressed: () => context.push('/login'),
            style: FilledButton.styleFrom(minimumSize: const Size(200, 48)),
            child: const Text('Iniciar sesión'),
          ),
          const SizedBox(height: 12),
          OutlinedButton(
            onPressed: () => context.push('/register'),
            style: OutlinedButton.styleFrom(minimumSize: const Size(200, 48)),
            child: const Text('Crear cuenta'),
          ),
        ]),
      ),
    ),
  );
}

class _AuthView extends StatelessWidget {
  const _AuthView({required this.session});
  final SessionProvider session;

  @override
  Widget build(BuildContext context) {
    final user = session.user!;
    return Scaffold(
      appBar: AppBar(title: const Text('Mi cuenta')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // Avatar + nombre
          Center(
            child: Column(children: [
              CircleAvatar(
                radius: 40,
                backgroundColor: const Color(0xFFDBEAFF),
                backgroundImage: user.avatarUrl != null
                    ? NetworkImage(user.avatarUrl!) : null,
                child: user.avatarUrl == null
                    ? Text(user.fullName.isNotEmpty
                          ? user.fullName[0].toUpperCase() : '?',
                        style: const TextStyle(fontSize: 32,
                            fontWeight: FontWeight.w800, color: Color(0xFF003874)))
                    : null,
              ),
              const SizedBox(height: 12),
              Text(user.fullName,
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
              const SizedBox(height: 4),
              Text(user.email,
                  style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFDBEAFF),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  user.isProveedor ? 'Negocio' : user.isAdmin ? 'Admin' : 'Cliente',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700,
                      color: Color(0xFF003874)),
                ),
              ),
            ]),
          ),
          const SizedBox(height: 32),
          const Divider(),
          _Tile(icon: Icons.person_outline, label: 'Datos personales',
              onTap: () {}),
          if (user.isCliente) ...[
            _Tile(icon: Icons.inbox_outlined, label: 'Mis solicitudes',
                onTap: () {}),
            _Tile(icon: Icons.favorite_border, label: 'Favoritos',
                onTap: () {}),
          ],
          if (user.isProveedor)
            _Tile(icon: Icons.storefront_outlined, label: 'Mi perfil de negocio',
                onTap: () {}),
          const Divider(),
          _Tile(
            icon: Icons.logout_rounded,
            label: 'Cerrar sesión',
            color: const Color(0xFFDC2626),
            onTap: () async {
              final confirm = await showDialog<bool>(
                context: context,
                builder: (_) => AlertDialog(
                  title: const Text('¿Cerrar sesión?'),
                  actions: [
                    TextButton(onPressed: () => Navigator.pop(context, false),
                        child: const Text('Cancelar')),
                    FilledButton(onPressed: () => Navigator.pop(context, true),
                        child: const Text('Salir')),
                  ],
                ),
              );
              if (confirm == true && context.mounted) {
                await context.read<SessionProvider>().logout();
                if (context.mounted) context.go('/login');
              }
            },
          ),
        ],
      ),
    );
  }
}

class _Tile extends StatelessWidget {
  const _Tile({required this.icon, required this.label,
      required this.onTap, this.color});
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  @override
  Widget build(BuildContext context) => ListTile(
    leading: Icon(icon, color: color ?? const Color(0xFF003874)),
    title: Text(label,
        style: TextStyle(color: color, fontWeight: FontWeight.w600)),
    trailing: const Icon(Icons.chevron_right, color: Color(0xFFCBD5E1)),
    onTap: onTap,
  );
}
