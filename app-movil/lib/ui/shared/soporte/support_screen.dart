import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/endpoints.dart';
import '../widgets/error_view.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({super.key});

  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen> {
  List<Map<String, dynamic>> _tickets = [];
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
      final data = await context.read<ApiClient>().get(
        Endpoints.support,
        params: {'per_page': 50},
      );
      _tickets = ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _openNew() async {
    final created = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _NewTicketSheet(api: context.read<ApiClient>()),
    );
    if (created == true) _load();
  }

  void _openDetail(Map<String, dynamic> ticket) {
    Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => _TicketDetailScreen(ticket: ticket),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Soporte'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openNew,
        icon: const Icon(Icons.add),
        label: const Text('Nuevo caso'),
        backgroundColor: const Color(0xFF003874),
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _tickets.isEmpty
                  ? const _EmptyState()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
                        itemCount: _tickets.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 10),
                        itemBuilder: (_, i) => _TicketTile(
                          ticket: _tickets[i],
                          onTap: () => _openDetail(_tickets[i]),
                        ),
                      ),
                    ),
    );
  }
}

// ---------------------------------------------------------------------------
// Nueva ticket bottom sheet
// ---------------------------------------------------------------------------
class _NewTicketSheet extends StatefulWidget {
  const _NewTicketSheet({required this.api});
  final ApiClient api;

  @override
  State<_NewTicketSheet> createState() => _NewTicketSheetState();
}

class _NewTicketSheetState extends State<_NewTicketSheet> {
  final _form        = GlobalKey<FormState>();
  final _subjectCtrl = TextEditingController();
  final _bodyCtrl    = TextEditingController();
  String _category   = 'otro';
  bool   _sending    = false;
  String? _error;

  static const _categories = [
    ('cuenta',   'Mi cuenta'),
    ('anuncios', 'Anuncios / publicación'),
    ('pagos',    'Pagos / membresía'),
    ('tecnico',  'Problema técnico'),
    ('otro',     'Otro'),
  ];

  @override
  void dispose() {
    _subjectCtrl.dispose();
    _bodyCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() { _sending = true; _error = null; });
    try {
      await widget.api.post(Endpoints.support, data: {
        'subject':  _subjectCtrl.text.trim(),
        'category': _category,
        'body':     _bodyCtrl.text.trim(),
      });
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _sending = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + bottom),
      child: Form(
        key: _form,
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Center(
            child: Container(
              width: 40, height: 4,
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                  color: const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(2)),
            ),
          ),
          const Text('Nuevo caso de soporte',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800,
                  color: Color(0xFF0B1C30))),
          const SizedBox(height: 20),

          // Categoría
          DropdownButtonFormField<String>(
            initialValue: _category,
            decoration: const InputDecoration(labelText: 'Categoría'),
            items: _categories.map((c) =>
                DropdownMenuItem(value: c.$1, child: Text(c.$2))).toList(),
            onChanged: (v) => setState(() => _category = v ?? 'otro'),
          ),
          const SizedBox(height: 14),

          // Asunto
          TextFormField(
            controller: _subjectCtrl,
            textCapitalization: TextCapitalization.sentences,
            decoration: const InputDecoration(labelText: 'Asunto *'),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Campo requerido' : null,
          ),
          const SizedBox(height: 14),

          // Descripción
          TextFormField(
            controller: _bodyCtrl,
            maxLines: 4,
            textCapitalization: TextCapitalization.sentences,
            decoration: const InputDecoration(
              labelText: 'Descripción *',
              alignLabelWithHint: true,
            ),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Describe el problema' : null,
          ),

          if (_error != null) ...[
            const SizedBox(height: 8),
            Text(_error!,
                style: const TextStyle(color: Color(0xFFDC2626), fontSize: 13)),
          ],
          const SizedBox(height: 20),

          SizedBox(
            width: double.infinity, height: 50,
            child: FilledButton.icon(
              onPressed: _sending ? null : _submit,
              icon: _sending
                  ? const SizedBox(width: 18, height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.send_outlined),
              label: Text(_sending ? 'Enviando...' : 'Enviar caso'),
              style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF003874)),
            ),
          ),
        ]),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Detalle del ticket
// ---------------------------------------------------------------------------
class _TicketDetailScreen extends StatefulWidget {
  const _TicketDetailScreen({required this.ticket});
  final Map<String, dynamic> ticket;

