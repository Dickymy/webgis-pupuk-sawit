@php
$isEdit=isset($kondisiLahan);
$get=fn($name,$default=null)=>old($name,$isEdit?data_get($kondisiLahan,$name):$default);
$selectedBlock=(string)old('blok_lahan_id',$isEdit?$kondisiLahan->blok_lahan_id:($selectedBlokId??''));
$selectedBlockModel=$selectedBlock!==''?$bloks->firstWhere('id',(int)$selectedBlock):null;
$selectedOwner=(string)old('anggota_id',$selectedBlockModel?->anggota_id??'');
$rainMode=old('metode_data_hujan',$isEdit?($kondisiLahan->curah_hujan_mm_bulanan!==null?'data_angka':($kondisiLahan->curah_hujan_kategori!==null?'perkiraan':'tidak_tersedia')):'data_angka');
$labels=config('observation.leaf_condition_labels',[]);
$descriptions=config('observation.leaf_condition_descriptions',[]);
$specialLeaves=config('observation.unmatched_leaf_values',[]);
$storedLeaf=old('warna_daun',$isEdit?($kondisiLahan->warna_daun??'__tidak_pasti'):'');
$selectedLeaf=in_array($storedLeaf,array_merge($leafConditions,array_keys($specialLeaves)),true)?$storedLeaf:($storedLeaf!==''?'__tidak_pasti':'');
$owners=$bloks->pluck('anggota')->filter()->unique('id')->sortBy('nama')->values();
$existingPhotoUrl=$isEdit&&$kondisiLahan->foto_observasi_path?route('kondisi-lahan.photo',$kondisiLahan):null;
$photoMarkedForRemoval=old('hapus_foto','0')==='1';
$control='w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100';
@endphp

@include('components.observation-stepper')

<section data-observation-step="1" class="observation-card">
 <div class="observation-heading"><span>1</span><div><h2>Pilih blok yang diperiksa</h2><p>Pilih anggota terlebih dahulu. Jika hanya memiliki satu blok, blok akan terpilih otomatis.</p></div></div>
 <div class="grid gap-4 lg:grid-cols-3">
  <div>
   <label for="anggota-select" class="field-label">Nama anggota <b>*</b></label>
   <select id="anggota-select" name="anggota_id" required class="{{ $control }} @error('anggota_id') border-red-400 @enderror">
    <option value="">Pilih anggota</option>
    @foreach($owners as $owner)<option value="{{ $owner->id }}" {{ $selectedOwner===(string)$owner->id?'selected':'' }}>{{ $owner->nama }}</option>@endforeach
   </select>
   @error('anggota_id')<p class="field-error">{{ $message }}</p>@enderror
  </div>
  <div>
   <label for="blok-lahan-select" class="field-label">Blok lahan <b>*</b></label>
   <select id="blok-lahan-select" name="blok_lahan_id" required class="{{ $control }} @error('blok_lahan_id') border-red-400 @enderror">
    <option value="">Pilih anggota terlebih dahulu</option>
    @foreach($bloks as $blok)<option value="{{ $blok->id }}" data-owner-id="{{ $blok->anggota_id }}" {{ $selectedBlock===(string)$blok->id?'selected':'' }}>{{ $blok->nama_blok }} — {{ number_format((float)$blok->luas_ha,2,',','.') }} Ha</option>@endforeach
   </select>
   <p id="block-choice-help" class="field-help">Daftar blok akan mengikuti anggota yang dipilih.</p>
   @error('blok_lahan_id')<p class="field-error">{{ $message }}</p>@enderror
  </div>
  <div>
   <label for="tanggal-observasi" class="field-label">Tanggal observasi <b>*</b></label>
   <input id="tanggal-observasi" type="date" name="tanggal_observasi" value="{{ old('tanggal_observasi',$isEdit?$kondisiLahan->tanggal_observasi?->format('Y-m-d'):now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required class="{{ $control }}">
   @error('tanggal_observasi')<p class="field-error">{{ $message }}</p>@enderror
  </div>
 </div>
 <div id="selected-block-card" class="mt-4 hidden rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/30">
  <div><strong id="block-name" class="text-base text-slate-900 dark:text-white">-</strong><p id="block-owner" class="mt-0.5 text-xs text-slate-500">-</p></div>
  <div id="block-facts" class="mt-4 grid grid-cols-2 gap-3 text-xs sm:grid-cols-3 xl:grid-cols-6"></div>
  <p class="mt-3 border-t border-emerald-200 pt-3 text-xs text-emerald-800 dark:border-emerald-800 dark:text-emerald-200">Perkiraan jumlah pohon dihitung dari luas × pohon per hektare (SPH).</p>
 </div>
 <div id="banner-tbm" class="mt-4 hidden rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-800 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-200">Blok berada pada fase tanaman belum menghasilkan. Dosis tetap mengikuti umur dan fase tanaman.</div>
 <div class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-700">
  <label for="tanggal-pemupukan-terakhir" class="field-label">Pemupukan terakhir <em>(jika diketahui)</em></label>
  <div class="grid gap-3 lg:grid-cols-3"><input id="tanggal-pemupukan-terakhir" type="date" name="tanggal_pemupukan_terakhir" value="{{ old('tanggal_pemupukan_terakhir',$isEdit?$kondisiLahan->tanggal_pemupukan_terakhir?->format('Y-m-d'):null) }}" max="{{ now()->format('Y-m-d') }}" class="{{ $control }}"><p id="last-date-help" class="field-help lg:col-span-2">Diisi otomatis dari Pelaksanaan Pemupukan bila tersedia. Kosongkan jika tidak diketahui.</p></div>
  @error('tanggal_pemupukan_terakhir')<p class="field-error">{{ $message }}</p>@enderror
 </div>
