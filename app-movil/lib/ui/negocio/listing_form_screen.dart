import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../data/repositories/provider_repository.dart';
import '../../providers/catalog_provider.dart';
import '../../providers/geo_provider.dart';
import '../shared/widgets/app_button.dart';

class ListingFormScreen extends StatefulWidget {
  const ListingFormScreen({super.key, this.listing});
  final Map<String, dynamic>? listing;

  @override
  State<ListingFormScreen> createState() => _ListingFormScreenState();
}

class _ListingFormScreenState extends State<ListingFormScreen> {
  final _form      = GlobalKey<FormState>();
  final _picker    = ImagePicker();
  bool _saving     = false;
  String? _error;

  late final TextEditingController _title;
  late final TextEditingController _description;
  late final TextEditingController _price;
  late final TextEditingController _address;
  String  _priceType    = 'fijo';
  String  _listingType  = 'presencia';
  int?    _categoryId;
  int?    _districtId;

  // Fotos: las existentes (URLs) y las nuevas (paths locales)
  List<String>  _existingImages = [];
  List<XFile>   _newImages      = [];
  static const  _maxPhotos      = 3;

  static const _priceTypes = [
    ('fijo',  'Precio fijo'),
    ('hora',  'Por hora'),
    ('dia',   'Por día'),
    ('mes',   'Por mes'),
    ('trato', 'A tratar'),
  ];

  bool get _isEdit => widget.listing != null;
  int  get _totalPhotos => _existingImages.length + _newImages.length;

