import 'package:chamba_app/data/api/provider_api.dart';
import 'package:chamba_app/data/models/service_request_model.dart';
import 'package:chamba_app/presentation/view_models/provider_requests_vm.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

class ProveedorSolicitudesTab extends StatelessWidget {
  const ProveedorSolicitudesTab({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => ProviderRequestsViewModel(api: ctx.read<ProviderApi>())..load(),
      child: const _SolicitudesBody(),
    );
  }
}

class _SolicitudesBody extends StatelessWidget {
  const _SolicitudesBody();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<ProviderRequestsViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return Column(children: [
      // Filtros
      SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
        child: Row(children: [
          for (final f in [
            ('all', 'Todos'),
            ('nuevo', 'Nuevos'),
            ('visto', 'Vistos'),
            ('cerrado', 'Atendidos'),
          ])
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilterChip(
                label: Text(f.$2),
                selected: vm.statusFilter == f.$1,
                onSelected: (_) => vm.setFilter(f.$1),
              ),
            ),
        ]),
      ),

      if (vm.loading)
        const Expanded(child: Center(child: CircularProgressIndicator()))
      else if (vm.filtered.isEmpty)
        Expanded(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(32),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(shape: BoxShape.circle, color: scheme.secondaryContainer),
                  child: Icon(Icons.inbox_rounded, size: 48, color: scheme.onSecondaryContainer),
                ),
                const SizedBox(height: 20),
                Text('Sin solicitudes', style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
                const SizedBox(height: 8),
                Text('Los clientes interesados en tus anuncios aparecerán aquí.',
                    textAlign: TextAlign.center,
                    style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant)),
              ]),
            ),
          ),
        )
      else
        Expanded(
          child: RefreshIndicator(
            onRefresh: vm.load,
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
              itemCount: vm.filtered.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (_, i) => _RequestCard(request: vm.filtered[i], vm: vm),
            ),
          ),
        ),
    ]);
  }
}

class _RequestCard extends StatelessWidget {
  const _RequestCard({required this.request, required this.vm});
  final ReceivedRequest request;
  final ProviderRequestsViewModel vm;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;
    final isNew = request.status == 'nuevo';
    final statusColor = _statusColor(request.status, scheme);

    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(22),
        side: isNew
            ? BorderSide(color: scheme.primary.withValues(alpha: 0.6), width: 1.5)
            : BorderSide(color: scheme.outlineVariant.withValues(alpha: 0.55)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(_statusIcon(request.status), color: statusColor, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                if (request.categoryName != null)
                  Text(request.categoryName!,
                      style: textTheme.labelSmall?.copyWith(color: scheme.primary, fontWeight: FontWeight.w700)),
                Text(request.serviceTitle ?? 'Anuncio',
                    style: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                // ── Nombre del cliente ──
                Row(children: [
                  Icon(Icons.person_rounded, size: 16, color: scheme.onSurfaceVariant),
                  const SizedBox(width: 4),
                  Text(request.clientName ?? '—',
                      style: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600)),
                ]),
                // ── Teléfono del cliente (prominente) ──
                if (request.clientPhone != null && request.clientPhone!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  GestureDetector(
                    onTap: () => _launchPhone(request.clientPhone!),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: scheme.primaryContainer,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(mainAxisSize: MainAxisSize.min, children: [
                        Icon(Icons.phone_rounded, size: 14, color: scheme.onPrimaryContainer),
                        const SizedBox(width: 6),
                        Text(request.clientPhone!,
                            style: textTheme.labelMedium?.copyWith(
                                fontWeight: FontWeight.w800, color: scheme.onPrimaryContainer)),
                      ]),
                    ),
                  ),
                ],
              ]),
            ),
            _StatusChip(status: request.status, color: statusColor),
          ]),

          if (request.message != null && request.message!.isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: scheme.surfaceContainerHighest.withValues(alpha: 0.5),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text('"${request.message}"',
                  style: textTheme.bodySmall?.copyWith(fontStyle: FontStyle.italic, color: scheme.onSurfaceVariant)),
            ),
          ],

          const SizedBox(height: 12),
          Row(children: [
            // Botones de llamar / WhatsApp si tiene teléfono
            if (request.clientPhone != null && request.clientPhone!.isNotEmpty) ...[
              _ActionChip(
                icon: Icons.call_rounded,
                label: 'Llamar',
                color: scheme.primary,
                onTap: () => _launchPhone(request.clientPhone!),
              ),
              const SizedBox(width: 8),
              _ActionChip(
                icon: Icons.chat_rounded,
                label: 'WhatsApp',
                color: const Color(0xFF25D366),
                onTap: () => _launchWhatsApp(request.clientPhone!),
              ),
              const SizedBox(width: 8),
            ],
            if (request.serviceId != null)
              _ActionChip(
                icon: Icons.open_in_new_rounded,
                label: 'Ver anuncio',
                color: scheme.secondary,
                onTap: () => context.push('/listing/${request.serviceId}'),
              ),
            const Spacer(),
            if (request.canClose)
              TextButton(
                onPressed: () => vm.markDone(request.id),
                child: const Text('Marcar atendido'),
              ),
          ]),

          if (isNew)
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: () => vm.markSeen(request.id),
                icon: const Icon(Icons.visibility_rounded, size: 16),
                label: const Text('Marcar visto'),
              ),
            ),
        ]),
      ),
    );
  }

  Color _statusColor(String status, ColorScheme scheme) => switch (status) {
    'nuevo' => scheme.primary,
    'visto' => Colors.orange,
    'cerrado' => Colors.green,
    'cancelado' => scheme.error,
    _ => scheme.onSurfaceVariant,
  };

  IconData _statusIcon(String status) => switch (status) {
    'nuevo' => Icons.fiber_new_rounded,
    'visto' => Icons.visibility_rounded,
    'cerrado' => Icons.check_circle_rounded,
    'cancelado' => Icons.cancel_rounded,
    _ => Icons.circle_outlined,
  };

  void _launchPhone(String phone) async {
    final uri = Uri(scheme: 'tel', path: phone.replaceAll(RegExp(r'\D'), ''));
    if (await canLaunchUrl(uri)) launchUrl(uri);
  }

  void _launchWhatsApp(String phone) async {
    final number = phone.replaceAll(RegExp(r'\D'), '');
    final uri = Uri.parse('https://wa.me/$number');
    if (await canLaunchUrl(uri)) launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

class _ActionChip extends StatelessWidget {
  const _ActionChip({required this.icon, required this.label, required this.color, required this.onTap});
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: color.withValues(alpha: 0.25)),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 4),
          Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: color)),
        ]),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status, required this.color});
  final String status;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Text(
        switch (status) {
          'nuevo' => 'Nuevo',
          'visto' => 'Visto',
          'cerrado' => 'Atendido',
          'cancelado' => 'Cancelado',
          _ => status,
        },
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: color),
      ),
    );
  }
}