</section>
<section data-observation-step="2" class="observation-card">
 <div class="observation-heading"><span>2</span><div><h2>Periksa kondisi tanaman</h2><p>Kondisi daun menjadi fakta Rule Based. Foto hanya menjadi dokumentasi pendukung.</p></div></div>
 <div class="grid gap-5 lg:grid-cols-2">
  <div>
   <label for="warna-daun" class="field-label">Hasil pemeriksaan daun <b>*</b></label>
   <select id="warna-daun" name="warna_daun" required class="{{ $control }}"><option value="">Pilih kondisi yang paling sesuai</option>@foreach($leafConditions as $condition)<option value="{{ $condition }}" {{ $selectedLeaf===$condition?'selected':'' }}>{{ $labels[$condition]??$condition }}</option>@endforeach @foreach($specialLeaves as $value=>$label)<option value="{{ $value }}" {{ $selectedLeaf===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select>
   @error('warna_daun')<p class="field-error">{{ $message }}</p>@enderror
   <p id="leaf-help" class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600 dark:bg-slate-900 dark:text-slate-300">Pilih gejala yang benar-benar terlihat. Jika tidak sesuai dengan empat gejala yang tersedia, pilih pemeriksaan lanjutan.</p>
  </div>
  <div>
   <div class="flex items-center justify-between gap-3"><label for="foto-observasi" class="field-label mb-0">Foto pendukung <em>(opsional)</em></label><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300">Tidak memengaruhi hasil</span></div>
   <label for="foto-observasi" class="mt-2 flex min-h-12 cursor-pointer items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:bg-emerald-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-emerald-600"><span id="photo-label">{{ $existingPhotoUrl?'Ganti foto':'Ambil atau pilih foto' }}</span><input id="foto-observasi" type="file" name="foto_observasi" accept="image/jpeg,image/png,image/webp" capture="environment" class="sr-only"></label>
   <p class="field-help">Format JPG, PNG, atau WebP. Ukuran maksimal 4 MB.</p>@error('foto_observasi')<p class="field-error">{{ $message }}</p>@enderror
   <input id="hapus-foto" type="hidden" name="hapus_foto" value="{{ $photoMarkedForRemoval?'1':'0' }}">
   <div id="photo-preview-box" class="{{ $existingPhotoUrl&&!$photoMarkedForRemoval?'':'hidden' }} mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
    <a id="photo-preview-link" href="{{ $existingPhotoUrl??'#' }}" target="_blank" rel="noopener" class="block bg-slate-100 dark:bg-slate-950" aria-label="Buka foto ukuran penuh"><img id="photo-preview" src="{{ $existingPhotoUrl??'' }}" alt="Pratinjau foto observasi" class="h-44 w-full object-contain sm:h-52"></a>
    <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p id="photo-status" class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $existingPhotoUrl?'Foto tersimpan':'Foto dipilih' }}</p><p class="text-[11px] text-slate-500">Klik gambar untuk melihat ukuran penuh.</p></div><button id="remove-photo" type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950/40">Hapus foto</button></div>
   </div>
   <div id="photo-removal-note" class="{{ $photoMarkedForRemoval?'':'hidden' }} mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/30"><p class="text-xs text-amber-800 dark:text-amber-200">Foto akan dihapus ketika perubahan disimpan.</p><button id="undo-photo-removal" type="button" class="mt-2 min-h-9 rounded-lg border border-amber-300 bg-white px-3 text-xs font-semibold text-amber-800 dark:border-amber-700 dark:bg-slate-900 dark:text-amber-200">Batalkan hapus</button></div>
  </div>
 </div>
