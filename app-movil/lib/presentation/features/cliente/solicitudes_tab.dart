import 'package:chamba_app/data/api/client_api.dart';
import 'package:chamba_app/data/models/service_request_model.dart';
import 'package:chamba_app/presentation/view_models/client_requests_vm.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class SolicitudesTab extends StatelessWidget {
  const SolicitudesTab({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (ctx) => ClientRequestsViewModel(api: ctx.read<ClientApi>())..load(),
      child: const _SolicitudesBody(),
    );
  }
}

class _SolicitudesBody extends StatelessWidget {
  const _SolicitudesBody();

  @override
  Widget build(BuildContext context) {
    final vm = context.watch<ClientRequestsViewModel>();
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    if (vm.loading) return const Center(child: CircularProgressIndicator());

    if (vm.items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(shape: BoxShape.circle, color: scheme.secondaryContainer),
              child: Icon(Icons.send_rounded, size: 48, color: scheme.onSecondaryContainer),
            ),
            const SizedBox(height: 20),
            Text('Sin solicitudes', style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
            const SizedBox(height: 8),
            Text('Cuando contactes un negocio, tus solicitudes aparecerán aquí.',
                textAlign: TextAlign.center,
                style: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant)),
          ]),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: vm.load,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        itemCount: vm.items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (_, i) => _RequestCard(request: vm.items[i], vm: vm),
      ),
    );
  }
}

class _RequestCard extends StatelessWidget {
  const _RequestCard({required this.request, required this.vm});
  final ClientRequest request;
  final ClientRequestsViewModel vm;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;
    final statusColor = _statusColor(request.status, scheme);

    return Card(
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
                Text(request.serviceTitle ?? 'Servicio', style: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                if (request.providerName != null)
                  Text(request.providerName!, style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant)),
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
          const SizedBox(height: 10),
          Row(children: [
            Icon(Icons.chat_bubble_outline_rounded, size: 16, color: scheme.onSurfaceVariant),
            const SizedBox(width: 4),
            Text('${request.messagesCount} mensaje(s)',
                style: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant)),
            const Spacer(),
            if (request.createdAt != null)
              Text(_formatDate(request.createdAt!), style: textTheme.bodySmall?.copyWith(color: scheme.outlineVariant)),
          ]),
          if (request.serviceId != null) ...[
            const SizedBox(height: 8),
            Row(children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => context.push('/listing/${request.serviceId}'),
                  icon: const Icon(Icons.open_in_new_rounded, size: 16),
                  label: const Text('Ver anuncio'),
                ),
              ),
              if (request.status != 'cerrado' && request.status != 'cancelado') ...[
                const SizedBox(width: 8),
                OutlinedButton(
                  onPressed: () => _confirmClose(context, vm, request.id),
                  style: OutlinedButton.styleFrom(foregroundColor: scheme.error),
                  child: const Text('Cerrar'),
                ),
              ],
            ]),
          ],
        ]),
      ),
    );
  }

  void _confirmClose(BuildContext context, ClientRequestsViewModel vm, int id) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('¿Cerrar solicitud?'),
        content: const Text('Marcarás esta solicitud como resuelta y no podrás reabrirla.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancelar')),
          FilledButton(
            onPressed: () {
              Navigator.pop(ctx);
              vm.close(id);
            },
            child: const Text('Confirmar'),
          ),
        ],
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

  String _formatDate(DateTime d) {
    return '${d.day}/${d.month}/${d.year}';
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
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
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
