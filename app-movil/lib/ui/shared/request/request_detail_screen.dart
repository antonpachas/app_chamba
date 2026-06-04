import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/endpoints.dart';
import '../../../data/models/service_request.dart';
import '../../../providers/session_provider.dart';
import '../../../data/repositories/provider_repository.dart';
import '../../../data/repositories/client_repository.dart';
import '../widgets/error_view.dart';

class RequestDetailScreen extends StatefulWidget {
  const RequestDetailScreen({super.key, required this.requestId});
  final int requestId;

  @override
  State<RequestDetailScreen> createState() => _RequestDetailScreenState();
}

class _RequestDetailScreenState extends State<RequestDetailScreen> {
  ServiceRequest? _request;
  List<Map<String, dynamic>> _messages = [];
  List<String> _evidenceImages = [];
  bool _loading = true;
  bool _sending = false;
  String? _error;
  bool? _alreadyReviewed;
  bool _uploadingEvidence = false;
  final _msgCtrl    = TextEditingController();
  final _scrollCtrl = ScrollController();
  final _picker     = ImagePicker();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _msgCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    final api          = context.read<ApiClient>();
    final session      = context.read<SessionProvider>();
    final providerRepo = context.read<ProviderRepository>();
    final clientRepo   = context.read<ClientRepository>();
    final isProvider   = session.user?.isProveedor == true;

