import re

with open('resources/views/blok_lahan/create.blade.php', 'r', encoding='utf-8') as f:
    create_content = f.read()

with open('resources/views/blok_lahan/edit.blade.php', 'r', encoding='utf-8') as f:
    edit_content = f.read()

# Get the main script block from create.blade.php
create_script_match = re.search(r'<script>\s*var currentTab = \'draw\';(.*?)<\/script>', create_content, re.DOTALL)
if create_script_match:
    create_script_inner = create_script_match.group(1)
    
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
                    koordinatTitikCount = 0;
                    document.getElementById('koordinat-list').innerHTML = '';
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
                    setTimeout(terapkanKoordinat, 500);
                }
            }
        } catch(e) { console.error('Error parsing existing geojson', e); }
    }
})();
"""
    
    # Replace the default init with custom init
    new_script_inner = create_script_inner.replace(
        '// Inisialisasi 3 baris titik kosong saat halaman load (minimal)\n(function() {\n    for (var i = 0; i < 3; i++) tambahTitikKoordinat();\n})();',
        init_js
    )
    
    # Replace the script block in edit.blade.php
    edit_content = re.sub(
        r'<script>\s*var currentTab = \'draw\';.*?<\/script>',
        f"<script>\\nvar currentTab = 'draw';{new_script_inner}</script>",
        edit_content,
        flags=re.DOTALL
    )

with open('resources/views/blok_lahan/edit.blade.php', 'w', encoding='utf-8') as f:
    f.write(edit_content)