  @override
  State<_TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends State<_TicketDetailScreen> {
  late Map<String, dynamic> _ticket;
  List<Map<String, dynamic>> _messages = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _ticket = widget.ticket;
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final data = await context.read<ApiClient>().get(
        Endpoints.supportDetail(_ticket['id'] as int));
      _ticket   = ((data['data'] ?? data) as Map<String, dynamic>);
      _messages = ((_ticket['messages'] as List?) ?? [])
          .cast<Map<String, dynamic>>();
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Color _statusColor(String s) => switch (s) {
    'open'            => const Color(0xFF003874),
    'in_progress'     => const Color(0xFF0284C7),
    'waiting_user'    => const Color(0xFFF59E0B),
    'resolved'        => const Color(0xFF16A34A),
    'closed'          => const Color(0xFF64748B),
    _                 => const Color(0xFF94A3B8),
  };

  String _statusLabel(String s) => switch (s) {
    'open'            => 'Abierto',
    'in_progress'     => 'En proceso',
    'waiting_user'    => 'Esperando tu respuesta',
    'resolved'        => 'Resuelto',
    'closed'          => 'Cerrado',
    _                 => s,
  };

  @override
  Widget build(BuildContext context) {
    final status = _ticket['status'] as String? ?? '';
    return Scaffold(
      appBar: AppBar(
        title: Text(_ticket['subject'] as String? ?? 'Caso #${_ticket['id']}',
            maxLines: 1, overflow: TextOverflow.ellipsis),
        actions: [
          if (status.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: Chip(
                label: Text(_statusLabel(status),
                    style: TextStyle(
                        fontSize: 11, color: _statusColor(status),
                        fontWeight: FontWeight.w700)),
                backgroundColor: _statusColor(status).withAlpha(25),
                padding: EdgeInsets.zero,
                side: BorderSide.none,
              ),
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _messages.isEmpty
              ? Center(
                  child: Column(mainAxisSize: MainAxisSize.min, children: [
                    const Icon(Icons.support_agent_outlined,
                        size: 56, color: Color(0xFFCBD5E1)),
                    const SizedBox(height: 12),
                    Text(_ticket['body'] as String? ?? '',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                            fontSize: 14, color: Color(0xFF64748B))),
                    const SizedBox(height: 8),
                    const Text('Nuestro equipo te responderá pronto.',
                        style: TextStyle(fontSize: 13,
                            color: Color(0xFF94A3B8))),
                  ]),
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _messages.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 8),
                  itemBuilder: (_, i) {
                    final msg = _messages[i];
                    final isAdmin = msg['from_admin'] == true;
                    return Align(
                      alignment: isAdmin
                          ? Alignment.centerLeft
                          : Alignment.centerRight,
                      child: Container(
                        constraints: BoxConstraints(
                            maxWidth: MediaQuery.of(context).size.width * 0.8),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isAdmin
                              ? Colors.white
                              : const Color(0xFF003874),
                          borderRadius: BorderRadius.circular(12),
                          border: isAdmin
                              ? Border.all(color: const Color(0xFFE2E8F0))
                              : null,
                        ),
                        child: Text(msg['body'] as String? ?? '',
                            style: TextStyle(
                                fontSize: 14,
                                color: isAdmin
                                    ? const Color(0xFF0B1C30)
                                    : Colors.white,
                                height: 1.4)),
                      ),
                    );
                  },
                ),
    );
  }
}

// ---------------------------------------------------------------------------
// Tile de lista
// ---------------------------------------------------------------------------
class _TicketTile extends StatelessWidget {
  const _TicketTile({required this.ticket, required this.onTap});
  final Map<String, dynamic> ticket;
  final VoidCallback onTap;

  Color _statusColor(String s) => switch (s) {
    'open'            => const Color(0xFF003874),
    'in_progress'     => const Color(0xFF0284C7),
    'waiting_user'    => const Color(0xFFF59E0B),
    'resolved'        => const Color(0xFF16A34A),
    'closed'          => const Color(0xFF64748B),
    _                 => const Color(0xFF94A3B8),
  };

  String _statusLabel(String s) => switch (s) {
    'open'            => 'Abierto',
    'in_progress'     => 'En proceso',
    'waiting_user'    => 'Esperando tu respuesta',
    'resolved'        => 'Resuelto',
    'closed'          => 'Cerrado',
    _                 => s,
  };

  @override
  Widget build(BuildContext context) {
    final status   = ticket['status'] as String? ?? '';
    final subject  = ticket['subject'] as String? ?? 'Sin asunto';
    final category = ticket['category'] as String? ?? '';
    final color    = _statusColor(status);

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withAlpha(25),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.support_agent_outlined, color: color, size: 20),
            ),
            const SizedBox(width: 14),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(subject,
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
                  maxLines: 1, overflow: TextOverflow.ellipsis),
              if (category.isNotEmpty) ...[
                const SizedBox(height: 2),
                Text(category,
                    style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
              ],
            ])),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: color.withAlpha(25),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(_statusLabel(status),
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700,
                      color: color)),
            ),
            const SizedBox(width: 4),
            const Icon(Icons.chevron_right_rounded, color: Color(0xFFCBD5E1)),
          ]),
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
        Icon(Icons.support_agent_outlined, size: 72, color: Color(0xFFCBD5E1)),
        SizedBox(height: 16),
        Text('Sin casos de soporte',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700,
                color: Color(0xFF0B1C30))),
        SizedBox(height: 8),
        Text('¿Tienes algún problema? Abre un nuevo caso y te ayudaremos.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Color(0xFF64748B), height: 1.5)),
      ]),
    ),
  );
}
