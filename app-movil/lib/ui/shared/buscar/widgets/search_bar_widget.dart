import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../providers/search_provider.dart';
import '../../../../providers/geo_provider.dart';

class SearchBarWidget extends StatefulWidget {
  const SearchBarWidget({
    super.key,
    required this.onSearch,
    required this.onGeoTap,
  });

  final VoidCallback onSearch;
  final VoidCallback onGeoTap;

  @override
  State<SearchBarWidget> createState() => _SearchBarWidgetState();
}

class _SearchBarWidgetState extends State<SearchBarWidget> {
  final _ctrl = TextEditingController();

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  void _submit() {
    context.read<SearchProvider>().setKeyword(_ctrl.text.trim());
    widget.onSearch();
  }

  @override
  Widget build(BuildContext context) {
    final geo = context.watch<GeoProvider>();
    final place = geo.selectedDistName ?? geo.selectedProvName ?? geo.selectedDeptName;

    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Expanded(
          child: TextField(
            controller: _ctrl,
            onSubmitted: (_) => _submit(),
            style: const TextStyle(fontSize: 15, color: Color(0xFF0B1C30)),
            decoration: InputDecoration(
              hintText: 'Busca chicharrones, talleres, tiendas…',
              hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14),
              prefixIcon: const Icon(Icons.search, color: Color(0xFF64748B)),
              suffixIcon: _ctrl.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.close, size: 18),
                      onPressed: () {
                        _ctrl.clear();
                        context.read<SearchProvider>().setKeyword('');
                        setState(() {});
                      },
                    )
                  : null,
              filled: true,
              fillColor: Colors.white,
              contentPadding: const EdgeInsets.symmetric(vertical: 12),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
            ),
            onChanged: (_) => setState(() {}),
          ),
        ),
        const SizedBox(width: 8),
        FilledButton(
          onPressed: _submit,
          style: FilledButton.styleFrom(
            backgroundColor: const Color(0xFF1D6EE8),
            minimumSize: const Size(56, 48),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
          child: const Icon(Icons.search, color: Colors.white),
        ),
      ]),
      const SizedBox(height: 8),
      GestureDetector(
        onTap: widget.onGeoTap,
        child: Row(children: [
          const Icon(Icons.location_on_outlined, color: Color(0xFF93C5FD), size: 16),
          const SizedBox(width: 4),
          Text(
            place ?? 'Todo el Perú',
            style: const TextStyle(color: Color(0xFF93C5FD), fontSize: 13,
                fontWeight: FontWeight.w500),
          ),
          const SizedBox(width: 4),
          const Icon(Icons.keyboard_arrow_down, color: Color(0xFF93C5FD), size: 16),
        ]),
      ),
    ]);
  }
}