  @override
  void initState() {
    super.initState();
    final l = widget.listing;
    _title        = TextEditingController(text: l?['title'] as String? ?? '');
    _description  = TextEditingController(text: l?['description'] as String? ?? '');
    _price        = TextEditingController(text: l?['base_price']?.toString() ?? '');
    _address      = TextEditingController(text: l?['address_text'] as String? ?? '');
    _priceType    = l?['price_type']  as String? ?? 'fijo';
    _listingType  = l?['listing_type'] as String? ?? 'presencia';
    _categoryId   = (l?['category_id'] as num?)?.toInt()
        ?? (l?['category'] as Map?)?['id'] as int?;
    _districtId   = (l?['district_id'] as num?)?.toInt();
    final imgs    = l?['images'] as List?;
    _existingImages = imgs?.map((e) => e.toString()).toList() ?? [];

    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CatalogProvider>().ensureLoaded();
      context.read<GeoProvider>().loadDepartments();
    });
  }

  @override
  void dispose() {
    _title.dispose(); _description.dispose();
    _price.dispose(); _address.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    if (_totalPhotos >= _maxPhotos) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Máximo 3 fotos por anuncio')));
      return;
    }
    final source = await _showImageSourceDialog();
    if (source == null) return;
    final file = await _picker.pickImage(
        source: source, imageQuality: 80, maxWidth: 1200);
    if (file != null && mounted) {
      setState(() => _newImages.add(file));
    }
  }

  Future<ImageSource?> _showImageSourceDialog() =>
      showModalBottomSheet<ImageSource>(
        context: context,
        builder: (_) => SafeArea(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Galería'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined),
              title: const Text('Cámara'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
          ]),
        ),
      );

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() { _saving = true; _error = null; });
    final repo = context.read<ProviderRepository>();
    final body = <String, dynamic>{
      'title':        _title.text.trim(),
      'description':  _description.text.trim(),
      'price_type':   _priceType,
      'listing_type': _listingType,
      if (_price.text.trim().isNotEmpty && _priceType != 'trato')
        'base_price': double.tryParse(_price.text.trim()),
      if (_categoryId != null) 'category_id': _categoryId,
      if (_districtId != null) 'district_id': _districtId,
      if (_address.text.trim().isNotEmpty) 'address_text': _address.text.trim(),
    };
    try {
      Map<String, dynamic> saved;
      if (_isEdit) {
        saved = await repo.updateListing(widget.listing!['id'] as int, body);
      } else {
        saved = await repo.createListing(body);
      }
      // Subir fotos nuevas
      if (_newImages.isNotEmpty) {
        final serviceId = (saved['id'] as num?)?.toInt()
            ?? widget.listing?['id'] as int?;
        if (serviceId != null) {
          await repo.uploadListingImages(
              serviceId, _newImages.map((f) => f.path).toList());
        }
      }
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final categories = context.watch<CatalogProvider>().categories;
    final geo        = context.watch<GeoProvider>();

    return Scaffold(
      appBar: AppBar(title: Text(_isEdit ? 'Editar anuncio' : 'Nuevo anuncio')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 40),
        child: Form(
          key: _form,
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [

            // Error banner
            if (_error != null)
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(color: const Color(0xFFFEE2E2),
                    borderRadius: BorderRadius.circular(10)),
                child: Row(children: [
                  const Icon(Icons.error_outline, color: Color(0xFFDC2626), size: 18),
                  const SizedBox(width: 8),
                  Expanded(child: Text(_error!,
                      style: const TextStyle(color: Color(0xFFDC2626), fontSize: 13))),
                ]),
              ),

            // Tipo de publicación
            _Label('Tipo de publicación'),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'presencia',
                    icon: Icon(Icons.storefront_outlined), label: Text('Presencia')),
                ButtonSegment(value: 'promocion',
                    icon: Icon(Icons.star_outline_rounded), label: Text('Promoción')),
              ],
              selected: {_listingType},
              onSelectionChanged: (s) => setState(() => _listingType = s.first),
            ),
            const SizedBox(height: 20),

            // Fotos
            _Label('Fotos (máx. $_maxPhotos)'),
            SizedBox(
              height: 100,
              child: ListView(
                scrollDirection: Axis.horizontal,
                children: [
                  // Fotos existentes
                  ..._existingImages.asMap().entries.map((e) => _PhotoThumb(
                    child: Image.network(e.value, fit: BoxFit.cover),
                    onRemove: () => setState(() => _existingImages.removeAt(e.key)),
                  )),
                  // Fotos nuevas
                  ..._newImages.asMap().entries.map((e) => _PhotoThumb(
                    child: Image.file(File(e.value.path), fit: BoxFit.cover),
                    onRemove: () => setState(() => _newImages.removeAt(e.key)),
                  )),
                  // Botón agregar
                  if (_totalPhotos < _maxPhotos)
                    GestureDetector(
                      onTap: _pickPhoto,
                      child: Container(
                        width: 90, height: 90,
                        margin: const EdgeInsets.only(right: 8),
                        decoration: BoxDecoration(
                          border: Border.all(
                              color: const Color(0xFFCBD5E1), width: 2,
                              style: BorderStyle.none),
                          borderRadius: BorderRadius.circular(10),
                          color: const Color(0xFFF1F5F9),
                        ),
                        child: const Icon(Icons.add_photo_alternate_outlined,
                            size: 32, color: Color(0xFF94A3B8)),
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Título
            TextFormField(
              controller: _title,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Título del anuncio *',
                prefixIcon: Icon(Icons.campaign_outlined),
              ),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'Campo requerido' : null,
            ),
            const SizedBox(height: 16),

            // Categoría
            if (categories.isNotEmpty) ...[
              DropdownButtonFormField<int>(
                value: _categoryId,
                decoration: const InputDecoration(
                  labelText: 'Categoría',
                  prefixIcon: Icon(Icons.category_outlined),
                ),
                items: categories.map((c) =>
                    DropdownMenuItem(value: c.id, child: Text(c.name))).toList(),
                onChanged: (v) => setState(() => _categoryId = v),
                hint: const Text('Selecciona una categoría'),
              ),
              const SizedBox(height: 16),
            ],

            // Descripción
            TextFormField(
              controller: _description,
              maxLines: 5,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Descripción *',
                alignLabelWithHint: true,
                prefixIcon: Padding(
                  padding: EdgeInsets.only(bottom: 80),
                  child: Icon(Icons.description_outlined),
                ),
              ),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'Campo requerido' : null,
            ),
            const SizedBox(height: 20),

            // Tipo de precio
            _Label('Tipo de precio'),
            Wrap(
              spacing: 8, runSpacing: 4,
              children: _priceTypes.map(((String, String) pt) => ChoiceChip(
                label: Text(pt.$2),
                selected: _priceType == pt.$1,
                onSelected: (_) => setState(() => _priceType = pt.$1),
                selectedColor: const Color(0xFF003874),
                labelStyle: TextStyle(
                  color: _priceType == pt.$1 ? Colors.white : null,
                  fontWeight: FontWeight.w600, fontSize: 13,
                ),
              )).toList(),
            ),
            const SizedBox(height: 16),

            // Precio
            if (_priceType != 'trato') ...[
              TextFormField(
                controller: _price,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Precio (S/)',
                  prefixIcon: Icon(Icons.attach_money_outlined),
                ),
                validator: (v) {
                  if (_priceType == 'trato') return null;
                  if (v != null && v.isNotEmpty && double.tryParse(v) == null) {
                    return 'Ingresa un número válido';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
            ],

            // Ubicación — Departamento
            _Label('Ubicación'),
            DropdownButtonFormField<int>(
              value: geo.selectedDeptId,
              decoration: const InputDecoration(
                labelText: 'Departamento',
                prefixIcon: Icon(Icons.map_outlined),
              ),
              items: geo.departments.map((d) =>
                  DropdownMenuItem(value: d.id, child: Text(d.name))).toList(),
              onChanged: (v) => context.read<GeoProvider>().selectDepartment(v),
              hint: geo.loadingDepts
                  ? const Text('Cargando...')
                  : const Text('Selecciona'),
            ),
            const SizedBox(height: 12),

            // Provincia
            DropdownButtonFormField<int>(
              value: geo.selectedProvId,
              decoration: const InputDecoration(
                labelText: 'Provincia',
                prefixIcon: Icon(Icons.location_city_outlined),
              ),
              items: geo.provinces.map((p) =>
                  DropdownMenuItem(value: p.id, child: Text(p.name))).toList(),
              onChanged: geo.selectedDeptId == null
                  ? null
                  : (v) => context.read<GeoProvider>().selectProvince(v),
              hint: geo.loadingProvs
                  ? const Text('Cargando...')
                  : const Text('Selecciona'),
            ),
            const SizedBox(height: 12),

            // Distrito
            DropdownButtonFormField<int>(
              value: _districtId,
              decoration: const InputDecoration(
                labelText: 'Distrito',
                prefixIcon: Icon(Icons.pin_drop_outlined),
              ),
              items: geo.districts.map((d) =>
                  DropdownMenuItem(value: d.id, child: Text(d.name))).toList(),
              onChanged: geo.selectedProvId == null
                  ? null
                  : (v) {
                      context.read<GeoProvider>().selectDistrict(v);
                      setState(() => _districtId = v);
                    },
              hint: geo.loadingDists
                  ? const Text('Cargando...')
                  : const Text('Selecciona'),
            ),
            const SizedBox(height: 12),

            // Dirección
            TextFormField(
              controller: _address,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Dirección (opcional)',
                prefixIcon: Icon(Icons.home_outlined),
                hintText: 'Av. Ejemplo 123',
              ),
            ),
            const SizedBox(height: 32),

            AppButton(
              label: _isEdit ? 'Guardar cambios' : 'Publicar anuncio',
              onPressed: _submit,
              loading: _saving,
            ),
          ]),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
class _Label extends StatelessWidget {
  const _Label(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Text(text,
        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700,
            color: Color(0xFF64748B))),
  );
}

class _PhotoThumb extends StatelessWidget {
  const _PhotoThumb({required this.child, required this.onRemove});
  final Widget child;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) => Container(
    width: 90, height: 90,
    margin: const EdgeInsets.only(right: 8),
    child: Stack(fit: StackFit.expand, children: [
      ClipRRect(
        borderRadius: BorderRadius.circular(10),
        child: child,
      ),
      Positioned(
        top: 2, right: 2,
        child: GestureDetector(
          onTap: onRemove,
          child: Container(
            decoration: const BoxDecoration(
                color: Colors.black54, shape: BoxShape.circle),
            child: const Icon(Icons.close, color: Colors.white, size: 16),
          ),
        ),
      ),
    ]),
  );
}
