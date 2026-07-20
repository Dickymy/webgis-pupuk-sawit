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
            font-family: 'Times-Roman', 'DejaVu Serif', 'Times New Roman', serif; 
            font-size: 10.5px; 
            color: #0f172a; 
            line-height: 1.5; 
            background: #ffffff;
        }

        /* Header */
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px double #1e293b; 
            padding-bottom: 12px; 
        }
        .header h1 { 
            font-size: 14px; 
            font-weight: 700; 
            color: #0f172a; 
            margin-bottom: 2px; 
            text-transform: uppercase;
            letter-spacing: 0.5px; 
        }
        .header h2 { 
            font-size: 11px; 
            font-weight: 600; 
            color: #334155; 
            margin-bottom: 4px; 
        }
        .header p { 
            font-size: 9px; 
            color: #64748b; 
        }

        /* Status Banner — print-friendly grayscale */
        .status-banner { 
            padding: 10px; 
            border: 1px solid #1e293b; 
            background: #f8fafc;
            border-radius: 4px; 
            margin-bottom: 16px; 
            text-align: center; 
            page-break-inside: avoid;
        }
        .status-banner .label { 
            font-size: 8px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-weight: 700; 
            color: #475569; 
        }
        .status-banner .value { 
            font-size: 15px; 
            font-weight: 800; 
            color: #0f172a;
            margin-top: 2px; 
        }

        /* Section */
        .section { 
            margin-bottom: 18px; 
            page-break-inside: avoid;
        }
        .section-title { 
            font-size: 10px; 
            font-weight: 700; 
            color: #0f172a; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            margin-bottom: 6px; 
            padding-bottom: 3px; 
            border-bottom: 1px solid #475569; 
        }

        /* Info Grid */
        .info-grid { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
        }
        .info-grid td { 
            padding: 5px 8px; 
            border: 1px solid #cbd5e1; 
            vertical-align: top; 
            font-size: 10px; 
        }
        .info-grid .label { 
            color: #475569; 
            width: 25%; 
            background: #f8fafc; 
            font-weight: 600; 
        }
        .info-grid .value { 
            color: #0f172a; 
            font-weight: 600; 
        }

        /* Logistik — table */
        .logistik-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
        }
        .logistik-table th, .logistik-table td { 
            border: 1px solid #1e293b; 
            padding: 6px 10px; 
            text-align: center; 
        }
        .logistik-table th { 
            background: #f1f5f9; 
            font-size: 9px; 
            font-weight: 700; 
            color: #0f172a; 
            text-transform: uppercase; 
        }
        .logistik-table td { 
            font-size: 11px; 
            font-weight: 700; 
            color: #0f172a;
        }

        /* Jadwal table */
        .jadwal-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
        }
        .jadwal-table th, .jadwal-table td { 
            border: 1px solid #cbd5e1; 
            padding: 5px 7px; 
            text-align: left; 
            font-size: 9.5px; 
        }
        .jadwal-table th { 
            background: #f8fafc; 
            font-weight: 700; 
            color: #0f172a; 
            font-size: 8.5px; 
            text-transform: uppercase; 
        }
        .jadwal-table .num { 
            text-align: right; 
            font-weight: 700; 
            color: #0f172a;
        }

        /* Saran box */
        .saran-box { 
            background: #f8fafc; 
            border: 1px solid #cbd5e1; 
            border-radius: 4px; 
            padding: 8px 10px; 
            margin-bottom: 12px; 
            page-break-inside: avoid;
        }
        .saran-box .title { 
            font-size: 9px; 
            font-weight: 700; 
            color: #0f172a; 
            text-transform: uppercase; 
            margin-bottom: 3px; 
        }
        .saran-box .text { 
            font-size: 10px; 
            color: #334155; 
            line-height: 1.5; 
        }

        /* Catatan box */
        .catatan-box { 
            background: #f8fafc; 
            border: 1px solid #cbd5e1; 
            border-radius: 4px; 
            padding: 8px 10px; 
            margin-bottom: 12px; 
            page-break-inside: avoid;
        }
        .catatan-box .title { 
            font-size: 9px; 
            font-weight: 700; 
            color: #0f172a; 
            text-transform: uppercase; 
            margin-bottom: 3px; 
        }
        .catatan-box .text { 
            font-size: 10px; 
            color: #334155; 
            line-height: 1.5; 
        }

        /* Masalah */
        .masalah-item { 
            display: inline-block; 
            background: #f8fafc; 
            border: 1px solid #cbd5e1; 
            border-radius: 3px; 
            padding: 2px 6px; 
            font-size: 9px; 
            margin: 2px 3px 2px 0; 
            color: #0f172a; 
            font-weight: 600; 
        }

        /* Pupuk card */
        .pupuk-card { 
            background: #f8fafc; 
            border: 1px solid #cbd5e1; 
            border-radius: 4px; 
            padding: 6px 10px; 
            margin-bottom: 5px; 
            page-break-inside: avoid;
        }
        .pupuk-card .nama { 
            font-size: 10.5px; 
            font-weight: 700; 
            color: #0f172a; 
            margin-bottom: 2px; 
        }
        .pupuk-card .detail { 
            font-size: 9px; 
            color: #334155; 
            line-height: 1.4; 
        }

        /* Rules table */
        .rules-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
        }
        .rules-table th, .rules-table td { 
            border: 1px solid #e2e8f0; 
            padding: 4px 6px; 
            text-align: left; 
            font-size: 8.5px; 
        }
        .rules-table th { 
            background: #f8fafc; 
            font-weight: 700; 
            color: #475569; 
        }
        .rules-table td { 
            color: #334155; 
        }

        .badge { 
            display: inline-block; 
            padding: 1px 4px; 
            border-radius: 2px; 
            font-size: 7.5px; 
            font-weight: 700; 
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        /* Disclaimer */
        .disclaimer { 
            background: #f8fafc; 
            border: 1px solid #cbd5e1; 
            border-radius: 4px; 
            padding: 6px 8px; 
            margin-bottom: 10px; 
            font-size: 8px; 
            color: #475569; 
            line-height: 1.4; 
            page-break-inside: avoid;
        }

        /* Footer */
        .footer { 
            margin-top: 15px; 
            padding-top: 8px; 
            border-top: 1px solid #cbd5e1; 
            text-align: center; 
            font-size: 8px; 
            color: #64748b; 
        }

        /* Meta info kecil */
        .meta-info { 
            font-size: 8.5px; 
            color: #475569; 
            margin-bottom: 12px; 
            padding: 5px 8px; 
            background: #f8fafc; 
            border-radius: 4px; 
            border: 1px solid #cbd5e1; 
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    {{-- ═══ 1. KOP SURAT RESMI ═══ --}}
    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 5px;">
        <tr>
            <td style="width: 80px; text-align: center; vertical-align: middle; border: none; padding: 0;">
                <img src="{{ public_path('img/logo-150.png') }}" style="width: 70px; height: 70px; object-fit: contain;" alt="Logo Suluh Tani">
            </td>
            <td style="text-align: center; vertical-align: middle; border: none; padding: 0; font-family: 'Times-Roman', 'DejaVu Serif', serif;">
                <div style="font-size: 15px; font-weight: bold; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">KELOMPOK TANI SULUH TANI</div>
                <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e293b; margin-top: 2px;">DESA RESAK, KECAMATAN BONGAN</div>
                <div style="font-size: 10px; font-weight: 500; color: #334155; margin-top: 2px;">Kabupaten Kutai Barat - Provinsi Kalimantan Timur</div>
            </td>
            <td style="width: 80px; border: none; padding: 0;"></td>
        </tr>
    </table>
    <div style="border-top: 2px solid #0f172a; border-bottom: 0.5px solid #0f172a; height: 3px; margin-bottom: 12px; margin-top: 4px;"></div>

    <div style="text-align: center; margin-bottom: 15px; font-family: 'Times-Roman', 'DejaVu Serif', serif;">
        <h2 style="font-size: 12px; font-weight: bold; color: #0f172a; text-transform: uppercase; text-decoration: underline; margin-bottom: 2px;">LAPORAN REKOMENDASI PEMUPUKAN KELAPA SAWIT</h2>
        <p style="font-size: 9px; color: #334155; font-weight: 600;">Nomor Dokumen: LHP-RBS/{{ $rekomendasiRbs->blokLahan->id }}/{{ $rekomendasiRbs->id }}/{{ $rekomendasiRbs->tanggal_analisis->format('Y') }}</p>
    </div>

    {{-- ═══ 2. STATUS — besar & jelas ═══ --}}
    @php
        $statusClass = match($rekomendasiRbs->status_kebutuhan_dominan) {
            'Darurat' => 'status-darurat',
            'Segera' => 'status-segera',
            'Normal' => 'status-normal',
            default => 'status-tunda',
        };
    @endphp
    <div class="status-banner {{ $statusClass }}">
        <div class="label">Status Kebutuhan Pemupukan</div>
        <div class="value">{{ \App\Models\RekomendasiRbs::labelStatus($rekomendasiRbs->status_kebutuhan_dominan) }}</div>
    </div>

    {{-- ═══ 3. INFO LAHAN — ringkas ═══ --}}
    <div class="section">
        <div class="section-title">Informasi Blok Lahan</div>
        <table class="info-grid">
            <tr>
                <td class="label">Nama Blok</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->nama_blok }}</td>
                <td class="label">Pemilik</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->nama_pemilik }}</td>
            </tr>
            <tr>
                <td class="label">Luas</td>
                <td class="value">{{ number_format($rekomendasiRbs->blokLahan->luas_ha, 2) }} Ha</td>
                <td class="label">SPH</td>
                <td class="value">{{ number_format($rekomendasiRbs->blokLahan->sph) }} pohon/Ha</td>
            </tr>
            @if($rekomendasiRbs->blokLahan->tahun_tanam)
            <tr>
                <td class="label">Umur Tanaman</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->umur_tanaman }} tahun ({{ $rekomendasiRbs->blokLahan->kategori_umur }})</td>
                <td class="label">Jenis Tanah</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->jenis_tanah }}</td>
            </tr>
            <tr>
                <td class="label">Topografi</td>
                <td class="value">{{ $rekomendasiRbs->blokLahan->topografi }}</td>
                <td class="label">Total Pohon</td>
                <td class="value">{{ number_format($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha) }} pohon</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ═══ 4. KEBUTUHAN PUPUK — yang paling dicari petani ═══ --}}
    @if($rekomendasiRbs->urea_estimasi_kg_per_pokok_tahun !== null || $rekomendasiRbs->kcl_estimasi_kg_per_pokok_tahun !== null || $rekomendasiRbs->total_urea || $rekomendasiRbs->total_kcl)
    <div class="section">
        <div class="section-title">Kebutuhan Pupuk</div>

        {{-- Rentang Referensi Pahan --}}
        @if($rekomendasiRbs->urea_min_kg_per_pokok_tahun)
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; font-size: 9px;">
            <p style="font-weight: 700; color: #1e40af; margin-bottom: 4px;">📖 Rentang Referensi Pahan (2013) — Tabel 9.13 & 9.14</p>
            <p style="color: #1e3a5f;">
                Urea: {{ number_format($rekomendasiRbs->urea_min_kg_per_pokok_tahun, 2) }}–{{ number_format($rekomendasiRbs->urea_max_kg_per_pokok_tahun, 2) }} kg/pokok/tahun
                &nbsp;|&nbsp;
                KCl: {{ number_format($rekomendasiRbs->kcl_min_kg_per_pokok_tahun, 2) }}–{{ number_format($rekomendasiRbs->kcl_max_kg_per_pokok_tahun, 2) }} kg/pokok/tahun
            </p>
            <p style="color: #64748b; font-size: 8px; margin-top: 2px;">
                Fase: {{ $rekomendasiRbs->label_fase }} · Umur saat observasi: {{ $rekomendasiRbs->umur_tanaman_snapshot ?? '-' }} thn · Strategi: {{ $rekomendasiRbs->strategi_estimasi_dosis ?? 'midpoint' }}
            </p>
        </div>
        @endif

        <p style="font-size: 8.5px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">ESTIMASI DOSIS KERJA SISTEM:</p>
        <table class="logistik-table">
            <thead>
                <tr>
                    <th>Jenis Pupuk</th>
                    <th>Dosis / Pokok</th>
                    <th>Total Kebutuhan</th>
                    <th>Jumlah Karung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="urea">Urea</td>
                    <td class="urea">{{ $rekomendasiRbs->dosis_urea ?? '-' }} kg</td>
                    <td class="urea">{{ $rekomendasiRbs->total_urea ? number_format($rekomendasiRbs->total_urea, 1) . ' kg' : '-' }}</td>
                    <td class="urea">{{ $rekomendasiRbs->karung_urea ?? '-' }} karung</td>
                </tr>
                <tr>
                    <td class="kcl">KCl</td>
                    <td class="kcl">{{ $rekomendasiRbs->dosis_kcl ?? '-' }} kg</td>
                    <td class="kcl">{{ $rekomendasiRbs->total_kcl ? number_format($rekomendasiRbs->total_kcl, 1) . ' kg' : '-' }}</td>
                    <td class="kcl">{{ $rekomendasiRbs->karung_kcl ?? '-' }} karung</td>
                </tr>
            </tbody>
        </table>
        <p style="font-size: 9px; color: #6b7280; text-align: right;">*1 karung = 50 kg</p>
    </div>
    @else
    <div class="section">
        <div class="section-title">Kebutuhan Pupuk</div>
        <div class="saran-box" style="border-left: 3px solid #475569; background-color: #f8fafc; padding: 10px;">
            <div class="title" style="color: #0f172a; font-weight: 700; font-size: 9px;">APLIKASI PUPUK KIMIA DITUNDA</div>
            <div class="text" style="color: #334155; font-size: 9.5px; line-height: 1.5; margin-top: 4px;">
                @if($rekomendasiRbs->status_kebutuhan_dominan === 'Darurat')
                    Rekomendasi pemupukan Urea &amp; KCl ditangguhkan sementara karena status lahan <strong>Defisiensi Berat / Darurat</strong>. Sangat disarankan untuk memprioritaskan tindakan korektif (seperti pengapuran dengan Dolomit) terlebih dahulu sebelum mengaplikasikan pupuk kimia guna memastikan penyerapan unsur hara optimal.
                @else
                    @php
                        $masalahStr = implode(' ', $rekomendasiRbs->masalah_teridentifikasi ?? []);
                        $pesanTunda = 'Kondisi pembatas lahan saat ini (genangan air atau kekeringan ekstrem) tidak ideal untuk pemupukan. Pemupukan kimia standar ditunda sementara waktu guna mencegah pemborosan pupuk akibat pencucian (leaching) atau penguapan (volatilisasi).';
                        if (str_contains($masalahStr, 'Waterlogging') || str_contains($masalahStr, 'tergenang') || str_contains($masalahStr, 'drainase') || str_contains($masalahStr, 'Drainase')) {
                            $pesanTunda = '<strong>Lahan Tergenang (Waterlogging)</strong>: Pemupukan tanah ditunda sementara. Air tergenang akan mencuci bersih pupuk (leaching) dan membuat akar kelapa sawit kekurangan oksigen untuk menyerap hara secara efektif. Disarankan untuk memprioritaskan perbaikan parit drainase terlebih dahulu.';
                        } elseif (str_contains($masalahStr, 'Kekeringan') || str_contains($masalahStr, 'kering') || str_contains($masalahStr, 'Kemarau')) {
                            $pesanTunda = '<strong>Cekaman Kekeringan</strong>: Pemupukan ditunda sementara. Tanah yang terlalu kering membuat pupuk tidak dapat larut untuk diserap akar, serta berisiko membakar akar rambut kelapa sawit. Disarankan fokus pada pemberian mulsing organik (seperti janjang kosong) untuk menjaga kelembaban dan menunggu hingga curah hujan cukup.';
                        } elseif (str_contains($masalahStr, 'Tua Renta') || str_contains($masalahStr, 'Tua')) {
                            $pesanTunda = '<strong>Tanaman Tua Renta</strong>: Pemupukan standar ditangguhkan untuk analisis ekonomi. Pohon berusia di atas 25 tahun memiliki efisiensi penyerapan hara yang sangat rendah. Disarankan mengevaluasi kelayakan replanting (peremajaan lahan) dibandingkan biaya pemeliharaan pupuk.';
                        }
                    @endphp
                    {!! $pesanTunda !!}
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ 5. JADWAL PEMUPUKAN — kapan harus bertindak ═══ --}}
    @if($rekomendasiRbs->jadwal_pemupukan && count($rekomendasiRbs->jadwal_pemupukan) > 0)
    <div class="section">
        <div class="section-title">Jadwal Pemupukan</div>
        @if($rekomendasiRbs->kondisiLahan?->ada_gulma_dominan || $rekomendasiRbs->kondisiLahan?->ada_serangan_hama)
        <div class="saran-box" style="border-left: 3px solid #d97706; background-color: #fffbeb; margin-bottom: 8px; padding: 8px 10px;">
            <div class="title" style="color: #b45309; font-weight: 700; font-size: 9px;">TINDAKAN SEBELUM PEMUPUKAN KIMIA</div>
            <div class="text" style="color: #92400e; font-size: 9px; line-height: 1.4; margin-top: 4px;">
                Sebelum melakukan pemupukan sesuai jadwal di bawah, pastikan tindakan berikut telah diselesaikan:
                <ul style="margin-left: 12px; margin-top: 3px; padding-left: 0; list-style-type: disc;">
                    @if($rekomendasiRbs->kondisiLahan?->ada_gulma_dominan)
                    <li><strong>Pengendalian Gulma</strong>: Bersihkan gulma di piringan pohon terlebih dahulu agar pupuk kimia terserap sepenuhnya oleh tanaman utama, bukan oleh gulma pengganggu.</li>
                    @endif
                    @if($rekomendasiRbs->kondisiLahan?->ada_serangan_hama)
                    <li><strong>Pengendalian Hama &amp; Penyakit</strong>: Tangani serangan hama aktif menggunakan pestisida/fungisida yang sesuai sebelum pemupukan dilakukan, agar tanaman dapat fokus pulih secara optimal.</li>
                    @endif
                </ul>
            </div>
        </div>
        @endif
        <table class="jadwal-table">
            <thead>
                <tr>
                    <th style="width:18%">Tahap</th>
                    <th style="width:22%">Waktu Aplikasi</th>
                    <th style="width:14%">Dosis/Pokok</th>
                    <th style="width:14%">Total Blok</th>
                    <th style="width:32%">Cara & Petunjuk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasiRbs->jadwal_pemupukan as $jadwal)
                @php
                    $hasUrea = isset($jadwal['urea_kg']) && $jadwal['urea_kg'] > 0;
                    $hasKcl = isset($jadwal['kcl_kg']) && $jadwal['kcl_kg'] > 0;
                    $isCombined = $hasUrea && $hasKcl;
                    
                    $dosisPokok = '-';
                    $totalKg = '-';
                    
                    if ($isCombined) {
                        $dosisU = isset($jadwal['urea_per_pokok']) ? $jadwal['urea_per_pokok'] : ($jadwal['urea_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                        $dosisK = isset($jadwal['kcl_per_pokok']) ? $jadwal['kcl_per_pokok'] : ($jadwal['kcl_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                        $dosisPokok = 'U: ' . number_format($dosisU, 1) . ' | K: ' . number_format($dosisK, 1);
                        $totalKg = 'U: ' . number_format($jadwal['urea_kg'], 0) . ' | K: ' . number_format($jadwal['kcl_kg'], 0);
                    } elseif ($hasUrea) {
                        $dosisU = isset($jadwal['urea_per_pokok']) ? $jadwal['urea_per_pokok'] : ($jadwal['urea_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                        $dosisPokok = number_format($dosisU, 2) . ' kg';
                        $totalKg = number_format($jadwal['urea_kg'], 1) . ' kg';
                    } elseif ($hasKcl) {
                        $dosisK = isset($jadwal['kcl_per_pokok']) ? $jadwal['kcl_per_pokok'] : ($jadwal['kcl_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                        $dosisPokok = number_format($dosisK, 2) . ' kg';
                        $totalKg = number_format($jadwal['kcl_kg'], 1) . ' kg';
                    }
                @endphp
                <tr>
                    <td style="font-weight:600;">{{ $jadwal['nama_tahap'] }}</td>
                    <td>{{ $jadwal['estimasi_waktu'] }}</td>
                    <td class="num">{{ $dosisPokok }}</td>
                    <td class="num">{{ $totalKg }}</td>
                    <td>
                        <span style="font-size:8.5px;">{{ $jadwal['metode_aplikasi'] }}</span>
                        @if(!empty($jadwal['catatan']))
                        <div style="font-size:8px; color:#92400e; margin-top:2px; font-style:italic;">⚠️ {{ $jadwal['catatan'] }}</div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ═══ 6. CATATAN DOSIS — peringatan penting ═══ --}}
    @if($rekomendasiRbs->catatan_dosis)
    <div class="catatan-box">
        <div class="title">⚠ Catatan Penting</div>
        <div class="text">{{ $rekomendasiRbs->catatan_dosis }}</div>
    </div>
    @endif

    {{-- ═══ 7. SARAN TINDAKAN — apa yang harus dilakukan ═══ --}}
    @if($rekomendasiRbs->saran_tindakan_utama)
    <div class="saran-box">
        <div class="title">Saran Tindakan</div>
        <div class="text">{{ $rekomendasiRbs->saran_tindakan_utama }}</div>
    </div>
    @endif

    {{-- ═══ 8. MASALAH TERIDENTIFIKASI ═══ --}}
    @if($rekomendasiRbs->masalah_teridentifikasi && count($rekomendasiRbs->masalah_teridentifikasi) > 0)
    <div class="section">
        <div class="section-title">Masalah Teridentifikasi</div>
        <div style="margin-bottom: 8px;">
            @foreach($rekomendasiRbs->masalah_teridentifikasi as $masalah)
                <span class="masalah-item">{{ $masalah }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══ 9. REKOMENDASI PUPUK SPESIFIK ═══ --}}
    @if($rekomendasiRbs->rekomendasi_pupuk && count($rekomendasiRbs->rekomendasi_pupuk) > 0)
    <div class="section">
        <div class="section-title">Rekomendasi Pupuk Spesifik</div>
        @foreach($rekomendasiRbs->rekomendasi_pupuk as $pupuk)
        <div class="pupuk-card">
            <div class="nama">{{ $pupuk['jenis_utama'] ?? '' }}@if(!empty($pupuk['jenis_pendukung'])) + {{ $pupuk['jenis_pendukung'] }}@endif</div>
            <div class="detail">
                @if(!empty($pupuk['dosis']))Dosis: {{ $pupuk['dosis'] }}<br>@endif
                @if(!empty($pupuk['metode']))Metode: {{ $pupuk['metode'] }}<br>@endif
                @if(!empty($pupuk['waktu']))Waktu: {{ $pupuk['waktu'] }}@endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ═══ 10. DETAIL TEKNIS (font kecil, untuk dokumentasi) ═══ --}}
    @if($rekomendasiRbs->rules_terpicu && count($rekomendasiRbs->rules_terpicu) > 0)
    <div class="section">
        <div class="section-title">Detail Teknis — Rules Terpicu ({{ $rekomendasiRbs->jumlah_rule_terpicu }})</div>
        <table class="rules-table">
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:40%">Indikasi</th>
                    <th style="width:25%">Pupuk</th>
                    <th style="width:15%">Status</th>
                    <th style="width:15%">Prioritas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasiRbs->rules_terpicu as $i => $rule)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $rule['indikasi'] ?? '-' }}</td>
                    <td>{{ $rule['pupuk'] ?? '-' }}</td>
                    <td>
                        @php $badgeClass = match($rule['status'] ?? '') {
                            'Darurat' => 'badge-darurat',
                            'Segera' => 'badge-segera',
                            'Normal' => 'badge-normal',
                            default => 'badge-tunda',
                        }; @endphp
                        <span class="badge {{ $badgeClass }}">{{ \App\Models\RekomendasiRbs::labelStatus($rule['status'] ?? '') }}</span>
                    </td>
                    <td>{{ $rule['prioritas'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ═══ 11. META INFO — validitas & confidence ═══ --}}
    <div class="meta-info">
        <strong>Validitas:</strong> {{ $rekomendasiRbs->validitas_rekomendasi ?? 'Estimasi Visual' }}
        &nbsp;·&nbsp;
        <strong>Tingkat Keandalan Data:</strong> {{ $rekomendasiRbs->confidence_label ?? 'Rendah' }} ({{ $rekomendasiRbs->confidence_score ?? 0 }}%)
        @if(!$rekomendasiRbs->data_cukup)
        &nbsp;·&nbsp; <span style="color: #dc2626;">Data observasi belum lengkap</span>
        @endif
    </div>

    {{-- ═══ 12. DISCLAIMER ═══ --}}
    <div class="disclaimer">
        <strong>Catatan:</strong> Rekomendasi ini dihasilkan oleh sistem berbasis aturan (Rule-Based System) berdasarkan data observasi lapangan. Hasil ini bersifat rekomendasi dan bukan pengganti analisis laboratorium tanah/daun. Untuk keputusan yang lebih akurat, disarankan melengkapi dengan hasil uji lab.
    </div>

    {{-- ═══ DISCLAIMER ═══ --}}
    <div style="margin-top: 12px; padding: 8px 10px; background: #fefce8; border: 1px solid #fde047; border-radius: 6px; font-size: 8.5px; color: #713f12; line-height: 1.5;">
        <strong>⚠️ Disclaimer:</strong> Rekomendasi ini merupakan estimasi awal berbasis data blok, observasi visual, dan basis aturan (Rule-Based System). Hasil ini bukan pengganti analisis laboratorium tanah/daun atau keputusan ahli agronomi. Perhitungan kuantitatif dibatasi pada Urea dan MOP/KCl. Unsur P, Mg, B, dan unsur lain tetap dapat diperlukan sesuai kondisi tanaman dan hasil evaluasi ahli.
    </div>


    {{-- ═══ FOOTER ═══ --}}
    <div class="footer">
        Sistem Pendukung Keputusan Pemupukan Kelapa Sawit (SawitGIS)<br>
        Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WITA
    </div>
</body>
</html>
