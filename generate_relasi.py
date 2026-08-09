import xml.etree.ElementTree as ET

mxfile = ET.Element('mxfile', version="21.6.5")
diagram = ET.SubElement(mxfile, 'diagram', id="diagram_relasi", name="Relasi Tabel")
model = ET.SubElement(diagram, 'mxGraphModel', dx="1400", dy="1400", grid="1", gridSize="10", guides="1", tooltips="1", connect="1", arrows="1", fold="1", page="1", pageScale="1", pageWidth="1400", pageHeight="1100", background="#ffffff")
root = ET.SubElement(model, 'root')
ET.SubElement(root, 'mxCell', id="0")
ET.SubElement(root, 'mxCell', id="1", parent="0")

tables = {
    'anggotas': {
        'x': 50, 'y': 50, 'w': 230,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('', 'nama', 'varchar(100)'),
            ('', 'no_hp', 'varchar(20)'),
            ('', 'alamat', 'text')
        ]
    },
    'admins': {
        'x': 900, 'y': 50, 'w': 230,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('', 'username', 'varchar(50)'),
            ('', 'password', 'varchar'),
            ('', 'nama_lengkap', 'varchar(100)')
        ]
    },
    'blok_lahans': {
        'x': 50, 'y': 250, 'w': 230,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('FK', 'anggota_id', 'bigint'),
            ('', 'nama_blok', 'varchar(100)'),
            ('', 'luas_ha', 'double'),
            ('', 'sph', 'integer'),
            ('', 'koordinat_geojson', 'longtext'),
            ('', 'tahun_tanam', 'integer'),
            ('', 'jenis_tanah', 'varchar(255)'),
            ('', 'topografi', 'varchar(50)'),
            ('', 'fase_tanaman', 'varchar(50)')
        ]
    },
    'program_pemupukans': {
        'x': 450, 'y': 250, 'w': 250,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('FK', 'blok_lahan_id', 'bigint'),
            ('', 'tahun_program', 'integer'),
            ('', 'status_program', 'varchar(30)')
        ]
    },
    'rule_bases_lanjutan': {
        'x': 900, 'y': 220, 'w': 250,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('', 'kode_rule', 'varchar(50)'),
            ('', 'kondisi_warna_daun', 'varchar(100)'),
            ('', 'kondisi_curah_hujan_min_mm', 'decimal'),
            ('', 'kondisi_curah_hujan_max_mm', 'decimal'),
            ('', 'kondisi_kategori_umur', 'varchar(50)'),
            ('', 'indikasi_masalah', 'varchar(255)'),
            ('', 'jenis_pupuk_utama', 'varchar(100)'),
            ('', 'saran_tindakan', 'text'),
            ('', 'status_kebutuhan', 'varchar(50)'),
            ('', 'prioritas', 'integer'),
            ('', 'aktif', 'boolean')
        ]
    },
    'kondisi_lahans': {
        'x': 50, 'y': 550, 'w': 250,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('FK', 'blok_lahan_id', 'bigint'),
            ('', 'tanggal_observasi', 'date'),
            ('', 'warna_daun', 'enum'),
            ('', 'curah_hujan_mm_bulanan', 'decimal'),
            ('', 'kelembaban_tanah', 'enum'),
            ('', 'musim_saat_ini', 'enum'),
            ('', 'kondisi_drainase', 'enum'),
            ('', 'ada_gulma_dominan', 'boolean'),
            ('', 'ada_serangan_hama', 'boolean'),
            ('', 'catatan_observasi', 'text')
        ]
    },
    'rekomendasi_rbs': {
        'x': 450, 'y': 480, 'w': 260,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('FK', 'blok_lahan_id', 'bigint'),
            ('FK', 'program_pemupukan_id', 'bigint'),
            ('FK', 'kondisi_lahan_id', 'bigint'),
            ('FK', 'admin_id', 'bigint'),
            ('', 'tanggal_analisis', 'date'),
            ('', 'rules_terpicu', 'json'),
            ('', 'masalah_teridentifikasi', 'json'),
            ('', 'status_kebutuhan_dominan', 'varchar(50)'),
            ('', 'total_urea', 'double'),
            ('', 'total_kcl', 'double'),
            ('', 'status_stage', 'varchar(50)'),
            ('', 'kelengkapan_data_score', 'integer')
        ]
    },
    'realisasi_pemupukans': {
        'x': 900, 'y': 570, 'w': 250,
        'cols': [
            ('PK', 'id', 'bigint'),
            ('FK', 'rekomendasi_rbs_id', 'bigint'),
            ('FK', 'blok_lahan_id', 'bigint'),
            ('FK', 'program_pemupukan_id', 'bigint'),
            ('FK', 'admin_id', 'bigint'),
            ('', 'tahun_program', 'integer'),
            ('', 'tahap', 'integer'),
            ('', 'tanggal_realisasi', 'date'),
            ('', 'urea_rencana_kg', 'decimal'),
            ('', 'kcl_rencana_kg', 'decimal'),
            ('', 'urea_realisasi_kg', 'decimal'),
            ('', 'kcl_realisasi_kg', 'decimal'),
            ('', 'status_realisasi', 'varchar(30)'),
            ('', 'catatan_pelaksana', 'text')
        ]
    }
}

