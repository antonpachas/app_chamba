import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/network/api_client.dart';
import '../../core/network/endpoints.dart';
import '../../data/models/geo.dart';
import '../../data/repositories/geo_repository.dart';
import '../shared/widgets/error_view.dart';

class ProviderLocationsScreen extends StatefulWidget {
  const ProviderLocationsScreen({super.key});

  @override
  State<ProviderLocationsScreen> createState() =>
      _ProviderLocationsScreenState();
}

class _ProviderLocationsScreenState extends State<ProviderLocationsScreen> {
  List<Map<String, dynamic>> _locations = [];
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
      final data = await context.read<ApiClient>()
          .get(Endpoints.providerLocations);
      _locations = ((data['data'] as List?) ?? [])
          .cast<Map<String, dynamic>>();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _delete(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Eliminar ubicación'),
        content: const Text('¿Seguro que deseas eliminar esta ubicación?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFFDC2626)),
              child: const Text('Eliminar')),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    try {
      await context.read<ApiClient>().delete(
          Endpoints.providerLocationDetail(id));
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString())));
      }
    }
  }

  void _openForm({Map<String, dynamic>? location}) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _LocationFormSheet(
        api: context.read<ApiClient>(),
        geoRepo: context.read<GeoRepository>(),
        location: location,
      ),
    );
    if (saved == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mis ubicaciones'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _load),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(),
        icon: const Icon(Icons.add_location_alt_outlined),
        label: const Text('Agregar'),
        backgroundColor: const Color(0xFF003874),
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _locations.isEmpty
                  ? const _EmptyState()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding:
                            const EdgeInsets.fromLTRB(16, 16, 16, 80),
                        itemCount: _locations.length,
                        separatorBuilder: (_, _) =>
                            const SizedBox(height: 10),
                        itemBuilder: (_, i) {
                          final loc = _locations[i];
                          return _LocationTile(
                            location: loc,
                            onEdit: () => _openForm(location: loc),
                            onDelete: () => _delete(
                                (loc['id'] as num).toInt()),
                          );
                        },
                      ),
                    ),
    );
  }
}

// ---------------------------------------------------------------------------
// Tile
// ---------------------------------------------------------------------------
class _LocationTile extends StatelessWidget {
  const _LocationTile({
    required this.location,
    required this.onEdit,
    required this.onDelete,
  });
  final Map<String, dynamic> location;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final name    = location['name'] as String? ?? 'Ubicación';
    final address = location['address_text'] as String? ?? '';
    final district = location['district_name'] as String?
        ?? (location['district'] as Map?)?['name'] as String? ?? '';
    final isPrimary = location['is_primary'] == true;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(
              child: Row(children: [
                const Icon(Icons.location_on_outlined,
                    color: Color(0xFF003874), size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(name,
                      style: const TextStyle(
                          fontSize: 15, fontWeight: FontWeight.w700)),
                ),
                if (isPrimary)
                  Container(
                    margin: const EdgeInsets.only(left: 8),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFFDBEAFF),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Text('Principal',
                        style: TextStyle(fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF003874))),
                  ),
              ]),
            ),
            PopupMenuButton<String>(
              onSelected: (v) {
                if (v == 'edit') onEdit();
                if (v == 'delete') onDelete();
              },
              itemBuilder: (_) => const [
                PopupMenuItem(value: 'edit',
                    child: ListTile(
                        leading: Icon(Icons.edit_outlined),
                        title: Text('Editar'),
                        contentPadding: EdgeInsets.zero,
                        dense: true)),
                PopupMenuItem(value: 'delete',
                    child: ListTile(
                        leading: Icon(Icons.delete_outline,
                            color: Color(0xFFDC2626)),
                        title: Text('Eliminar',
                            style: TextStyle(color: Color(0xFFDC2626))),
                        contentPadding: EdgeInsets.zero,
                        dense: true)),
              ],
            ),
          ]),
          if (district.isNotEmpty || address.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              [district, address].where((s) => s.isNotEmpty).join(' · '),
              style: const TextStyle(
                  fontSize: 13, color: Color(0xFF64748B)),
            ),
          ],
        ]),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Form bottom sheet
// ---------------------------------------------------------------------------
class _LocationFormSheet extends StatefulWidget {
  const _LocationFormSheet({
    required this.api,
    required this.geoRepo,
    this.location,
  });
  final ApiClient api;
  final GeoRepository geoRepo;
  final Map<String, dynamic>? location;

  @override
  State<_LocationFormSheet> createState() => _LocationFormSheetState();
}

class _LocationFormSheetState extends State<_LocationFormSheet> {
  final _form    = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _address;
  bool _isPrimary = false;
  bool _saving    = false;
  String? _error;

  // Geo local
  List<Department> _depts = [];
  List<Province>   _provs = [];
  List<District>   _dists = [];
  int? _deptId, _provId, _distId;
  bool _loadingDepts = false, _loadingProvs = false, _loadingDists = false;

  bool get _isEdit => widget.location != null;

  @override
  void initState() {
    super.initState();
    final loc  = widget.location;
    _name      = TextEditingController(text: loc?['name'] as String? ?? '');
    _address   = TextEditingController(
        text: loc?['address_text'] as String? ?? '');
    _isPrimary = loc?['is_primary'] == true;
    _distId    = (loc?['district_id'] as num?)?.toInt();
    _loadDepts();
  }