</section>
<section data-observation-step="3" class="observation-card">
 <div class="observation-heading"><span>3</span><div><h2>Periksa kesiapan pemupukan</h2><p>Lengkapi data hujan dan kondisi lahan untuk menentukan apakah pemupukan dapat dilakukan sekarang.</p></div></div>
 <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
  <p class="text-sm font-bold text-blue-900 dark:text-blue-100">Yang diperiksa pada tahap ini</p>
  <div class="mt-3 grid gap-3 text-xs text-blue-800 sm:grid-cols-3 dark:text-blue-200">
   <p><span class="mr-1 font-bold">1.</span> Curah hujan dan musim saat observasi.</p>
   <p><span class="mr-1 font-bold">2.</span> Kelembapan tanah dan kelancaran drainase.</p>
   <p><span class="mr-1 font-bold">3.</span> Hambatan lapangan seperti hama atau gulma.</p>
  </div>
 </div>
 <fieldset class="mt-5 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
  <legend class="px-2 text-sm font-bold text-slate-800 dark:text-slate-100">1. Data hujan dan musim</legend>
  <p class="field-help">Gunakan data angka jika tersedia. Musim dicatat bersama data hujan sebagai gambaran kondisi saat observasi.</p>
  <input type="hidden" name="mode_data_hujan_dikonfirmasi" value="1">
  <div class="mt-4 grid gap-2 sm:grid-cols-3">
   @foreach(['data_angka'=>['Ada data angka','Isi mm/bulan dan sumber'],'perkiraan'=>['Perkiraan kondisi hujan','Pilih keadaan umum di lapangan'],'tidak_tersedia'=>['Belum ada data','Kesiapan belum dapat dipastikan']] as $value=>$text)<label class="rain-mode flex min-h-20 cursor-pointer gap-3 rounded-xl border p-3 {{ $rainMode===$value?'border-emerald-500 bg-emerald-50':'border-slate-200' }} dark:border-slate-700"><input type="radio" name="metode_data_hujan" value="{{ $value }}" {{ $rainMode===$value?'checked':'' }} class="mt-1"><span><strong class="block text-sm text-slate-800 dark:text-white">{{ $text[0] }}</strong><small class="text-slate-500">{{ $text[1] }}</small></span></label>@endforeach
  </div>
  <div id="rain-number" class="rain-panel {{ $rainMode==='data_angka'?'':'hidden' }} border-sky-200 bg-sky-50 dark:border-sky-800 dark:bg-sky-950/30">
   <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><strong class="text-sm text-slate-800 dark:text-white">Data hujan dalam angka</strong><p class="field-help">Data 100&ndash;250 mm/bulan mendukung penjadwalan jika syarat lain terpenuhi.</p></div><button type="button" id="fetch-weather" class="min-h-11 rounded-xl border border-sky-300 bg-white px-4 text-sm font-semibold text-sky-700 disabled:opacity-50">Ambil data cuaca 30 hari</button></div>
   <p id="weather-message" class="mb-3 hidden rounded-lg px-3 py-2 text-xs"></p>
   <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div><label for="rain-mm" class="field-label">Jumlah hujan (mm/bulan)</label><input id="rain-mm" type="number" name="curah_hujan_mm_bulanan" value="{{ $get('curah_hujan_mm_bulanan') }}" min="0" max="1000" step="0.1" class="{{ $control }}">@error('curah_hujan_mm_bulanan')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div><label for="rain-period" class="field-label">Periode data</label><input id="rain-period" type="text" name="periode_curah_hujan" value="{{ $get('periode_curah_hujan') }}" maxlength="50" placeholder="Contoh: 30 hari terakhir" class="{{ $control }}">@error('periode_curah_hujan')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div><label for="rain-source" class="field-label">Sumber data</label><select id="rain-source" name="sumber_curah_hujan" class="{{ $control }}"><option value="">Pilih sumber</option>@foreach(['alat_ukur'=>'Alat ukur di kebun','open-meteo'=>'Open-Meteo (perkiraan lokasi)','manual'=>'Catatan kelompok tani','lainnya'=>'Sumber lainnya'] as $value=>$label)<option value="{{ $value }}" {{ (string)$get('sumber_curah_hujan')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select>@error('sumber_curah_hujan')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div data-season-target></div>
   </div>
  </div>
  <div id="rain-estimate" class="rain-panel {{ $rainMode==='perkiraan'?'':'hidden' }} border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30">
   <div class="grid gap-3 sm:grid-cols-2">
    <div><label for="rain-category" class="field-label">Perkiraan kondisi hujan</label><select id="rain-category" name="curah_hujan_kategori" class="{{ $control }}"><option value="">Pilih keadaan umum</option>@foreach(['Sangat Rendah'=>'Hampir tidak ada hujan','Rendah'=>'Hujan jarang','Normal'=>'Hujan cukup','Tinggi'=>'Hujan sering','Sangat Tinggi'=>'Hujan sangat sering atau lebat'] as $value=>$label)<option value="{{ $value }}" {{ (string)$get('curah_hujan_kategori')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select>@error('curah_hujan_kategori')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div data-season-target></div>
   </div>
   <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">Perkiraan tanpa angka membantu pencatatan, tetapi belum cukup untuk langsung menyatakan siap dipupuk.</p>
  </div>
  <div id="rain-none" class="rain-panel {{ $rainMode==='tidak_tersedia'?'':'hidden' }} border-slate-200 bg-slate-50 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
   <p>Observasi tetap dapat disimpan, tetapi waktu pemupukan belum dapat dinyatakan siap.</p>
   <div data-season-target class="mt-3 max-w-md"></div>
  </div>
  <div id="season-field" class="hidden"><label for="season" class="field-label">Musim saat observasi <em>(opsional)</em></label><select id="season" name="musim_saat_ini" class="{{ $control }}"><option value="">Belum dicatat</option>@foreach(['Musim Hujan','Musim Kemarau','Peralihan'] as $option)<option value="{{ $option }}" {{ (string)$get('musim_saat_ini')===$option?'selected':'' }}>{{ $option }}</option>@endforeach</select><p class="field-help">Dicatat sebagai konteks kondisi cuaca saat pengamatan.</p></div>
 </fieldset>
 <fieldset class="mt-5 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
  <legend class="px-2 text-sm font-bold text-slate-800 dark:text-slate-100">2. Kondisi lahan saat diperiksa</legend>
  <div class="grid gap-4 sm:grid-cols-2">
   <div><label for="kelembaban-tanah" class="field-label">Kelembapan tanah</label><select id="kelembaban-tanah" name="kelembaban_tanah" class="{{ $control }}"><option value="">Belum dapat dipastikan</option>@foreach(['Sangat Kering'=>'Kering sekali','Kering'=>'Kering','Normal'=>'Cukup lembap','Lembab'=>'Basah','Sangat Lembab'=>'Sangat basah'] as $value=>$label)<option value="{{ $value }}" {{ (string)$get('kelembaban_tanah')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select><p class="field-help">Kondisi terlalu kering atau terlalu basah dapat menunda pemupukan.</p></div>
   <div><label for="kondisi-drainase" class="field-label">Aliran air atau drainase</label><select id="kondisi-drainase" name="kondisi_drainase" class="{{ $control }}"><option value="">Belum diperiksa</option>@foreach(['Baik'=>'Baik — air mengalir lancar','Cukup'=>'Kurang lancar — tidak tergenang','Buruk — Tergenang'=>'Buruk — terdapat genangan'] as $value=>$label)<option value="{{ $value }}" {{ (string)$get('kondisi_drainase')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select><p class="field-help">Genangan menandakan pemupukan perlu ditunda.</p></div>
  </div>
 </fieldset>
 <div class="mt-5"><p class="field-label">3. Hambatan di area pemupukan</p><div class="mt-2 grid gap-3 sm:grid-cols-2"><label class="check-card"><input type="checkbox" name="ada_serangan_hama" value="1" {{ old('ada_serangan_hama',$isEdit?$kondisiLahan->ada_serangan_hama:false)?'checked':'' }}><span><strong>Ada hama atau penyakit</strong><small>Perlu ditangani sebelum pemupukan.</small></span></label><label class="check-card"><input type="checkbox" name="ada_gulma_dominan" value="1" {{ old('ada_gulma_dominan',$isEdit?$kondisiLahan->ada_gulma_dominan:false)?'checked':'' }}><span><strong>Gulma menutupi area aplikasi</strong><small>Bersihkan area terlebih dahulu.</small></span></label></div></div>
 <div class="mt-5"><label for="catatan" class="field-label">Catatan lapangan <em>(opsional)</em></label><textarea id="catatan" name="catatan_observasi" rows="3" maxlength="1000" class="{{ $control }}" placeholder="Jelaskan gejala lain atau kondisi khusus...">{{ $get('catatan_observasi') }}</textarea>@error('catatan_observasi')<p class="field-error">{{ $message }}</p>@enderror</div>
 <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/30"><strong class="text-sm text-emerald-900 dark:text-emerald-100">Hasil setelah disimpan</strong><div class="mt-3 grid gap-2 text-xs sm:grid-cols-3"><p><b>Kebutuhan pupuk</b><br>Dari luas, SPH, umur, dan fase.</p><p><b>Hasil Rule Based</b><br>Dari kondisi daun.</p><p><b>Kesiapan pemupukan</b><br>Dari curah hujan, interval, dan kondisi lahan.</p></div></div>
</section>
@include('components.observation-step-navigation')
<div data-observation-step="3" class="flex flex-col-reverse gap-2 pb-2 sm:flex-row sm:justify-end"><a href="{{ route('kondisi-lahan.index') }}" class="secondary-button">Batal</a><button type="submit" class="primary-button">{{ $isEdit?'Simpan dan Hitung Ulang':'Simpan dan Lihat Hasil Analisis' }}</button></div>
@push('scripts')
<script>
(function(){
 const blocks=@json($bloksJson),descriptions=@json($descriptions),isEdit=@json($isEdit),existingPhotoUrl=@json($existingPhotoUrl);
 const ownerSelect=document.getElementById('anggota-select'),blockSelect=document.getElementById('blok-lahan-select');
 const blockOptions=blockSelect?Array.from(blockSelect.options).slice(1):[],blockHelp=document.getElementById('block-choice-help');
 const lastDate=document.getElementById('tanggal-pemupukan-terakhir'),weatherButton=document.getElementById('fetch-weather');
 let lastDateTouched=Boolean(lastDate&&lastDate.value);
 const currentBlock=()=>blocks.find(b=>String(b.id)===String(blockSelect?.value));
 function fact(label,value){return '<div class="rounded-lg bg-white/70 p-2.5 dark:bg-slate-900/40"><span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-400">'+label+'</span><b class="mt-0.5 block text-xs text-slate-800 dark:text-white">'+(value||'-')+'</b></div>'}
 function updateBlock(fillHistory){
  const block=currentBlock(),card=document.getElementById('selected-block-card'),banner=document.getElementById('banner-tbm');
  card?.classList.toggle('hidden',!block);
  if(!block){banner?.classList.add('hidden');if(weatherButton)weatherButton.disabled=true;return}
  const estimatedTrees=Math.round(Number(block.luas_ha||0)*Number(block.sph||0));
  document.getElementById('block-name').textContent=block.nama_blok;
  document.getElementById('block-owner').textContent='Pemilik: '+block.anggota_nama;
  document.getElementById('block-facts').innerHTML=fact('Luas',Number(block.luas_ha).toLocaleString('id-ID',{minimumFractionDigits:2})+' Ha')+fact('Pohon/Ha',Number(block.sph||0).toLocaleString('id-ID'))+fact('Perkiraan pohon',estimatedTrees.toLocaleString('id-ID'))+fact('Tahun tanam',block.tahun_tanam)+fact('Umur',block.umur!==null?block.umur+' tahun':'-')+fact('Fase',block.fase||block.kategori);
  const immature=block.kategori==='Belum Menghasilkan'||block.fase==='Tanaman Belum Menghasilkan';banner?.classList.toggle('hidden',!immature);
  if(weatherButton)weatherButton.disabled=!(block.centroid_lat&&block.centroid_lng);
  if(lastDate){
   const hasHistory=!!block.tanggal_pemupukan_terakhir;
   if(hasHistory){
    lastDate.value=block.tanggal_pemupukan_terakhir;
    lastDate.readOnly=true;
    lastDate.classList.add('bg-slate-100','text-slate-500','cursor-not-allowed','opacity-75');
    const help=document.getElementById('last-date-help');
    if(help) help.innerHTML='<span class="text-amber-700 dark:text-amber-400 font-semibold">Dikunci:</span> Data diambil langsung dari riwayat Pelaksanaan Pemupukan resmi di sistem.';
   }else{
    lastDate.readOnly=false;
    lastDate.classList.remove('bg-slate-100','text-slate-500','cursor-not-allowed','opacity-75');
    if(fillHistory&&!isEdit&&!lastDateTouched) lastDate.value='';
    const help=document.getElementById('last-date-help');
    if(help) help.textContent='Belum ada riwayat pelaksanaan resmi. Isi jika diketahui atau biarkan kosong.';
   }
  }
 }
 function filterBlocks(autoSelectSingle){
  if(!ownerSelect||!blockSelect)return;const ownerId=ownerSelect.value,available=blockOptions.filter(o=>String(o.dataset.ownerId)===String(ownerId));
  blockOptions.forEach(o=>{const visible=ownerId!==''&&String(o.dataset.ownerId)===String(ownerId);o.hidden=!visible;o.disabled=!visible});
  const selected=blockOptions.find(o=>o.value===blockSelect.value);if(!selected||selected.disabled)blockSelect.value='';
  blockSelect.disabled=ownerId==='';blockSelect.options[0].textContent=ownerId===''?'Pilih anggota terlebih dahulu':(available.length?'Pilih blok lahan':'Anggota belum memiliki blok');
  if(ownerId!==''&&available.length===1&&(autoSelectSingle||!blockSelect.value)){blockSelect.value=available[0].value;if(blockHelp)blockHelp.textContent='Satu blok ditemukan dan dipilih otomatis.'}else if(blockHelp){blockHelp.textContent=available.length>1?available.length+' blok tersedia. Pilih blok yang akan diperiksa.':'Daftar blok akan mengikuti anggota yang dipilih.'}
  updateBlock(true);
 }
 ownerSelect?.addEventListener('change',()=>filterBlocks(true));blockSelect?.addEventListener('change',()=>updateBlock(true));lastDate?.addEventListener('input',()=>lastDateTouched=true);filterBlocks(false);
 const leaf=document.getElementById('warna-daun'),leafHelp=document.getElementById('leaf-help');
 function updateLeaf(){if(!leafHelp||!leaf)return;leafHelp.textContent=leaf.value==='__gejala_lain'?'Jelaskan gejala pada Catatan lapangan; sistem tidak memaksanya cocok dengan rule.':leaf.value==='__tidak_pasti'?'Sistem tidak menyatakan kondisi normal dan akan meminta pemeriksaan lanjutan.':(descriptions[leaf.value]||(leaf.value?'Fakta ini akan diperiksa terhadap rule yang aktif.':'Pilih kondisi yang benar-benar terlihat.'))}
 leaf?.addEventListener('change',updateLeaf);updateLeaf();
 const photoInput=document.getElementById('foto-observasi'),photoLabel=document.getElementById('photo-label'),photoBox=document.getElementById('photo-preview-box'),photoPreview=document.getElementById('photo-preview'),photoLink=document.getElementById('photo-preview-link'),photoStatus=document.getElementById('photo-status'),removePhotoButton=document.getElementById('remove-photo'),undoPhotoButton=document.getElementById('undo-photo-removal'),removePhotoInput=document.getElementById('hapus-foto'),removalNote=document.getElementById('photo-removal-note');
 let previewObjectUrl=null;
 function showPhoto(url,status){if(!photoBox||!photoPreview||!photoLink)return;photoPreview.src=url;photoLink.href=url;photoStatus.textContent=status;photoBox.classList.remove('hidden');removalNote?.classList.add('hidden')}
 function clearSelectedPhoto(){if(previewObjectUrl){URL.revokeObjectURL(previewObjectUrl);previewObjectUrl=null}if(photoInput)photoInput.value=''}
 photoInput?.addEventListener('change',function(){const file=this.files?.[0];if(!file)return;if(file.size>4*1024*1024){this.value='';if(typeof showToast==='function')showToast('warning','Ukuran foto maksimal 4 MB.',4000);return}if(!['image/jpeg','image/png','image/webp'].includes(file.type)){this.value='';if(typeof showToast==='function')showToast('warning','Gunakan foto JPG, PNG, atau WebP.',4000);return}if(previewObjectUrl)URL.revokeObjectURL(previewObjectUrl);previewObjectUrl=URL.createObjectURL(file);removePhotoInput.value='0';photoLabel.textContent='Ganti foto';showPhoto(previewObjectUrl,file.name+' · '+(file.size/1024/1024).toFixed(1)+' MB')});
 removePhotoButton?.addEventListener('click',function(){clearSelectedPhoto();photoBox?.classList.add('hidden');photoLabel.textContent=existingPhotoUrl?'Pilih foto pengganti':'Ambil atau pilih foto';if(existingPhotoUrl){removePhotoInput.value='1';removalNote?.classList.remove('hidden')}else removePhotoInput.value='0'});
 undoPhotoButton?.addEventListener('click',function(){removePhotoInput.value='0';removalNote?.classList.add('hidden');photoLabel.textContent='Ganti foto';if(existingPhotoUrl)showPhoto(existingPhotoUrl,'Foto tersimpan')});
 const modes=Array.from(document.querySelectorAll('input[name="metode_data_hujan"]')),seasonField=document.getElementById('season-field');
 function updateRain(){const mode=modes.find(r=>r.checked)?.value||'data_angka',panels={data_angka:document.getElementById('rain-number'),perkiraan:document.getElementById('rain-estimate'),tidak_tersedia:document.getElementById('rain-none')};Object.entries(panels).forEach(([key,panel])=>panel?.classList.toggle('hidden',key!==mode));const seasonTarget=panels[mode]?.querySelector('[data-season-target]');if(seasonTarget&&seasonField){seasonTarget.appendChild(seasonField);seasonField.classList.remove('hidden')}document.querySelectorAll('.rain-mode').forEach(card=>{const active=card.querySelector('input').checked;card.classList.toggle('border-emerald-500',active);card.classList.toggle('bg-emerald-50',active);card.classList.toggle('border-slate-200',!active)})}
 modes.forEach(r=>r.addEventListener('change',updateRain));updateRain();
 function weatherMessage(ok,text){const el=document.getElementById('weather-message');el.className='mb-3 rounded-lg px-3 py-2 text-xs '+(ok?'bg-emerald-100 text-emerald-800':'bg-red-100 text-red-700');el.textContent=text}
 weatherButton?.addEventListener('click',function(){const block=currentBlock();if(!block?.centroid_lat||!block?.centroid_lng){weatherMessage(false,'Koordinat blok belum tersedia. Gunakan data lapangan.');return}this.disabled=true;this.textContent='Mengambil data...';fetch(@json(route('api.cuaca.fetch')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({lat:block.centroid_lat,lng:block.centroid_lng})}).then(r=>r.json()).then(data=>{if(!data.success)throw new Error(data.message||'Data tidak tersedia');document.getElementById('rain-mm').value=Number(data.detail.total_curah_hujan_mm).toFixed(1);document.getElementById('rain-category').value=data.curah_hujan_kategori||'';document.getElementById('rain-source').value='open-meteo';document.getElementById('season').value=data.musim_saat_ini||'';document.getElementById('rain-period').value='30 hari sampai '+new Date().toLocaleDateString('id-ID');weatherMessage(true,'Data hujan dan musim diisi dari Open-Meteo sebagai perkiraan berbasis lokasi blok.')}).catch(e=>weatherMessage(false,e.message||'Gagal mengambil data cuaca.')).finally(()=>{this.disabled=false;this.textContent='Ambil data cuaca 30 hari'})});
})();
</script>
@endpush