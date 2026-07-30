"""
Script untuk membuat sample Shapefile (ZIP) yang siap diimpor ke aplikasi.
Koordinat: area perkebunan kelapa sawit di Kalimantan Barat (Pontianak).
"""
import shapefile
import zipfile
import os

# Output directory
output_dir = os.path.dirname(os.path.abspath(__file__))
shp_base = os.path.join(output_dir, "sample_blok_lahan")
zip_path = os.path.join(output_dir, "sample_blok_lahan.zip")

# Koordinat polygon blok lahan sawit (~2 hektar, area Pontianak)
# Polygon harus closed (titik pertama = titik terakhir)
polygon_coords = [
    [109.3425, -0.0215],
    [109.3445, -0.0215],
    [109.3450, -0.0220],
    [109.3448, -0.0235],
    [109.3430, -0.0238],
    [109.3420, -0.0230],
    [109.3425, -0.0215],  # closed
]

# Buat shapefile
w = shapefile.Writer(shp_base, shapeType=shapefile.POLYGON)
w.field("NAMA", "C", 50)
w.field("LUAS_HA", "N", 10, 2)

# Tulis polygon
w.poly([polygon_coords])
w.record("Blok Sawit A1", 2.15)

w.close()

# Buat ZIP berisi .shp, .shx, .dbf
extensions = [".shp", ".shx", ".dbf"]
with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
    for ext in extensions:
        filepath = shp_base + ext
        if os.path.exists(filepath):
            zf.write(filepath, "sample_blok_lahan" + ext)

# Cleanup file individual (sisakan hanya ZIP)
for ext in extensions:
    filepath = shp_base + ext
    if os.path.exists(filepath):
        os.remove(filepath)

print(f"File SHP (ZIP) berhasil dibuat: {zip_path}")
print(f"Ukuran: {os.path.getsize(zip_path)} bytes")
print("File ini siap diimpor melalui fitur upload di halaman Blok Lahan.")
