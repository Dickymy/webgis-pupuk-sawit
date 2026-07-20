<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shapefile\ShapefileReader;

class GeoUploadController extends Controller
{
    /**
     * Handle upload file SHP (ZIP berisi .shp, .shx, .dbf) atau file .geojson
     * Mengembalikan GeoJSON polygon yang siap dirender di Leaflet.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'geo_file' => ['required', 'file', 'max:10240'], // max 10MB
        ], [
            'geo_file.required' => 'File wajib dipilih.',
            'geo_file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $request->file('geo_file');
        $ext = strtolower($file->getClientOriginalExtension());

        try {
            if ($ext === 'geojson' || $ext === 'json') {
                return $this->handleGeoJson($file);
            } elseif ($ext === 'zip') {
                return $this->handleShpZip($file);
            } elseif ($ext === 'shp') {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan upload file ZIP yang berisi .shp, .shx, dan .dbf secara bersamaan. Upload file .shp saja tidak cukup.',
                ], 422);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Format file tidak didukung. Gunakan file .zip (berisi SHP) atau .geojson.',
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Handle file GeoJSON (.geojson / .json)
     */
    private function handleGeoJson($file): JsonResponse
    {
        $content = file_get_contents($file->getRealPath());
        $geojson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'File GeoJSON tidak valid (bukan JSON yang benar).',
            ], 422);
        }

        // Extract polygon from various GeoJSON structures
        $polygon = $this->extractPolygon($geojson);

        if (! $polygon) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan Polygon dalam file GeoJSON. Pastikan file berisi data polygon.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'geojson' => $polygon,
            'message' => 'File GeoJSON berhasil diproses.',
        ]);
    }

    /**
     * Handle file ZIP berisi Shapefile (.shp, .shx, .dbf)
     */
    private function handleShpZip($file): JsonResponse
    {
        $tempDir = storage_path('app/temp_shp_'.uniqid());
        mkdir($tempDir, 0755, true);

        try {
            // Extract ZIP
            $zip = new \ZipArchive;
            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Gagal membuka file ZIP.');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // Find .shp file in extracted contents
            $shpFile = $this->findFileWithExtension($tempDir, 'shp');
            if (! $shpFile) {
                throw new \Exception('File .shp tidak ditemukan dalam ZIP. Pastikan ZIP berisi file .shp, .shx, dan .dbf.');
            }

            // Check for required companion files
            $baseName = pathinfo($shpFile, PATHINFO_FILENAME);
            $dir = pathinfo($shpFile, PATHINFO_DIRNAME);

            $shxFile = $this->findCompanionFile($dir, $baseName, 'shx');
            $dbfFile = $this->findCompanionFile($dir, $baseName, 'dbf');

            if (! $shxFile || ! $dbfFile) {
                throw new \Exception('File .shx atau .dbf tidak ditemukan. Shapefile membutuhkan minimal 3 file: .shp, .shx, dan .dbf.');
            }

            // Read shapefile
            $shapefile = new ShapefileReader($shpFile);

            $polygons = [];
            while ($geometry = $shapefile->fetchRecord()) {
                // Skip deleted records
                if ($geometry->isDeleted()) {
                    continue;
                }

                // Skip empty geometries (null shapes)
                if ($geometry->isEmpty()) {
                    continue;
                }

                $geoArray = json_decode($geometry->getGeoJSON(false), true);

                if (! $geoArray) {
                    continue;
                }

                // Only accept polygon types
                $type = $geoArray['type'] ?? '';
                if (in_array($type, ['Polygon', 'MultiPolygon'])) {
                    $polygons[] = $geoArray;
                } elseif ($type === 'Feature' && isset($geoArray['geometry'])) {
                    $geoType = $geoArray['geometry']['type'] ?? '';
                    if (in_array($geoType, ['Polygon', 'MultiPolygon'])) {
                        $polygons[] = $geoArray['geometry'];
                    }
                }
            }

            // Close shapefile to release file handles before cleanup
            unset($shapefile);

            if (empty($polygons)) {
                throw new \Exception('Tidak ditemukan data Polygon dalam Shapefile. Pastikan file berisi data batas lahan berbentuk polygon.');
            }

            // If MultiPolygon, convert to single Polygon (take first polygon)
            $polygon = $polygons[0];
            if ($polygon['type'] === 'MultiPolygon') {
                $polygon = [
                    'type' => 'Polygon',
                    'coordinates' => $polygon['coordinates'][0],
                ];
            }

            return response()->json([
                'success' => true,
                'geojson' => $polygon,
                'total_shapes' => count($polygons),
                'message' => count($polygons) > 1
                    ? 'Shapefile berisi '.count($polygons).' polygon. Polygon pertama ditampilkan. Anda dapat mengedit titik-titiknya di peta.'
                    : 'Shapefile berhasil diproses.',
            ]);
        } finally {
            // Cleanup temp directory
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * Extract first Polygon from various GeoJSON structures.
     * Validates: min 4 points, ring closed, coordinates valid.
     */
    private function extractPolygon(array $geojson): ?array
    {
        $type = $geojson['type'] ?? null;

        if ($type === 'Polygon') {
            return $this->validatePolygon($geojson);
        }

        if ($type === 'MultiPolygon') {
            $polygon = [
                'type' => 'Polygon',
                'coordinates' => $geojson['coordinates'][0],
            ];

            return $this->validatePolygon($polygon);
        }

        if ($type === 'Feature' && isset($geojson['geometry'])) {
            return $this->extractPolygon($geojson['geometry']);
        }

        if ($type === 'FeatureCollection' && ! empty($geojson['features'])) {
            foreach ($geojson['features'] as $feature) {
                $polygon = $this->extractPolygon($feature);
                if ($polygon) {
                    return $polygon;
                }
            }
        }

        return null;
    }

    /**
     * Validate polygon structure:
     * - Ring must have at least 4 points (GeoJSON spec)
     * - Ring must be closed (first == last point)
     * - Coordinates must be valid (lng: -180..180, lat: -90..90)
     * Auto-closes ring if not closed.
     */
    private function validatePolygon(array $polygon): ?array
    {
        if (empty($polygon['coordinates']) || ! is_array($polygon['coordinates'])) {
            return null;
        }

        $rings = $polygon['coordinates'];
        $validatedRings = [];

        foreach ($rings as $ring) {
            if (! is_array($ring) || count($ring) < 3) {
                return null; // Need at least 3 unique points
            }

            // Validate each coordinate
            foreach ($ring as $point) {
                if (! is_array($point) || count($point) < 2) {
                    return null;
                }
                $lng = $point[0];
                $lat = $point[1];
                if (! is_numeric($lng) || ! is_numeric($lat)) {
                    return null;
                }
                if ($lng < -180 || $lng > 180 || $lat < -90 || $lat > 90) {
                    return null;
                }
            }

            // Auto-close ring if not closed
            $first = $ring[0];
            $last = $ring[count($ring) - 1];
            if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
                $ring[] = $first;
            }

            // After closing, must have at least 4 points
            if (count($ring) < 4) {
                return null;
            }

            $validatedRings[] = $ring;
        }

        return [
            'type' => 'Polygon',
            'coordinates' => $validatedRings,
        ];
    }

    /**
     * Find a file with specific extension recursively in a directory
     */
    private function findFileWithExtension(string $dir, string $ext): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (strtolower($file->getExtension()) === strtolower($ext)) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Find companion file (case-insensitive)
     */
    private function findCompanionFile(string $dir, string $baseName, string $ext): ?string
    {
        // Try exact case match first
        $path = $dir.DIRECTORY_SEPARATOR.$baseName.'.'.$ext;
        if (file_exists($path)) {
            return $path;
        }

        // Try uppercase extension
        $path = $dir.DIRECTORY_SEPARATOR.$baseName.'.'.strtoupper($ext);
        if (file_exists($path)) {
            return $path;
        }

        // Scan directory for case-insensitive match
        $files = scandir($dir);
        foreach ($files as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === strtolower($ext) &&
                strtolower(pathinfo($file, PATHINFO_FILENAME)) === strtolower($baseName)) {
                return $dir.DIRECTORY_SEPARATOR.$file;
            }
        }

        return null;
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
