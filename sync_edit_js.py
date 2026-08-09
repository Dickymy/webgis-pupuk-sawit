import re

with open('resources/views/blok_lahan/create.blade.php', 'r', encoding='utf-8') as f:
    create_content = f.read()

with open('resources/views/blok_lahan/edit.blade.php', 'r', encoding='utf-8') as f:
    edit_content = f.read()

# Get everything inside <script>...</script> from create
create_script_match = re.search(r'<script>(.*?)</script>', create_content, re.DOTALL)
if create_script_match:
    create_script = create_script_match.group(1)
    
    # We replace the entire <script>...</script> in edit_content
    # First, construct the new script for edit by appending the init logic
    init_js = """
// ─── INIT KOORDINAT FOR EDIT ───
(function() {
    var geojsonStr = document.getElementById('koordinat_geojson').value;
    if (geojsonStr) {
        try {
            var geojson = JSON.parse(geojsonStr);
            if (geojson && geojson.coordinates && geojson.coordinates.length > 0) {
                var coords = geojson.coordinates[0];
                if (coords.length > 0 && Array.isArray(coords[0])) {
                    // hapus 3 baris kosong default
                    koordinatTitikCount = 0;
                    document.getElementById('koordinat-list').innerHTML = '';
                    
                    // tambahkan baris untuk tiap titik (kecuali titik terakhir yang menutup polygon)
                    var len = coords.length;
                    if(len > 3 && coords[0][0] === coords[len-1][0] && coords[0][1] === coords[len-1][1]) {
                        len = len - 1;
                    }
                    
                    for (var i = 0; i < len; i++) {
                        tambahTitikKoordinat();
                        var idNum = i + 1;
                        document.getElementById('titik-lng-' + idNum).value = coords[i][0];
                        document.getElementById('titik-lat-' + idNum).value = coords[i][1];
                    }
                    
                    // Render to preview map
                    setTimeout(terapkanKoordinat, 500);
                }
            }
        } catch(e) { console.error('Error parsing existing geojson', e); }
    }
})();
"""
    # create_script already contains the default init: (function() { for (var i=0; i<3; i++) tambahTitikKoordinat(); })();
    # We can replace that with our custom init
    new_script = create_script.replace(
        '// Inisialisasi 3 baris titik kosong saat halaman load (minimal)\n(function() {\n    for (var i = 0; i < 3; i++) tambahTitikKoordinat();\n})();',
        init_js
    )
    
    # Do replacement in edit.blade.php
    edit_content = re.sub(r'<script>.*?</script>', f'<script>{new_script}</script>', edit_content, flags=re.DOTALL)

with open('resources/views/blok_lahan/edit.blade.php', 'w', encoding='utf-8') as f:
    f.write(edit_content)
