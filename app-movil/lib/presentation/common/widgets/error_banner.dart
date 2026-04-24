import 'package:flutter/material.dart';

/// Mensaje de error legible: errores largos o técnicos del servidor se resumen y el detalle es opcional.
class ErrorBanner extends StatefulWidget {
  const ErrorBanner({super.key, required this.message});

  final String message;

  @override
  State<ErrorBanner> createState() => _ErrorBannerState();
}

class _ErrorBannerState extends State<ErrorBanner> {
  bool _showTechnical = false;

  bool get _isTechnical =>
      widget.message.contains('SQLSTATE') ||
      widget.message.contains("doesn't exist") ||
      widget.message.contains('PDOException') ||
      widget.message.contains('Stack trace');

  bool get _needsDetailToggle =>
      _isTechnical || widget.message.length > 160;

  String get _summary {
    if (widget.message.contains('personal_access_tokens')) {
      return 'El acceso no está listo en el servidor (falta configurar la base de datos).';
    }
    if (_isTechnical) {
      return 'Algo salió mal. Puedes intentar de nuevo en unos momentos.';
    }
    if (widget.message.length > 160) {
      return '${widget.message.substring(0, 140)}…';
    }
    return widget.message;
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 10),
      decoration: BoxDecoration(
        color: scheme.errorContainer,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: scheme.error.withValues(alpha: 0.22)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.info_outline_rounded, color: scheme.onErrorContainer, size: 24),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  _summary,
                  style: TextStyle(
                    color: scheme.onErrorContainer,
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                    height: 1.35,
                  ),
                ),
              ),
            ],
          ),
          if (_needsDetailToggle) ...[
            if (_showTechnical) ...[
              const SizedBox(height: 10),
              SelectableText(
                widget.message,
                style: TextStyle(
                  color: scheme.onErrorContainer.withValues(alpha: 0.9),
                  fontWeight: FontWeight.w500,
                  fontSize: 12.5,
                  height: 1.35,
                ),
              ),
            ],
            TextButton(
              onPressed: () => setState(() => _showTechnical = !_showTechnical),
              style: TextButton.styleFrom(
                foregroundColor: scheme.onErrorContainer,
                padding: EdgeInsets.zero,
                minimumSize: const Size(0, 44),
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: Text(_showTechnical ? 'Ocultar detalle' : 'Ver detalle'),
            ),
          ],
        ],
      ),
    );
  }
}