for name, t in tables.items():
    h = 30 + len(t['cols']) * 22
    
    cell = ET.SubElement(root, 'mxCell', id=f"tbl_{name}", value=f"<b>{name}</b>", style="swimlane;fontStyle=1;childLayout=stackLayout;horizontal=1;startSize=30;horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=0;marginBottom=0;html=1;align=center;fillColor=#e0e0e0;strokeColor=#000000;strokeWidth=1;", vertex="1", parent="1")
    ET.SubElement(cell, 'mxGeometry', x=str(t['x']), y=str(t['y']), width=str(t['w']), height=str(h), **{'as': 'geometry'})
    
    for i, col in enumerate(t['cols']):
        key_type, col_name, data_type = col
        val = f"{col_name} : {data_type}"
        if key_type == "PK":
            val = f"<b>PK</b> {val}"
        elif key_type == "FK":
            val = f"<b>FK</b> {val}"
            
        row = ET.SubElement(root, 'mxCell', id=f"col_{name}_{i}", value=val, style="text;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;spacingLeft=4;spacingRight=4;overflow=hidden;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;rotatable=0;whiteSpace=wrap;html=1;", vertex="1", parent=f"tbl_{name}")
        ET.SubElement(row, 'mxGeometry', y=str(30 + i*22), width=str(t['w']), height="22", **{'as': 'geometry'})

def add_edge(id, src, tgt, exitX, exitY, entryX, entryY, waypoints=None):
    style = f"edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;endArrow=ERmany;startArrow=ERmandOne;strokeColor=#000000;exitX={exitX};exitY={exitY};exitDx=0;exitDy=0;entryX={entryX};entryY={entryY};entryDx=0;entryDy=0;"
    edge = ET.SubElement(root, 'mxCell', id=f"edge_{id}", style=style, edge="1", parent="1", source=f"tbl_{src}", target=f"tbl_{tgt}")
    geo = ET.SubElement(edge, 'mxGeometry', relative="1", **{'as': 'geometry'})
    if waypoints:
        arr = ET.SubElement(geo, 'Array', **{'as': 'points'})
        for x, y in waypoints:
            ET.SubElement(arr, 'mxPoint', x=str(x), y=str(y))

add_edge("1", "anggotas", "blok_lahans", 0.5, 1, 0.5, 0)
add_edge("2", "blok_lahans", "kondisi_lahans", 0.5, 1, 0.5, 0)
add_edge("3", "blok_lahans", "program_pemupukans", 1, 0.3, 0, 0.5)
add_edge("4", "blok_lahans", "rekomendasi_rbs", 1, 0.7, 0, 0.2, [(320, 426), (320, 543)])
add_edge("6", "program_pemupukans", "rekomendasi_rbs", 0.5, 1, 0.5, 0)
add_edge("8", "kondisi_lahans", "rekomendasi_rbs", 1, 0.5, 0, 0.7)
add_edge("9", "rekomendasi_rbs", "realisasi_pemupukans", 1, 0.5, 0, 0.3)
add_edge("10", "admins", "rekomendasi_rbs", 0, 0.5, 1, 0.2, [(810, 110), (810, 543)])
add_edge("11", "admins", "realisasi_pemupukans", 1, 0.5, 1, 0.5, [(1220, 110), (1220, 740)])
add_edge("7", "program_pemupukans", "realisasi_pemupukans", 1, 0.5, 0, 0.1, [(760, 310), (760, 604)])
add_edge("5", "blok_lahans", "realisasi_pemupukans", 0.7, 1, 0.5, 1, [(211, 860), (1025, 860)])

tree = ET.ElementTree(mxfile)
tree.write('e:\\Skripsi\\Aplikasi Skripsi\\docs\\Proposal\\relasi_tabel_updated.drawio', encoding='utf-8', xml_declaration=True)
print('Done Relasi Tabel')
