import 'package:chamba_app/core/theme/app_theme.dart';
import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class CuentaTab extends StatelessWidget {
  const CuentaTab({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionViewModel>();
    final u = session.user!;
    final initial = u.fullName.trim().isNotEmpty ? u.fullName.trim()[0].toUpperCase() : '?';
    final roleLabel = u.isAdmin ? 'Administrador' : u.isProveedor ? 'Proveedor' : 'Cliente';
    final roleIcon = u.isAdmin ? Icons.admin_panel_settings_rounded : u.isProveedor ? Icons.handyman_rounded : Icons.person_rounded;
    final roleColor = u.isAdmin ? Colors.purple : u.isProveedor ? AppTheme.accent : AppTheme.success;

    return ListView(
      padding: EdgeInsets.zero,
      children: [
        // Header
        Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppTheme.primary, Color(0xFF1D5FAE)],
            ),
          ),
          padding: const EdgeInsets.fromLTRB(20, 32, 20, 28),
          child: Row(children: [
            CircleAvatar(
              radius: 36,
              backgroundColor: Colors.white.withValues(alpha: 0.2),
              child: Text(
                initial,
                style: const TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(
                  u.fullName,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    color: Colors.white,
                    letterSpacing: -0.3,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  u.email,
                  style: const TextStyle(fontSize: 13, color: Colors.white70),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: roleColor.withValues(alpha: 0.25),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.3)),
                  ),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    Icon(roleIcon, size: 13, color: Colors.white),
                    const SizedBox(width: 5),
                    Text(roleLabel, style: const TextStyle(fontSize: 12, color: Colors.white, fontWeight: FontWeight.w700)),
                  ]),
                ),
              ]),
            ),
          ]),
        ),

        const SizedBox(height: 8),

        // Información
        _SectionTitle(title: 'Información'),
        _InfoCard(children: [
          _InfoTile(icon: Icons.email_outlined, title: 'Correo', value: u.email),
          if (u.phone != null && u.phone!.isNotEmpty) ...[
            const _Divider(),
            _InfoTile(icon: Icons.phone_iphone_rounded, title: 'Teléfono', value: u.phone!),
          ],
          if (u.providerProfile != null) ...[
            const _Divider(),
            _InfoTile(
              icon: Icons.storefront_rounded,
              title: 'Negocio',
              value: u.providerProfile!.businessName ?? 'Sin nombre',
            ),
            if (u.providerProfile!.contactPhone != null) ...[
              const _Divider(),
              _InfoTile(icon: Icons.call_rounded, title: 'Teléfono negocio', value: u.providerProfile!.contactPhone!),
            ],
            if (u.providerProfile!.whatsapp != null) ...[
              const _Divider(),
              _InfoTile(icon: Icons.chat_rounded, title: 'WhatsApp', value: u.providerProfile!.whatsapp!),
            ],
          ],
        ]),

        // Admin link
        if (u.isAdmin) ...[
          _SectionTitle(title: 'Administración'),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: _MenuTile(
              icon: Icons.admin_panel_settings_rounded,
              title: 'Panel de administrador',
              subtitle: 'Gestiona usuarios, anuncios y reportes',
              color: Colors.purple,
              onTap: () => context.push('/admin'),
            ),
          ),
        ],

        _SectionTitle(title: 'Sesión'),

        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: _MenuTile(
            icon: Icons.logout_rounded,
            title: 'Cerrar sesión',
            subtitle: 'Salir de tu cuenta en este dispositivo',
            color: AppTheme.error,
            onTap: () async {
              final confirm = await showDialog<bool>(
                context: context,
                builder: (ctx) => AlertDialog(
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                  title: const Text('¿Cerrar sesión?'),
                  content: const Text('Se eliminará la sesión de este dispositivo.'),
                  actions: [
                    TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancelar')),
                    FilledButton(
                      onPressed: () => Navigator.pop(ctx, true),
                      style: FilledButton.styleFrom(backgroundColor: AppTheme.error),
                      child: const Text('Salir'),
                    ),
                  ],
                ),
              );
              if (confirm == true && context.mounted) {
                await session.logout();
                if (context.mounted) context.go('/login');
              }
            },
          ),
        ),

        const SizedBox(height: 40),
        const Center(
          child: Text(
            'Busca PE v1.0',
            style: TextStyle(fontSize: 12, color: AppTheme.textSecondary),
          ),
        ),
        const SizedBox(height: 20),
      ],
    );
  }
}

// ── Widgets auxiliares ────────────────────────────────────────────────────────

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 8),
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: AppTheme.textSecondary,
          letterSpacing: 1,
        ),
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.children});
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppTheme.border),
        ),
        child: Column(children: children),
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  const _InfoTile({required this.icon, required this.title, required this.value});
  final IconData icon;
  final String title;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: const Color(0xFFEFF6FF),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 18, color: AppTheme.primary),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary, fontWeight: FontWeight.w600)),
            const SizedBox(height: 2),
            Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppTheme.textPrimary)),
          ]),
        ),
      ]),
    );
  }
}

class _MenuTile extends StatelessWidget {
  const _MenuTile({required this.icon, required this.title, required this.subtitle, required this.color, required this.onTap});
  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppTheme.border),
        ),
        child: Row(children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: color)),
              Text(subtitle, style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
            ]),
          ),
          Icon(Icons.arrow_forward_ios_rounded, size: 15, color: color.withValues(alpha: 0.6)),
        ]),
      ),
    );
  }
}

class _Divider extends StatelessWidget {
  const _Divider();

  @override
  Widget build(BuildContext context) {
    return const Divider(height: 1, indent: 16, endIndent: 16);
  }
}