    try {
      // Mensajes
      final msgsData = await api.get(Endpoints.requestMessages(widget.requestId));
      _messages = ((msgsData['data'] as List?) ?? []).cast<Map<String, dynamic>>();

      // Datos del request
      if (isProvider) {
        final reqs = await providerRepo.myRequests();
        _request = reqs.where((r) => r.id == widget.requestId).firstOrNull;
      } else {
        final reqs = await clientRepo.myRequests();
        _request = reqs.where((r) => r.id == widget.requestId).firstOrNull;
      }

      // Evidencias del proveedor (si existen en el endpoint de detalle)
      try {
        final detail = isProvider
            ? await api.get('/provider/service-requests/${widget.requestId}')
            : await api.get('/client/service-requests/${widget.requestId}');
        final data = (detail['data'] ?? detail) as Map<String, dynamic>;
        final imgs = data['evidence_images'] as List?;
        _evidenceImages = imgs?.map((e) => e.toString()).toList() ?? [];
      } catch (_) {}

      // Estado de reseña (solo cliente, solo solicitud cerrada)
      if (!isProvider && _request?.isClosed == true && _request?.providerServiceId != null) {
        try {
          final rv = await api.get(
            Endpoints.reviewStatus,
            params: {'provider_service_id': _request!.providerServiceId},
          );
          _alreadyReviewed = rv['has_review'] == true;
        } catch (_) {
          _alreadyReviewed = false;
        }
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) {
        setState(() => _loading = false);
        WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
      }
    }
  }

  void _scrollToBottom() {
    if (_scrollCtrl.hasClients) {
      _scrollCtrl.animateTo(
        _scrollCtrl.position.maxScrollExtent,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  Future<void> _send() async {
    final text = _msgCtrl.text.trim();
    if (text.isEmpty) return;
    setState(() => _sending = true);
    try {
      await context.read<ApiClient>().post(
        Endpoints.requestMessages(widget.requestId),
        data: {'message': text},
      );
      _msgCtrl.clear();
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _updateStatus(String status) async {
    try {
      await context.read<ProviderRepository>().updateRequestStatus(widget.requestId, status);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    }
  }

  Future<void> _closeRequest() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Cerrar solicitud'),
        content: const Text('¿Confirmas que deseas cerrar esta solicitud?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Cerrar')),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    try {
      await context.read<ClientRepository>().closeRequest(widget.requestId);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    }
  }

  Future<void> _uploadEvidence() async {
    final files = await _picker.pickMultiImage(imageQuality: 80, limit: 10);
    if (files.isEmpty || !mounted) return;
    setState(() => _uploadingEvidence = true);
    try {
      await context.read<ProviderRepository>().uploadRequestEvidence(
        widget.requestId,
        files.map((f) => f.path).toList(),
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Evidencias subidas correctamente')));
        await _load();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _uploadingEvidence = false);
    }
  }

  Future<void> _markDelivered() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Marcar como entregado'),
        content: const Text(
            '¿Confirmas que completaste el trabajo y quieres cerrar esta solicitud?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Sí, entregar')),
        ],
      ),
    );
    if (confirm != true) return;
    await _updateStatus('cerrado');
  }

  Future<void> _showReviewSheet() async {
    final svcId = _request?.providerServiceId;
    if (svcId == null) return;
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _ReviewSheet(
        clientRepo: context.read<ClientRepository>(),
        serviceId: svcId,
        onDone: () {
          setState(() => _alreadyReviewed = true);
          ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('¡Reseña enviada, gracias!')));
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final session    = context.watch<SessionProvider>();
    final isProvider = session.user?.isProveedor == true;
    final status     = _request?.status ?? '';
    final title      = _request?.listingTitle ?? 'Solicitud';
    final isClosed   = status == 'cerrado' || status == 'cancelado';
    final canReview  = !isProvider && _request?.isClosed == true
        && (_alreadyReviewed == false);

    return Scaffold(
      appBar: AppBar(
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: const TextStyle(fontSize: 15),
              maxLines: 1, overflow: TextOverflow.ellipsis),
          if (status.isNotEmpty) _StatusBadge(status),
        ]),
        actions: [
          if (isProvider && !isClosed) ...[
            if (status == 'nuevo')
              IconButton(
                icon: const Icon(Icons.mark_email_read_outlined),
                tooltip: 'Marcar como visto',
                onPressed: () => _updateStatus('visto'),
              ),
            _uploadingEvidence
                ? const Padding(
                    padding: EdgeInsets.all(12),
                    child: SizedBox(
                      width: 20, height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2)),
                  )
                : IconButton(
                    icon: const Icon(Icons.add_photo_alternate_outlined),
                    tooltip: 'Subir evidencias',
                    onPressed: _uploadEvidence,
                  ),
            IconButton(
              icon: const Icon(Icons.check_circle_outline),
              tooltip: 'Marcar como entregado',
              onPressed: _markDelivered,
            ),
          ],
          if (!isProvider && !isClosed)
            IconButton(
              icon: const Icon(Icons.close_rounded),
              tooltip: 'Cerrar solicitud',
              onPressed: _closeRequest,
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : Column(children: [
                  Expanded(
                    child: ListView(
                      controller: _scrollCtrl,
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 12),
                      children: [
                        // Evidencias del proveedor
                        if (_evidenceImages.isNotEmpty) ...[
                          _SectionLabel('Evidencias del trabajo'),
                          SizedBox(
                            height: 120,
                            child: ListView.separated(
                              scrollDirection: Axis.horizontal,
                              itemCount: _evidenceImages.length,
                              separatorBuilder: (_, _) =>
                                  const SizedBox(width: 8),
                              itemBuilder: (_, i) => ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: Image.network(
                                  _evidenceImages[i],
                                  width: 120, height: 120,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) => Container(
                                    width: 120, height: 120,
                                    color: const Color(0xFFE8EEF6),
                                    child: const Icon(Icons.broken_image_outlined,
                                        color: Color(0xFFA0B8D4)),
                                  ),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                        ],

                        // Mensajes
                        if (_messages.isEmpty)
                          const Center(
                            child: Padding(
                              padding: EdgeInsets.symmetric(vertical: 32),
                              child: Text('Aún no hay mensajes.',
                                  style: TextStyle(color: Color(0xFF94A3B8))),
                            ),
                          )
                        else
                          ..._messages.map((msg) {
                            final isMine =
                                msg['sender_id'] == session.user?.id;
                            return _MessageBubble(
                              message: msg['message'] as String? ?? '',
                              senderName:
                                  msg['sender_name'] as String? ?? '',
                              isMine: isMine,
                              createdAt: msg['created_at'] as String?,
                            );
                          }),
                      ],
                    ),
                  ),

                  // Banner reseña
                  if (canReview)
                    _ReviewBanner(onTap: _showReviewSheet),

                  // Input bar o mensaje de cerrado
                  if (!isClosed)
                    _InputBar(
                        controller: _msgCtrl,
                        sending: _sending,
                        onSend: _send)
                  else
                    Container(
                      padding: const EdgeInsets.all(14),
                      color: const Color(0xFFF1F5F9),
                      child: const Center(
                        child: Text('Esta solicitud está cerrada.',
                            style: TextStyle(
                                fontSize: 13, color: Color(0xFF64748B))),
                      ),
                    ),
                ]),
    );
  }
}

