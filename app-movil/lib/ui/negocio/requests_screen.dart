import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../data/models/service_request.dart';
import '../../data/repositories/provider_repository.dart';
import '../shared/widgets/error_view.dart';

class ProviderRequestsScreen extends StatefulWidget {
  const ProviderRequestsScreen({super.key});

  @override
  State<ProviderRequestsScreen> createState() => _ProviderRequestsScreenState();
}

class _ProviderRequestsScreenState extends State<ProviderRequestsScreen> {
  List<ServiceRequest> _requests = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      _requests = await context.read<ProviderRepository>().myRequests();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Contactos recibidos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _load,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _requests.isEmpty
                  ? const _EmptyState()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: _requests.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 10),
                        itemBuilder: (_, i) => _RequestTile(
                          request: _requests[i],
                          onTap: () => context.push('/requests/${_requests[i].id}'),
                        ),
                      ),
                    ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) => const Center(
    child: Padding(
      padding: EdgeInsets.all(32),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.inbox_outlined, size: 72, color: Color(0xFFCBD5E1)),
        SizedBox(height: 16),
        Text('Aún no recibiste contactos',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700,
                color: Color(0xFF0B1C30))),
        SizedBox(height: 8),
        Text('Cuando un cliente contacte uno de tus anuncios, aparecerá aquí.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Color(0xFF64748B), height: 1.5)),
      ]),
    ),
  );
}

class _RequestTile extends StatelessWidget {
  const _RequestTile({required this.request, this.onTap});
  final ServiceRequest request;
  final VoidCallback? onTap;

  Color get _statusColor => switch (request.status) {
    'nuevo'     => const Color(0xFF003874),
    'visto'     => const Color(0xFF0284C7),
    'cerrado'   => const Color(0xFF16A34A),
    'cancelado' => const Color(0xFFDC2626),
    _           => const Color(0xFF64748B),
  };

  IconData get _statusIcon => switch (request.status) {
    'nuevo'     => Icons.mark_email_unread_outlined,
    'visto'     => Icons.mark_email_read_outlined,
    'cerrado'   => Icons.check_circle_outline,
    'cancelado' => Icons.cancel_outlined,
    _           => Icons.help_outline,
  };

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Icon(_statusIcon, color: _statusColor, size: 20),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                request.listingTitle ?? 'Anuncio',
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
                maxLines: 1, overflow: TextOverflow.ellipsis,
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
              decoration: BoxDecoration(
                color: _statusColor.withAlpha(25),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(request.statusLabel,
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700,
                      color: _statusColor)),
            ),
          ]),
          if (request.clientName != null) ...[
            const SizedBox(height: 8),
            Row(children: [
              const Icon(Icons.person_outline, size: 15, color: Color(0xFF64748B)),
              const SizedBox(width: 4),
              Text(request.clientName!,
                  style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
            ]),
          ],
          if (request.contactChannel != null) ...[
            const SizedBox(height: 4),
            Row(children: [
              Icon(
                request.contactChannel == 'whatsapp'
                    ? Icons.chat_outlined
                    : request.contactChannel == 'telefono'
                        ? Icons.phone_outlined
                        : Icons.send_outlined,
                size: 15, color: const Color(0xFF64748B)),
              const SizedBox(width: 4),
              Text('Vía ${request.contactChannel}',
                  style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
            ]),
          ],
          if (request.message != null && request.message!.isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF8F9FF),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Text(request.message!,
                  maxLines: 3, overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 13, color: Color(0xFF374151),
                      height: 1.4)),
            ),
          ],
          if (request.createdAt != null) ...[
            const SizedBox(height: 8),
            Text(
              '${request.createdAt!.day}/${request.createdAt!.month}/${request.createdAt!.year}',
              style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
          ],
        ]),
      ),
      ),
    );
  }
}
