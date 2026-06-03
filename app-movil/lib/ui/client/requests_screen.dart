import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../data/models/service_request.dart';
import '../../data/repositories/client_repository.dart';
import '../shared/widgets/error_view.dart';

class ClientRequestsScreen extends StatefulWidget {
  const ClientRequestsScreen({super.key});

  @override
  State<ClientRequestsScreen> createState() => _ClientRequestsScreenState();
}

class _ClientRequestsScreenState extends State<ClientRequestsScreen> {
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
      _requests = await context.read<ClientRepository>().myRequests();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);
    if (_requests.isEmpty) {
      return const Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.inbox_outlined, size: 56, color: Color(0xFFCBD5E1)),
          SizedBox(height: 12),
          Text('Aún no has contactado ningún negocio',
              style: TextStyle(color: Color(0xFF64748B), fontSize: 15)),
        ]),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _requests.length,
        separatorBuilder: (_, _) => const SizedBox(height: 10),
        itemBuilder: (_, i) => _RequestTile(request: _requests[i]),
      ),
    );
  }
}

class _RequestTile extends StatelessWidget {
  const _RequestTile({required this.request});
  final ServiceRequest request;

  Color _statusColor() => switch (request.status) {
    'nuevo'    => const Color(0xFF003874),
    'visto'    => const Color(0xFF16A34A),
    'cerrado'  => const Color(0xFF64748B),
    'cancelado'=> const Color(0xFFDC2626),
    _          => const Color(0xFF64748B),
  };

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            Expanded(
              child: Text(request.listingTitle ?? 'Anuncio',
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                  maxLines: 1, overflow: TextOverflow.ellipsis),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: _statusColor().withAlpha(25),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(request.statusLabel,
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700,
                      color: _statusColor())),
            ),
          ]),
          if (request.providerName != null) ...[
            const SizedBox(height: 4),
            Text(request.providerName!,
                style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
          ],
          if (request.message != null) ...[
            const SizedBox(height: 8),
            Text(request.message!, maxLines: 2, overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 13, color: Color(0xFF374151))),
          ],
          if (request.createdAt != null) ...[
            const SizedBox(height: 8),
            Text(
              '${request.createdAt!.day}/${request.createdAt!.month}/${request.createdAt!.year}',
              style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
          ],
        ]),
      ),
    );
  }
}
