import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/endpoints.dart';
import '../../../providers/session_provider.dart';
import '../widgets/error_view.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<Map<String, dynamic>> _notifications = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    final api     = context.read<ApiClient>();
    final session = context.read<SessionProvider>();
    final endpoint = session.user?.isProveedor == true
        ? Endpoints.providerNotifications
        : Endpoints.notifications;
    try {
      final data = await api.get(endpoint);
      _notifications = ((data['data'] as List?) ?? []).cast<Map<String, dynamic>>();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _markAllRead() async {
    final api     = context.read<ApiClient>();
    final session = context.read<SessionProvider>();
    final endpoint = session.user?.isProveedor == true
        ? '${Endpoints.providerNotifications}/read-all'
        : '${Endpoints.notifications}/read-all';
    try {
      await api.post(endpoint);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())));
      }
    }
  }

  Future<void> _markRead(int id) async {
    final api     = context.read<ApiClient>();
    final session = context.read<SessionProvider>();
    final endpoint = session.user?.isProveedor == true
        ? '${Endpoints.providerNotifications}/$id/read'
        : Endpoints.markNotificationRead(id);
    try {
      await api.post(endpoint);
      setState(() {
        final idx = _notifications.indexWhere((n) => n['id'] == id);
        if (idx != -1) {
          _notifications[idx] = {
            ..._notifications[idx],
            'read_at': DateTime.now().toIso8601String(),
          };
        }
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final unread = _notifications.where((n) => n['read_at'] == null).length;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notificaciones'),
        actions: [
          if (unread > 0)
            TextButton(
              onPressed: _markAllRead,
              child: const Text('Leer todo'),
            ),
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
              : _notifications.isEmpty
                  ? const _EmptyState()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        itemCount: _notifications.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (_, i) {
                          final n = _notifications[i];
                          final isRead = n['read_at'] != null;
                          final id = n['id'] as int? ?? 0;
                          return _NotificationTile(
                            notification: n,
                            isRead: isRead,
                            onTap: isRead ? null : () => _markRead(id),
                          );
                        },
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
        Icon(Icons.notifications_none_outlined, size: 72, color: Color(0xFFCBD5E1)),
        SizedBox(height: 16),
        Text('Sin notificaciones', style: TextStyle(fontSize: 18,
            fontWeight: FontWeight.w700, color: Color(0xFF0B1C30))),
        SizedBox(height: 6),
        Text('Aquí aparecerán tus notificaciones cuando las recibas.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Color(0xFF64748B))),
      ]),
    ),
  );
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({
    required this.notification,
    required this.isRead,
    this.onTap,
  });

  final Map<String, dynamic> notification;
  final bool isRead;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final title   = notification['title'] as String? ?? '';
    final body    = notification['body'] as String?
        ?? notification['message'] as String? ?? '';
    final type    = notification['type'] as String? ?? '';
    final created = notification['created_at'] as String?;

    String dateLabel = '';
    if (created != null) {
      final dt = DateTime.tryParse(created);
      if (dt != null) {
        final now = DateTime.now();
        final diff = now.difference(dt);
        if (diff.inMinutes < 60) {
          dateLabel = 'hace ${diff.inMinutes} min';
        } else if (diff.inHours < 24) {
          dateLabel = 'hace ${diff.inHours} h';
        } else {
          dateLabel = '${dt.day}/${dt.month}/${dt.year}';
        }
      }
    }

    return InkWell(
      onTap: onTap,
      child: Container(
        color: isRead ? null : const Color(0xFFF0F7FF),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Container(
            width: 40, height: 40,
            decoration: BoxDecoration(
              color: isRead
                  ? const Color(0xFFF1F5F9)
                  : const Color(0xFFDBEAFF),
              shape: BoxShape.circle,
            ),
            child: Icon(
              _iconForType(type),
              size: 20,
              color: isRead ? const Color(0xFF94A3B8) : const Color(0xFF003874),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              if (!isRead) ...[
                Container(
                  width: 8, height: 8,
                  decoration: const BoxDecoration(
                    color: Color(0xFF003874), shape: BoxShape.circle),
                ),
                const SizedBox(width: 6),
              ],
              Expanded(
                child: Text(title.isNotEmpty ? title : type,
                    style: TextStyle(
                        fontSize: 14,
                        fontWeight: isRead ? FontWeight.w500 : FontWeight.w700,
                        color: const Color(0xFF0B1C30))),
              ),
            ]),
            if (body.isNotEmpty) ...[
              const SizedBox(height: 3),
              Text(body, maxLines: 2, overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 13, color: Color(0xFF64748B),
                      height: 1.4)),
            ],
            if (dateLabel.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(dateLabel,
                  style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
            ],
          ])),
        ]),
      ),
    );
  }

  IconData _iconForType(String type) {
    if (type.contains('request')) return Icons.inbox_outlined;
    if (type.contains('review') || type.contains('rating')) return Icons.star_outline;
    if (type.contains('listing') || type.contains('service')) return Icons.campaign_outlined;
    if (type.contains('payment') || type.contains('wallet')) return Icons.account_balance_wallet_outlined;
    return Icons.notifications_outlined;
  }
}
