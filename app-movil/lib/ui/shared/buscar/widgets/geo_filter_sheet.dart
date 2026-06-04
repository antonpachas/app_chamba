import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../providers/geo_provider.dart';
import '../../../../providers/search_provider.dart';

class GeoFilterSheet extends StatefulWidget {
  const GeoFilterSheet({super.key});

  @override
  State<GeoFilterSheet> createState() => _GeoFilterSheetState();
}

class _GeoFilterSheetState extends State<GeoFilterSheet> {
  @override
  void initState() {
    super.initState();
    context.read<GeoProvider>().loadDepartments();
  }

  @override
  Widget build(BuildContext context) {
    final geo = context.watch<GeoProvider>();

    return DraggableScrollableSheet(
      initialChildSize: 0.75,
      maxChildSize: 0.95,
      minChildSize: 0.4,
      expand: false,
      builder: (_, controller) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Center(
            child: Container(width: 40, height: 4,
                decoration: BoxDecoration(color: const Color(0xFFCBD5E1),
                    borderRadius: BorderRadius.circular(2))),
          ),
          const SizedBox(height: 16),
          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            const Text('Filtrar por ubicación',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            TextButton(
              onPressed: () {
                context.read<GeoProvider>().clear();
                Navigator.pop(context);
                context.read<SearchProvider>()
                  ..setDistrict(null)
                  ..search();
              },
              child: const Text('Limpiar'),
            ),
          ]),
          const SizedBox(height: 16),
          Expanded(
            child: ListView(controller: controller, children: [
              // Departamento
              _label('Departamento'),
              geo.loadingDepts
                  ? const Center(child: CircularProgressIndicator())
                  : _Dropdown<int>(
                      value: geo.selectedDeptId,
                      hint: 'Selecciona departamento',
                      items: geo.departments.map((d) =>
                          DropdownMenuItem(value: d.id, child: Text(d.name))).toList(),
                      onChanged: (id) => context.read<GeoProvider>().selectDepartment(id),
                    ),
              const SizedBox(height: 16),
              // Provincia
              if (geo.selectedDeptId != null) ...[
                _label('Provincia'),
                geo.loadingProvs
                    ? const Center(child: CircularProgressIndicator())
                    : _Dropdown<int>(
                        value: geo.selectedProvId,
                        hint: 'Selecciona provincia',
                        items: geo.provinces.map((p) =>
                            DropdownMenuItem(value: p.id, child: Text(p.name))).toList(),
                        onChanged: (id) => context.read<GeoProvider>().selectProvince(id),
                      ),
                const SizedBox(height: 16),
              ],
              // Distrito
              if (geo.selectedProvId != null) ...[
                _label('Distrito'),
                geo.loadingDists
                    ? const Center(child: CircularProgressIndicator())
                    : _Dropdown<int>(
                        value: geo.selectedDistId,
                        hint: 'Selecciona distrito',
                        items: geo.districts.map((d) =>
                            DropdownMenuItem(value: d.id, child: Text(d.name))).toList(),
                        onChanged: (id) => context.read<GeoProvider>().selectDistrict(id),
                      ),
                const SizedBox(height: 24),
              ],
              FilledButton(
                onPressed: () {
                  final g = context.read<GeoProvider>();
                  context.read<SearchProvider>()
                    ..setDistrict(g.selectedDistId ?? g.selectedProvId)
                    ..search();
                  Navigator.pop(context);
                },
                child: const Text('Aplicar filtro'),
              ),
            ]),
          ),
        ]),
      ),
    );
  }

  Widget _label(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Text(text, style: const TextStyle(fontSize: 13,
        fontWeight: FontWeight.w700, color: Color(0xFF64748B))),
  );
}

class _Dropdown<T> extends StatelessWidget {
  const _Dropdown({
    required this.value,
    required this.hint,
    required this.items,
    required this.onChanged,
  });

  final T? value;
  final String hint;
  final List<DropdownMenuItem<T>> items;
  final ValueChanged<T?> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: DropdownButton<T>(
        value: value,
        hint: Text(hint, style: const TextStyle(color: Color(0xFF94A3B8))),
        isExpanded: true,
        underline: const SizedBox.shrink(),
        items: items,
        onChanged: onChanged,
      ),
    );
  }
}
