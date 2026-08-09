<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekomendasi Pemupukan — {{ $rekomendasiRbs->blokLahan->nama_blok }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            size: A4;
            margin: 20mm;
        }
        
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 11pt; 
            color: #000000; 
            line-height: 1.5; 
            background: #ffffff;
        }

        /* Header */
        .kop-surat {
            width: 100%;
            border-bottom: 3px double #000000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        
        .kop-surat table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .kop-surat td {
            vertical-align: middle;
            text-align: center;
        }
        
        .kop-surat h1 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            padding: 0;
        }
        
        .kop-surat h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 3px 0;
            padding: 0;
        }
        
        .kop-surat p {
            font-size: 10pt;
            margin: 0;
            padding: 0;
        }
        
        .report-title {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .report-title h3 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        
        .report-title p {
            font-size: 10pt;
        }

        /* Section */
        .section { 
            margin-bottom: 15px; 
            page-break-inside: avoid;
        }
        .section-title { 
            font-size: 11pt; 
            font-weight: bold; 
            margin-bottom: 8px; 
            text-transform: uppercase;
        }

        /* Standard Table */
        .standard-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
        }
        .standard-table th, .standard-table td { 
            border: 1px solid #000000; 
            padding: 6px 8px; 
            font-size: 10pt;
            vertical-align: top;
        }
        .standard-table th { 
            font-weight: bold; 
            text-align: center;
        }
        
        .info-table td.label {
            width: 25%;
            font-weight: bold;
        }
        .info-table td.value {
            width: 25%;
        }

        /* Boxed Text (Saran, Catatan) */
        .text-box { 
            border: 1px solid #000000; 
            padding: 8px 12px; 
            margin-bottom: 12px; 
            page-break-inside: avoid;
            font-size: 10pt;
        }
        .text-box .title { 
            font-weight: bold; 
            margin-bottom: 5px; 
            text-transform: uppercase;
        }

        /* Unordered lists */
        ul {
            margin-left: 20px;
            padding-left: 0;
        }

        /* Footer */
        .footer { 
            margin-top: 30px; 
            padding-top: 10px; 
            text-align: center; 
            font-size: 9pt; 
        }
        
        .meta-info {
            font-size: 9pt;
            margin-bottom: 15px;
        }
        
        .disclaimer {
            font-size: 9pt;
            font-style: italic;
            text-align: justify;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>

    {{-- ═══ 1. KOP SURAT RESMI ═══ --}}
    <div class="kop-surat">
        <table>
            <tr>
                <td style="width: 90px;">
                    <img src="{{ public_path('img/logo-150.png') }}" style="width: 80px; height: 80px; object-fit: contain;" alt="Logo Suluh Tani">
                </td>
                <td>
                    <h1>KELOMPOK TANI SULUH TANI</h1>
                    <h2>DESA JAMBUK MAKMUR, KECAMATAN BONGAN</h2>
                    <p>Kabupaten Kutai Barat - Provinsi Kalimantan Timur</p>
                </td>
                <td style="width: 90px;"></td>
            </tr>
        </table>
    </div>

    <div class="report-title">
        <h3>LAPORAN REKOMENDASI PEMUPUKAN KELAPA SAWIT</h3>
        <p>Nomor Dokumen: LHP-RBS/{{ $rekomendasiRbs->blokLahan->id }}/{{ $rekomendasiRbs->id }}/{{ $rekomendasiRbs->tanggal_analisis->format('Y') }}</p>
    </div>

    {{-- ═══ 2. STATUS ═══ --}}
    <div class="section">
        <table class="standard-table info-table">
            <tr>
                <td class="label">Kondisi Tanaman</td>
                <td class="value font-bold">{{ $rekomendasiRbs->label_kondisi_tanaman }}</td>
                <td class="label">Kesiapan Pemupukan</td>
                <td class="value font-bold">{{ $rekomendasiRbs->label_kelayakan }}</td>
            </tr>
        </table>
        @if($rekomendasiRbs->alasan_kelayakan)
        <p style="font-size: 10pt; margin-top: -5px; margin-bottom: 10px;"><strong>Catatan Kesiapan:</strong> {{ $rekomendasiRbs->alasan_kelayakan }}</p>
        @endif
    </div>

    {{-- ═══ 3. INFO LAHAN ═══ --}}
    <div class="section">
        <div class="section-title">A. INFORMASI BLOK LAHAN</div>
        <table class="standard-table info-table">
            <tr>
                <td class="label">Nama Blok</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->nama_blok }}</td>
                <td class="label">Pemilik</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->nama_pemilik }}</td>
            </tr>
            <tr>
                <td class="label">Luas</td>
                <td class="value">{{ number_format($rekomendasiRbs->luas_ha_snapshot ?? $rekomendasiRbs->blokLahan->luas_ha, 2) }} Ha</td>
                <td class="label">SPH</td>
                <td class="value">{{ number_format($rekomendasiRbs->sph_snapshot ?? $rekomendasiRbs->blokLahan->sph) }} pohon/Ha</td>
            </tr>
            @if($rekomendasiRbs->blokLahan->tahun_tanam)
            <tr>
                <td class="label">Umur saat Observasi</td>
                <td class="value">{{ $rekomendasiRbs->umur_tanaman_snapshot ?? $rekomendasiRbs->blokLahan->umur_tanaman }} tahun</td>
                <td class="label">Fase Tanaman</td>
                <td class="value">{{ $rekomendasiRbs->label_fase }}</td>
            </tr>
            <tr>
                <td class="label">Jenis Tanah</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->jenis_tanah }}</td>
                <td class="label">Total Pohon</td>
                <td class="value">{{ number_format($rekomendasiRbs->jumlah_pokok_snapshot ?? $rekomendasiRbs->blokLahan->jumlah_pokok_aktual) }} pohon</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ═══ 4. KEBUTUHAN PUPUK ═══ --}}
    <div class="section">
        <div class="section-title">B. KEBUTUHAN PUPUK</div>

        {{-- Rentang Referensi Pahan --}}
        @if($rekomendasiRbs->urea_min_kg_per_pokok_tahun)
        <div class="text-box">
            <div class="title">Acuan Dosis: Iyung Pahan (2013)</div>
            <p>
                Urea: {{ number_format($rekomendasiRbs->urea_min_kg_per_pokok_tahun, 2) }}–{{ number_format($rekomendasiRbs->urea_max_kg_per_pokok_tahun, 2) }} kg/pokok/tahun
                &nbsp;|&nbsp;
                KCl: {{ number_format($rekomendasiRbs->kcl_min_kg_per_pokok_tahun, 2) }}–{{ number_format($rekomendasiRbs->kcl_max_kg_per_pokok_tahun, 2) }} kg/pokok/tahun
            </p>
            <p style="font-size: 9pt; margin-top: 3px;">
                Fase: {{ $rekomendasiRbs->label_fase }} | Umur observasi: {{ $rekomendasiRbs->umur_tanaman_snapshot ?? '-' }} thn | Strategi: {{ $rekomendasiRbs->strategi_estimasi_dosis ?? 'midpoint' }}
            </p>
        </div>
        @endif

        {{-- Kebutuhan Tahunan --}}
        @if($rekomendasiRbs->urea_total_estimasi_tahunan || $rekomendasiRbs->kcl_total_estimasi_tahunan)
        <p class="font-bold" style="margin-bottom: 5px;">Kebutuhan Tahunan (Total):</p>
        <table class="standard-table">
            <thead>
                <tr>
                    <th>Jenis Pupuk</th>
                    <th>Dosis / Pokok / Tahun</th>
                    <th>Total Min</th>
                    <th>Total Estimasi</th>
                    <th>Total Max</th>
                    <th>Karung (50 kg)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">Urea</td>
                    <td class="text-center">{{ $rekomendasiRbs->urea_estimasi_kg_per_pokok_tahun ? number_format($rekomendasiRbs->urea_estimasi_kg_per_pokok_tahun, 2) . ' kg' : '-' }}</td>
                    <td class="text-center">{{ $rekomendasiRbs->urea_total_min_tahunan ? number_format($rekomendasiRbs->urea_total_min_tahunan, 1) . ' kg' : '-' }}</td>
                    <td class="text-center font-bold">{{ $rekomendasiRbs->urea_total_estimasi_tahunan ? number_format($rekomendasiRbs->urea_total_estimasi_tahunan, 1) . ' kg' : '-' }}</td>
                    <td class="text-center">{{ $rekomendasiRbs->urea_total_max_tahunan ? number_format($rekomendasiRbs->urea_total_max_tahunan, 1) . ' kg' : '-' }}</td>
                    <td class="text-center">{{ $rekomendasiRbs->urea_karung_estimasi_tahunan ?? '-' }} karung</td>
                </tr>
                <tr>
                    <td class="text-center">KCl</td>
                    <td class="text-center">{{ $rekomendasiRbs->kcl_estimasi_kg_per_pokok_tahun ? number_format($rekomendasiRbs->kcl_estimasi_kg_per_pokok_tahun, 2) . ' kg' : '-' }}</td>
                    <td class="text-center">{{ $rekomendasiRbs->kcl_total_min_tahunan ? number_format($rekomendasiRbs->kcl_total_min_tahunan, 1) . ' kg' : '-' }}</td>
                    <td class="text-center font-bold">{{ $rekomendasiRbs->kcl_total_estimasi_tahunan ? number_format($rekomendasiRbs->kcl_total_estimasi_tahunan, 1) . ' kg' : '-' }}</td>
                    <td class="text-center">{{ $rekomendasiRbs->kcl_total_max_tahunan ? number_format($rekomendasiRbs->kcl_total_max_tahunan, 1) . ' kg' : '-' }}</td>
                    <td class="text-center">{{ $rekomendasiRbs->kcl_karung_estimasi_tahunan ?? '-' }} karung</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- Aplikasi Saat Ini --}}
        @php
            $ureaAplikasi = $rekomendasiRbs->urea_aplikasi_saat_ini ?? 0;
            $kclAplikasi = $rekomendasiRbs->kcl_aplikasi_saat_ini ?? 0;
            $isDitunda = ($ureaAplikasi == 0 && $kclAplikasi == 0 && ($rekomendasiRbs->urea_total_estimasi_tahunan > 0 || $rekomendasiRbs->kcl_total_estimasi_tahunan > 0));
        @endphp

        @if($isDitunda)
        <div class="text-box" style="margin-top: 10px;">
            <div class="title">Status Aplikasi Saat Ini: DITUNDA (0 kg)</div>
            <p>
                Kebutuhan tahunan tetap tercatat di atas. Aplikasi saat ini ditunda karena kondisi kelayakan belum terpenuhi.
                @if($rekomendasiRbs->alasan_kelayakan)
                <br><strong>Alasan:</strong> {{ $rekomendasiRbs->alasan_kelayakan }}
                @endif
            </p>
        </div>
        @elseif($ureaAplikasi > 0 || $kclAplikasi > 0)
        <p class="font-bold" style="margin-top: 10px; margin-bottom: 5px;">Tahap Aktif Saat Ini{{ $rekomendasiRbs->active_stage ? ' (Tahap ' . $rekomendasiRbs->active_stage . ')' : '' }}:</p>
        <table class="standard-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Jenis Pupuk</th>
                    <th style="width: 30%;">Jumlah Tahap Aktif</th>
                    <th style="width: 30%;">Estimasi Karung (50 kg)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">Urea</td>
                    <td class="text-center font-bold">{{ number_format($ureaAplikasi, 1) }} kg</td>
                    <td class="text-center">{{ $ureaAplikasi > 0 ? (int) ceil($ureaAplikasi / 50) : 0 }} karung</td>
                </tr>
                <tr>
                    <td class="text-center">KCl</td>
                    <td class="text-center font-bold">{{ number_format($kclAplikasi, 1) }} kg</td>
                    <td class="text-center">{{ $kclAplikasi > 0 ? (int) ceil($kclAplikasi / 50) : 0 }} karung</td>
                </tr>
            </tbody>
        </table>
        @if($rekomendasiRbs->alasan_tahap)
        <p style="font-size: 10pt; margin-top: 5px;"><strong>Catatan:</strong> {{ $rekomendasiRbs->alasan_tahap }}</p>
        @endif
        @endif
        
        <p style="font-size: 9pt; text-align: right; margin-top: 5px;">*Keterangan: 1 karung = 50 kg (pembulatan ke atas)</p>
    </div>

    {{-- ═══ 5. JADWAL PEMUPUKAN ═══ --}}
    @if($rekomendasiRbs->jadwal_pemupukan && count($rekomendasiRbs->jadwal_pemupukan) > 0)
    <div class="section">
        <div class="section-title">C. JADWAL PEMUPUKAN</div>
        @if($rekomendasiRbs->kondisiLahan?->ada_gulma_dominan || $rekomendasiRbs->kondisiLahan?->ada_serangan_hama)
        <div class="text-box" style="margin-bottom: 10px;">
            <div class="title">Tindakan Pendahuluan (Sebelum Pemupukan)</div>
            <p style="margin-bottom: 5px;">Sebelum melakukan pemupukan sesuai jadwal di bawah, pastikan tindakan berikut telah diselesaikan:</p>
            <ul>
                @if($rekomendasiRbs->kondisiLahan?->ada_gulma_dominan)
                <li><strong>Pengendalian Gulma</strong>: Bersihkan gulma di piringan pohon terlebih dahulu agar pupuk kimia terserap sepenuhnya oleh tanaman utama.</li>
                @endif
                @if($rekomendasiRbs->kondisiLahan?->ada_serangan_hama)
                <li><strong>Pengendalian Hama &amp; Penyakit</strong>: Tangani serangan hama aktif menggunakan pestisida/fungisida yang sesuai sebelum pemupukan dilakukan.</li>
                @endif
            </ul>
        </div>
        @endif
        
        <table class="standard-table">
            <thead>
                <tr>
                    <th style="width:15%">Tahap</th>
                    <th style="width:20%">Waktu Aplikasi</th>
                    <th style="width:15%">Dosis/Pokok</th>
                    <th style="width:15%">Total Blok</th>
                    <th style="width:35%">Cara & Petunjuk Aplikasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasiRbs->jadwal_pemupukan as $jadwal)
                @php
                    $hasUrea = isset($jadwal['urea_kg']) && $jadwal['urea_kg'] > 0;
                    $hasKcl = isset($jadwal['kcl_kg']) && $jadwal['kcl_kg'] > 0;
                    $isCombined = $hasUrea && $hasKcl;
                    
                    $jumlahPokokPdf = $rekomendasiRbs->jumlah_pokok_snapshot ?? $rekomendasiRbs->blokLahan->jumlah_pokok_aktual;
                    
                    $dosisPokok = '-';
                    $totalKg = '-';
                    
                    if ($isCombined) {
                        $dosisU = isset($jadwal['urea_per_pokok']) ? $jadwal['urea_per_pokok'] : ($jadwal['urea_kg'] / max(1, $jumlahPokokPdf));
                        $dosisK = isset($jadwal['kcl_per_pokok']) ? $jadwal['kcl_per_pokok'] : ($jadwal['kcl_kg'] / max(1, $jumlahPokokPdf));
                        $dosisPokok = 'U: ' . number_format($dosisU, 1) . ' | K: ' . number_format($dosisK, 1);
                        $totalKg = 'U: ' . number_format($jadwal['urea_kg'], 0) . ' | K: ' . number_format($jadwal['kcl_kg'], 0);
                    } elseif ($hasUrea) {
                        $dosisU = isset($jadwal['urea_per_pokok']) ? $jadwal['urea_per_pokok'] : ($jadwal['urea_kg'] / max(1, $jumlahPokokPdf));
                        $dosisPokok = number_format($dosisU, 2) . ' kg';
                        $totalKg = number_format($jadwal['urea_kg'], 1) . ' kg';
                    } elseif ($hasKcl) {
                        $dosisK = isset($jadwal['kcl_per_pokok']) ? $jadwal['kcl_per_pokok'] : ($jadwal['kcl_kg'] / max(1, $jumlahPokokPdf));
                        $dosisPokok = number_format($dosisK, 2) . ' kg';
                        $totalKg = number_format($jadwal['kcl_kg'], 1) . ' kg';
                    }
                @endphp
                <tr>
                    <td class="text-center font-bold">{{ $jadwal['nama_tahap'] }}</td>
                    <td class="text-center">{{ $jadwal['estimasi_waktu'] }}</td>
                    <td class="text-center">{{ $dosisPokok }}</td>
                    <td class="text-center">{{ $totalKg }}</td>
                    <td>
                        {{ $jadwal['metode_aplikasi'] }}
                        @if(!empty($jadwal['catatan']))
                        <div style="font-size: 9pt; font-style: italic; margin-top: 4px;">Catatan: {{ $jadwal['catatan'] }}</div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ═══ 6. CATATAN DOSIS & SARAN TINDAKAN ═══ --}}
    @if($rekomendasiRbs->catatan_dosis || $rekomendasiRbs->saran_tindakan_utama)
    <div class="section">
        <div class="section-title">D. REKOMENDASI DAN CATATAN TAMBAHAN</div>
        
        @if($rekomendasiRbs->catatan_dosis)
        <div class="text-box">
            <div class="title">Catatan Mengenai Dosis:</div>
            <p>{{ $rekomendasiRbs->catatan_dosis }}</p>
        </div>
        @endif
        
        @if($rekomendasiRbs->saran_tindakan_utama)
        <div class="text-box">
            <div class="title">Saran Tindakan Agronomis:</div>
            <p>{{ $rekomendasiRbs->saran_tindakan_utama }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- ═══ 7. MASALAH TERIDENTIFIKASI & REKOMENDASI SPESIFIK ═══ --}}
    @if(($rekomendasiRbs->masalah_teridentifikasi && count($rekomendasiRbs->masalah_teridentifikasi) > 0) || ($rekomendasiRbs->rekomendasi_pupuk && count($rekomendasiRbs->rekomendasi_pupuk) > 0))
    <div class="section">
        <div class="section-title">E. KONDISI KHUSUS LAHAN</div>
        
        @if($rekomendasiRbs->masalah_teridentifikasi && count($rekomendasiRbs->masalah_teridentifikasi) > 0)
        <p class="font-bold" style="margin-bottom: 5px;">Masalah yang Teridentifikasi:</p>
        <ul style="margin-bottom: 10px;">
            @foreach($rekomendasiRbs->masalah_teridentifikasi as $masalah)
                <li>{{ $masalah }}</li>
            @endforeach
        </ul>
        @endif
        
        @if($rekomendasiRbs->rekomendasi_pupuk && count($rekomendasiRbs->rekomendasi_pupuk) > 0)
        <p class="font-bold" style="margin-bottom: 5px;">Rekomendasi Pupuk Tambahan (Spesifik):</p>
        <table class="standard-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Jenis Pupuk</th>
                    <th style="width: 25%;">Dosis</th>
                    <th style="width: 20%;">Waktu</th>
                    <th style="width: 25%;">Metode</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasiRbs->rekomendasi_pupuk as $pupuk)
                <tr>
                    <td>{{ $pupuk['jenis_utama'] ?? '' }}@if(!empty($pupuk['jenis_pendukung'])) + {{ $pupuk['jenis_pendukung'] }}@endif</td>
                    <td class="text-center">{{ $pupuk['dosis'] ?? '-' }}</td>
                    <td class="text-center">{{ $pupuk['waktu'] ?? '-' }}</td>
                    <td class="text-center">{{ $pupuk['metode'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif

    {{-- ═══ 8. DETAIL TEKNIS ═══ --}}
    @if($rekomendasiRbs->rules_terpicu && count($rekomendasiRbs->rules_terpicu) > 0)
    <div class="section">
        <div class="section-title">F. LAMPIRAN TEKNIS ({{ $rekomendasiRbs->jumlah_rule_terpicu }} Aturan Terpicu)</div>
        <table class="standard-table">
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:45%">Indikasi</th>
                    <th style="width:20%">Tindakan/Pupuk</th>
                    <th style="width:15%">Status</th>
                    <th style="width:15%">Prioritas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasiRbs->rules_terpicu as $i => $rule)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $rule['indikasi'] ?? '-' }}</td>
                    <td>{{ $rule['pupuk'] ?? '-' }}</td>
                    <td class="text-center">{{ \App\Models\RekomendasiRbs::labelStatus($rule['status'] ?? '') }}</td>
                    <td class="text-center">{{ $rule['prioritas'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ═══ 9. RIWAYAT REALISASI PEMUPUKAN ═══ --}}
    @if(isset($realisasis) && $realisasis->count() > 0)
    <div class="section">
        <div class="section-title">G. RIWAYAT REALISASI PEMUPUKAN</div>
        <table class="standard-table" style="font-size: 9pt;">
            <thead>
                <tr>
                    <th>Tahap</th>
                    <th>Tanggal</th>
                    <th>Urea (Rencana)</th>
                    <th>Urea (Realisasi)</th>
                    <th>KCl (Rencana)</th>
                    <th>KCl (Realisasi)</th>
                    <th>Status</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalUreaRealisasi = 0;
                    $totalKclRealisasi = 0;
                @endphp
                @foreach($realisasis as $r)
                @php
                    $isBatal = $r->status_realisasi === 'BATAL';
                    if (!$isBatal) {
                        $totalUreaRealisasi += (float) $r->urea_realisasi_kg;
                        $totalKclRealisasi += (float) $r->kcl_realisasi_kg;
                    }
                @endphp
                <tr>
                    <td class="text-center">Tahap {{ $r->tahap }}</td>
                    <td class="text-center">{{ $r->tanggal_realisasi?->format('d/m/Y') }}</td>
                    <td class="text-center">{{ number_format($r->urea_rencana_kg, 1) }} kg</td>
                    <td class="text-center font-bold">{{ $isBatal ? '-' : number_format($r->urea_realisasi_kg, 1) . ' kg' }}</td>
                    <td class="text-center">{{ number_format($r->kcl_rencana_kg, 1) }} kg</td>
                    <td class="text-center font-bold">{{ $isBatal ? '-' : number_format($r->kcl_realisasi_kg, 1) . ' kg' }}</td>
                    <td class="text-center">{{ $isBatal ? 'Batal' : ($r->status_realisasi === 'SELESAI' ? 'Selesai' : 'Sebagian') }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($r->catatan_pelaksana, 40) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="font-bold text-right">Total Realisasi Aktif:</td>
                    <td class="text-center font-bold">{{ number_format($totalUreaRealisasi, 1) }} kg</td>
                    <td></td>
                    <td class="text-center font-bold">{{ number_format($totalKclRealisasi, 1) }} kg</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        {{-- Info Program --}}
        <div style="margin-top: 10px; font-size: 10pt;">
            @if($rekomendasiRbs->urea_sisa_tahunan !== null)
            <p><strong>Sisa Kebutuhan Tahunan:</strong> Urea {{ number_format($rekomendasiRbs->urea_sisa_tahunan, 1) }} kg | KCl {{ number_format($rekomendasiRbs->kcl_sisa_tahunan ?? 0, 1) }} kg</p>
            @endif
            @if($rekomendasiRbs->status_stage)
            <p><strong>Status Program:</strong> {{ \App\Services\CurrentApplicationCalculator::labelStatusStage($rekomendasiRbs->status_stage) }}</p>
            @endif
            @if($rekomendasiRbs->tanggal_minimum_tahap_berikutnya)
            <p><strong>Tanggal Minimum Tahap Berikutnya:</strong> {{ $rekomendasiRbs->tanggal_minimum_tahap_berikutnya->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>
    @endif

    @php
        $currentDataReady = $observationCompleteness['can_run_diagnosis'] ?? $rekomendasiRbs->data_cukup;
        $dataPendukungKurang = collect($observationCompleteness['missing_fields'] ?? $rekomendasiRbs->data_kurang ?? [])->filter()->values();
    @endphp
    {{-- ═══ 10. META INFO ═══ --}}
    <div class="meta-info">
        <strong>Data Analisis:</strong> {{ $currentDataReady ? 'Tersedia' : 'Belum lengkap' }}
        @if($dataPendukungKurang->isNotEmpty())
        <br><strong>Kekurangan Data Pendukung:</strong> {{ $dataPendukungKurang->implode(', ') }}
        @endif
    </div>

    {{-- ═══ 11. DISCLAIMER ═══ --}}
    <div class="disclaimer">
        <strong>Disclaimer:</strong> Estimasi sistem merupakan nilai kerja dari acuan Iyung Pahan (2013) dan bukan pengganti analisis laboratorium atau rekomendasi agronomis lapangan. Perhitungan kuantitatif dibatasi pada Urea dan MOP/KCl. Unsur P, Mg, B, dan unsur lain tetap dapat diperlukan sesuai kondisi tanaman dan hasil evaluasi ahli.
    </div>

    {{-- ═══ FOOTER ═══ --}}
    <div class="footer">
        Sistem Pendukung Keputusan Pemupukan Kelapa Sawit (SawitGIS)<br>
        Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WITA
    </div>
</body>
</html>
