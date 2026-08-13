<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BlokLahan;
use Shapefile\Shapefile;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Polygon;
use Shapefile\Geometry\MultiPolygon;
use Shapefile\Geometry\Geometry;

class ExportLahanShp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lahan:export-shp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Blok Lahan to Shapefile';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai export lahan ke Shapefile...');

        $lahans = BlokLahan::with('anggota')->get();
        
        if ($lahans->isEmpty()) {
            $this->error('Tidak ada data lahan untuk di-export.');
            return;
        }

        $exportDir = storage_path('app/public/export_shp');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $shpPath = $exportDir . '/lahan_export.shp';
        
        // Delete existing files if they exist
        $extensions = ['.shp', '.shx', '.dbf', '.prj'];
        foreach ($extensions as $ext) {
            $file = $exportDir . '/lahan_export' . $ext;
            if (file_exists($file)) {
                unlink($file);
            }
        }

        try {
            $ShapefileWriter = new ShapefileWriter($shpPath);
            $ShapefileWriter->setShapeType(Shapefile::SHAPE_TYPE_POLYGON);

            // Add fields (Max 10 chars per field name in DBF)
            $ShapefileWriter->addNumericField('ID', 10);
            $ShapefileWriter->addCharField('NAMA_BLOK', 100);
            $ShapefileWriter->addNumericField('LUAS_HA', 10, 4);
            $ShapefileWriter->addNumericField('SPH', 10);
            $ShapefileWriter->addNumericField('JML_POHON', 10);
            $ShapefileWriter->addNumericField('THN_TANAM', 10);
            $ShapefileWriter->addCharField('TOPOGRAFI', 50);
            $ShapefileWriter->addCharField('FASE', 10);
            $ShapefileWriter->addCharField('PEMILIK', 100);

            $count = 0;
            foreach ($lahans as $lahan) {
                if (empty($lahan->koordinat_geojson)) {
                    continue;
                }

                $geojson = $lahan->koordinat_geojson;
                
                // Ensure it's a valid JSON string
                if (is_array($geojson) || is_object($geojson)) {
                    $geojson = json_encode($geojson);
                }

                // Parse GeoJSON to determine type
                $geoArray = is_string($geojson) ? json_decode($geojson, true) : $geojson;
                if (!isset($geoArray['type'])) {
                    $this->warn("Tipe GeoJSON tidak ditemukan untuk ID: {$lahan->id}");
                    continue;
                }

                if ($geoArray['type'] === 'Polygon') {
                    $Geometry = new Polygon();
                } elseif ($geoArray['type'] === 'MultiPolygon') {
                    $Geometry = new MultiPolygon();
                } else {
                    $this->warn("Tipe geometri tidak didukung ({$geoArray['type']}) untuk ID: {$lahan->id}");
                    continue;
                }

                $geojsonString = is_string($geojson) ? $geojson : json_encode($geojson);
                $Geometry->initFromGeoJSON($geojsonString);

                // Set attributes
                $Geometry->setData('ID', $lahan->id);
                $Geometry->setData('NAMA_BLOK', $lahan->nama_blok ?? '');
                $Geometry->setData('LUAS_HA', $lahan->luas_ha ?? 0);
                $Geometry->setData('SPH', $lahan->sph ?? 0);
                $Geometry->setData('JML_POHON', $lahan->jumlah_pohon ?? 0);
                $Geometry->setData('THN_TANAM', $lahan->tahun_tanam ?? 0);
                $Geometry->setData('TOPOGRAFI', $lahan->topografi ?? '');
                $Geometry->setData('FASE', $lahan->fase_tanaman ?? '');
                $Geometry->setData('PEMILIK', $lahan->nama_pemilik ?? '');

                $ShapefileWriter->writeRecord($Geometry);
                $count++;
            }

            $this->info("Berhasil meng-export {$count} lahan ke {$shpPath}");
        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan: " . $e->getMessage());
        }
    }
}