// ---------------------------------------------------------------------------
// Review banner
// ---------------------------------------------------------------------------
class _ReviewBanner extends StatelessWidget {
  const _ReviewBanner({required this.onTap});
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      color: const Color(0xFFFFF7ED),
      child: Row(children: [
        const Icon(Icons.star_rounded, color: Color(0xFFF59E0B), size: 22),
        const SizedBox(width: 10),
        const Expanded(
          child: Text('¿Cómo estuvo el servicio? Deja tu reseña',
              style: TextStyle(fontSize: 13, color: Color(0xFFB45309),
                  fontWeight: FontWeight.w600)),
        ),
        const Icon(Icons.chevron_right_rounded, color: Color(0xFFB45309)),
      ]),
    ),
  );
}

// ---------------------------------------------------------------------------
// Review bottom sheet
// ---------------------------------------------------------------------------
class _ReviewSheet extends StatefulWidget {
  const _ReviewSheet({
    required this.clientRepo,
    required this.serviceId,
    required this.onDone,
  });
  final ClientRepository clientRepo;
  final int serviceId;
  final VoidCallback onDone;

  @override
  State<_ReviewSheet> createState() => _ReviewSheetState();
}

class _ReviewSheetState extends State<_ReviewSheet> {
  int _rating = 0;
  final _commentCtrl = TextEditingController();
  bool _sending = false;
  String? _error;

  @override
  void dispose() {
    _commentCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_rating == 0) {
      setState(() => _error = 'Selecciona una calificación');
      return;
    }
    setState(() { _sending = true; _error = null; });
    try {
      await widget.clientRepo.submitReview(
        serviceId: widget.serviceId,
        rating: _rating,
        comment: _commentCtrl.text.trim().isEmpty ? null : _commentCtrl.text.trim(),
      );
      if (mounted) {
        Navigator.of(context).pop();
        widget.onDone();
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _sending = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + bottom),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
          width: 40, height: 4,
          margin: const EdgeInsets.only(bottom: 16),
          decoration: BoxDecoration(
              color: const Color(0xFFCBD5E1),
              borderRadius: BorderRadius.circular(2)),
        ),
        const Text('Califica el servicio',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800,
                color: Color(0xFF0B1C30))),
        const SizedBox(height: 20),

        // Estrellas
        Row(mainAxisAlignment: MainAxisAlignment.center, children: List.generate(5, (i) {
          final star = i + 1;
          return GestureDetector(
            onTap: () => setState(() => _rating = star),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 6),
              child: Icon(
                star <= _rating ? Icons.star_rounded : Icons.star_outline_rounded,
                size: 40,
                color: const Color(0xFFF59E0B),
              ),
            ),
          );
        })),
        const SizedBox(height: 6),
        Text(
          _rating == 0 ? 'Toca para calificar'
              : _rating == 1 ? 'Muy malo'
              : _rating == 2 ? 'Malo'
              : _rating == 3 ? 'Regular'
              : _rating == 4 ? 'Bueno'
              : 'Excelente',
          style: TextStyle(
            fontSize: 13,
            color: _rating == 0
                ? const Color(0xFF94A3B8)
                : const Color(0xFFF59E0B),
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 20),

        TextField(
          controller: _commentCtrl,
          maxLines: 3,
          textCapitalization: TextCapitalization.sentences,
          decoration: InputDecoration(
            hintText: 'Comentario opcional (máx. 500 caracteres)',
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            contentPadding: const EdgeInsets.all(14),
          ),
          maxLength: 500,
        ),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Text(_error!,
                style: const TextStyle(color: Color(0xFFDC2626), fontSize: 13)),
          ),
        const SizedBox(height: 12),

        SizedBox(
          width: double.infinity,
          height: 50,
          child: FilledButton.icon(
            onPressed: _sending ? null : _submit,
            icon: _sending
                ? const SizedBox(width: 18, height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.send_outlined),
            label: Text(_sending ? 'Enviando...' : 'Enviar reseña'),
            style: FilledButton.styleFrom(backgroundColor: const Color(0xFF003874)),
          ),
        ),
      ]),
    );
  }
}

