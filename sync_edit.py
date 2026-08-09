import re

with open('resources/views/blok_lahan/create.blade.php', 'r', encoding='utf-8') as f:
    create_content = f.read()

with open('resources/views/blok_lahan/edit.blade.php', 'r', encoding='utf-8') as f:
    edit_content = f.read()

# Extract tab buttons from create
tabs_match = re.search(r'(<div class="flex gap-1 mb-3 bg-slate-100 p-1 rounded-xl">.*?</button>\s*</div>)', create_content, re.DOTALL)
if tabs_match:
    create_tabs = tabs_match.group(1)
    edit_content = re.sub(r'<div class="flex gap-1 mb-3 bg-slate-100 p-1 rounded-xl">.*?</button>\s*</div>', create_tabs, edit_content, flags=re.DOTALL)

# Extract panel-coords from create
coords_match = re.search(r'(<div id="panel-coords" class="hidden space-y-3">.*?</div>\s*</div>\s*</div>)', create_content, re.DOTALL)
if coords_match:
    create_coords = coords_match.group(1)
    # Replace panel-json in edit with panel-coords
    edit_content = re.sub(r'<div id="panel-json" class="hidden">.*?</div>\s*</div>\s*</div>\s*</div>', create_coords, edit_content, flags=re.DOTALL)

# Now JS replacement
# Replace `function switchTab(tab)`
create_switch = re.search(r'(function switchTab\(tab\) \{.*?\n\})', create_content, re.DOTALL)
if create_switch:
    edit_content = re.sub(r'function switchTab\(tab\) \{.*?\n\}', create_switch.group(1), edit_content, flags=re.DOTALL)

# Replace JS logic block starting from // --- UPLOAD FILE --- to end of script
create_js = re.search(r'(// ─── UPLOAD FILE ───.*)(</script>)', create_content, re.DOTALL)
if create_js:
    # First, let's find where to replace in edit
    edit_content = re.sub(r'// ─── UPLOAD FILE ───.*(?=</script>)', create_js.group(1), edit_content, flags=re.DOTALL)

# Need to ensure coords init pre-populates existing coordinates in edit mode
# I will append a custom JS block for edit.blade.php to load coordinates on load
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

edit_content = edit_content.replace('// Inisialisasi 3 baris titik kosong saat halaman load (minimal)\n(function() {\n    for (var i = 0; i < 3; i++) tambahTitikKoordinat();\n})();', init_js)

with open('resources/views/blok_lahan/edit.blade.php', 'w', encoding='utf-8') as f:
    f.write(edit_content)
