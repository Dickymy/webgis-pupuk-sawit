<?php

/**
 * Script untuk membuat sample Shapefile (.zip) untuk testing upload.
 *
 * Jalankan dari root project:
 *   php tests/sample_files/generate_sample_shp.php
 *
 * Hasilnya: tests/sample_files/sample_blok_lahan.zip
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Shapefile\Geometry\Linestring;
use Shapefile\Geometry\Point;
use Shapefile\Geometry\Polygon;
use Shapefile\Shapefile;
use Shapefile\ShapefileWriter;

$outputDir = __DIR__;
$baseName = $outputDir.'/sample_blok_lahan_shp';

// Create Shapefile
$shapefile = new ShapefileWriter($baseName);

// Set shape type to Polygon
$shapefile->setShapeType(Shapefile::SHAPE_TYPE_POLYGON);

// Add a field
$shapefile->addCharField('NAMA', 50);
$shapefile->addNumericField('LUAS_HA', 10, 2);

// Create a polygon (area perkebunan sawit di Kalbar)
// Kurang lebih 4.5 Ha
$ring = new Linestring;
$ring->addPoint(new Point(109.3425, -0.0215));
$ring->addPoint(new Point(109.3445, -0.0215));
$ring->addPoint(new Point(109.3450, -0.0220));
$ring->addPoint(new Point(109.3445, -0.0240));
$ring->addPoint(new Point(109.3430, -0.0245));
$ring->addPoint(new Point(109.3420, -0.0235));
$ring->addPoint(new Point(109.3425, -0.0215)); // close ring

$polygon = new Polygon;
$polygon->addRing($ring);
$polygon->setData('NAMA', 'Blok A - Uji SHP');
$polygon->setData('LUAS_HA', 4.52);

$shapefile->writeRecord($polygon);

// Close (writes all files)
$shapefile = null;

// Now create a ZIP from the generated shapefile components
$zipFile = $outputDir.'/sample_blok_lahan.zip';
$zip = new ZipArchive;
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $extensions = ['shp', 'shx', 'dbf', 'prj'];
    foreach ($extensions as $ext) {
        $file = $baseName.'.'.$ext;
        if (file_exists($file)) {
            $zip->addFile($file, 'sample_blok_lahan_shp.'.$ext);
        }
    }
    $zip->close();
    echo "✓ ZIP berhasil dibuat: $zipFile\n";
} else {
    echo "✗ Gagal membuat ZIP\n";
    exit(1);
}

// Cleanup individual shapefile files
foreach (['shp', 'shx', 'dbf', 'prj', 'cpg'] as $ext) {
    $file = $baseName.'.'.$ext;
    if (file_exists($file)) {
        unlink($file);
    }
}

echo "✓ File SHP component sudah dihapus (hanya ZIP yang tersisa)\n";
echo "\nFile test yang tersedia:\n";
echo "  - tests/sample_files/sample_blok_lahan.geojson  (GeoJSON)\n";
echo "  - tests/sample_files/sample_blok_lahan.zip      (Shapefile ZIP)\n";
echo "\nUpload salah satu file ini di tab 'Upload File' pada form Tambah Blok Lahan.\n";