  @override
  void dispose() {
    _name.dispose(); _address.dispose();
    super.dispose();
  }

  Future<void> _loadDepts() async {
    setState(() => _loadingDepts = true);
    try { _depts = await widget.geoRepo.departments(); } catch (_) {}
    if (mounted) setState(() => _loadingDepts = false);
  }

  Future<void> _onDeptChange(int? id) async {
    setState(() {
      _deptId = id; _provId = null; _distId = null;
      _provs  = []; _dists  = [];
    });
    if (id == null) return;
    setState(() => _loadingProvs = true);
    try { _provs = await widget.geoRepo.provinces(id); } catch (_) {}
    if (mounted) setState(() => _loadingProvs = false);
  }

  Future<void> _onProvChange(int? id) async {
    setState(() { _provId = id; _distId = null; _dists = []; });
    if (id == null) return;
    setState(() => _loadingDists = true);
    try { _dists = await widget.geoRepo.districts(id); } catch (_) {}
    if (mounted) setState(() => _loadingDists = false);
  }

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() { _saving = true; _error = null; });
    final body = <String, dynamic>{
      'name':       _name.text.trim(),
      'is_primary': _isPrimary,
      if (_address.text.trim().isNotEmpty) 'address_text': _address.text.trim(),
      if (_distId != null) 'district_id': _distId,
    };
    try {
      if (_isEdit) {
        await widget.api.put(
          Endpoints.providerLocationDetail(
              (widget.location!['id'] as num).toInt()),
          data: body,
        );
      } else {
        await widget.api.post(Endpoints.providerLocations, data: body);
      }
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _saving = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + bottom),
      child: Form(
        key: _form,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                    color: const Color(0xFFCBD5E1),
                    borderRadius: BorderRadius.circular(2)),
              ),
            ),
            Text(_isEdit ? 'Editar ubicación' : 'Nueva ubicación',
                style: const TextStyle(fontSize: 18,
                    fontWeight: FontWeight.w800, color: Color(0xFF0B1C30))),
            const SizedBox(height: 20),

            TextFormField(
              controller: _name,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Nombre del local *',
                prefixIcon: Icon(Icons.storefront_outlined),
                hintText: 'Ej: Tienda principal, Sucursal Miraflores',
              ),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'Campo requerido' : null,
            ),
            const SizedBox(height: 14),

            // Departamento
            DropdownButtonFormField<int>(
              initialValue: _deptId,
              decoration: const InputDecoration(
                  labelText: 'Departamento',
                  prefixIcon: Icon(Icons.map_outlined)),
              hint: _loadingDepts
                  ? const Text('Cargando...')
                  : const Text('Selecciona'),
              items: _depts.map((d) =>
                  DropdownMenuItem(value: d.id, child: Text(d.name))).toList(),
              onChanged: _onDeptChange,
            ),
            const SizedBox(height: 12),

            DropdownButtonFormField<int>(
              value: _provId,
              decoration: const InputDecoration(
                  labelText: 'Provincia',
                  prefixIcon: Icon(Icons.location_city_outlined)),
              hint: _loadingProvs
                  ? const Text('Cargando...')
                  : const Text('Selecciona'),
              items: _provs.map((p) =>
                  DropdownMenuItem(value: p.id, child: Text(p.name))).toList(),
              onChanged: _deptId == null ? null : _onProvChange,
            ),
            const SizedBox(height: 12),

            DropdownButtonFormField<int>(
              value: _distId,
              decoration: const InputDecoration(
                  labelText: 'Distrito',
                  prefixIcon: Icon(Icons.pin_drop_outlined)),
              hint: _loadingDists
                  ? const Text('Cargando...')
                  : const Text('Selecciona'),
              items: _dists.map((d) =>
                  DropdownMenuItem(value: d.id, child: Text(d.name))).toList(),
              onChanged: _provId == null
                  ? null
                  : (v) => setState(() => _distId = v),
            ),
            const SizedBox(height: 12),

            TextFormField(
              controller: _address,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Dirección (opcional)',
                prefixIcon: Icon(Icons.home_outlined),
              ),
            ),
            const SizedBox(height: 12),

            SwitchListTile(
              value: _isPrimary,
              onChanged: (v) => setState(() => _isPrimary = v),
              title: const Text('Marcar como ubicación principal',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
              contentPadding: EdgeInsets.zero,
            ),

            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!,
                  style: const TextStyle(
                      color: Color(0xFFDC2626), fontSize: 13)),
            ],
            const SizedBox(height: 20),

            SizedBox(
              width: double.infinity, height: 50,
              child: FilledButton.icon(
                onPressed: _saving ? null : _submit,
                icon: _saving
                    ? const SizedBox(width: 18, height: 18,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.save_outlined),
                label: Text(_saving
                    ? 'Guardando...'
                    : _isEdit ? 'Guardar cambios' : 'Agregar ubicación'),
                style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF003874)),
              ),
            ),
          ],
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
        Icon(Icons.location_off_outlined, size: 72,
            color: Color(0xFFCBD5E1)),
        SizedBox(height: 16),
        Text('Sin ubicaciones registradas',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700,
                color: Color(0xFF0B1C30))),
        SizedBox(height: 8),
        Text('Agrega la ubicación de tu negocio para aparecer en el mapa.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Color(0xFF64748B),
                height: 1.5)),
      ]),
    ),
  );
}