// ---------------------------------------------------------------------------
// Helpers UI
// ---------------------------------------------------------------------------
class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.label);
  final String label;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 10),
    child: Text(label,
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700,
            color: Color(0xFF64748B))),
  );
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge(this.status);
  final String status;

  Color get _color => switch (status) {
    'nuevo'    => const Color(0xFF003874),
    'visto'    => const Color(0xFF0284C7),
    'cerrado'  => const Color(0xFF16A34A),
    'cancelado'=> const Color(0xFFDC2626),
    _          => const Color(0xFF64748B),
  };

  String get _label => switch (status) {
    'nuevo'    => 'Nuevo',
    'visto'    => 'Visto',
    'cerrado'  => 'Cerrado',
    'cancelado'=> 'Cancelado',
    _          => status,
  };

  @override
  Widget build(BuildContext context) => Text(
    _label,
    style: TextStyle(fontSize: 11, color: _color, fontWeight: FontWeight.w600),
  );
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({
    required this.message,
    required this.senderName,
    required this.isMine,
    this.createdAt,
  });

  final String message, senderName;
  final bool isMine;
  final String? createdAt;

  String get _timeLabel {
    if (createdAt == null) return '';
    final dt = DateTime.tryParse(createdAt!);
    if (dt == null) return '';
    return '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
        constraints: BoxConstraints(
            maxWidth: MediaQuery.of(context).size.width * 0.75),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: isMine ? const Color(0xFF003874) : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(isMine ? 16 : 4),
            bottomRight: Radius.circular(isMine ? 4 : 16),
          ),
          border: isMine ? null : Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(color: Colors.black.withAlpha(13),
                blurRadius: 4, offset: const Offset(0, 2)),
          ],
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          if (!isMine) ...[
            Text(senderName,
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700,
                    color: Color(0xFF003874))),
            const SizedBox(height: 2),
          ],
          Text(message,
              style: TextStyle(fontSize: 14, height: 1.4,
                  color: isMine ? Colors.white : const Color(0xFF0B1C30))),
          if (_timeLabel.isNotEmpty) ...[
            const SizedBox(height: 4),
            Align(
              alignment: Alignment.centerRight,
              child: Text(_timeLabel,
                  style: TextStyle(fontSize: 10,
                      color: isMine
                          ? Colors.white.withAlpha(180)
                          : const Color(0xFF94A3B8))),
            ),
          ],
        ]),
      ),
    );
  }
}

class _InputBar extends StatelessWidget {
  const _InputBar({
    required this.controller,
    required this.sending,
    required this.onSend,
  });

  final TextEditingController controller;
  final bool sending;
  final VoidCallback onSend;

  @override
  Widget build(BuildContext context) => Container(
    decoration: const BoxDecoration(
      color: Colors.white,
      border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
    ),
    padding: EdgeInsets.only(
      left: 12, right: 12, top: 8,
      bottom: 8 + MediaQuery.of(context).padding.bottom,
    ),
    child: Row(children: [
      Expanded(
        child: TextField(
          controller: controller,
          minLines: 1,
          maxLines: 4,
          textCapitalization: TextCapitalization.sentences,
          decoration: InputDecoration(
            hintText: 'Escribe un mensaje...',
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(24),
                borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(24),
                borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            filled: true,
            fillColor: const Color(0xFFF8F9FF),
          ),
          onSubmitted: (_) => onSend(),
        ),
      ),
      const SizedBox(width: 8),
      sending
          ? const SizedBox(
              width: 44, height: 44,
              child: Center(child: CircularProgressIndicator(strokeWidth: 2)))
          : IconButton.filled(
              onPressed: onSend,
              icon: const Icon(Icons.send_rounded),
              style: IconButton.styleFrom(
                  backgroundColor: const Color(0xFF003874),
                  foregroundColor: Colors.white),
            ),
    ]),
  );
}
