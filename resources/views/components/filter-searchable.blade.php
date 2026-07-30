{{--
    Searchable Filter Dropdown (for filter forms)
    Usage: @include('components.filter-searchable', [
        'name' => 'anggota_id',
        'placeholder' => 'Cari anggota...',
        'options' => $anggotas,
        'displayField' => 'nama',
        'selected' => request('anggota_id'),
        'formId' => 'filter-form', // form to submit on selection
    ])
--}}
@php
    $fid = 'fs-' . $name . '-' . uniqid();
    $allLabel = $allLabel ?? 'Semua pemilik';
@endphp

<div class="relative" id="{{ $fid }}-wrap">
    <input type="hidden" name="{{ $name }}" id="{{ $fid }}-val" value="{{ $selected ?? '' }}" form="{{ $formId ?? '' }}">
    <input type="text" id="{{ $fid }}-input"
        autocomplete="off"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded="false"
        aria-controls="{{ $fid }}-drop"
        placeholder="{{ $placeholder ?? 'Cari...' }}"
        value="{{ $selected ? ($options->firstWhere('id', $selected)?->{$displayField} ?? '') : '' }}"
        class="min-h-10 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 cursor-text">
    <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>
    <div id="{{ $fid }}-drop" role="listbox" class="absolute left-0 right-0 top-full z-[100] mt-1 hidden max-h-44 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl dark:border-slate-600 dark:bg-slate-800">
        <div class="fs-opt px-3 py-2 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer text-xs text-slate-500 dark:text-slate-400 border-b border-slate-50 dark:border-slate-700" role="option" data-value="" data-label="">
            {{ $allLabel }}
        </div>
        @foreach($options as $opt)
        <div class="fs-opt px-3 py-2 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer text-xs text-slate-700 dark:text-slate-200 border-b border-slate-50 dark:border-slate-700 last:border-0" role="option" data-value="{{ $opt->id }}" data-label="{{ $opt->{$displayField} }}">
            {{ $opt->{$displayField} }}
        </div>
        @endforeach
        <div class="fs-empty px-3 py-2 text-xs text-slate-400 text-center hidden">Tidak ditemukan</div>
    </div>
</div>

<script>
(function(){
    var wrap = document.getElementById('{{ $fid }}-wrap');
    var input = document.getElementById('{{ $fid }}-input');
    var val = document.getElementById('{{ $fid }}-val');
    var drop = document.getElementById('{{ $fid }}-drop');
    var opts = drop.querySelectorAll('.fs-opt');
    var empty = drop.querySelector('.fs-empty');

    input.addEventListener('focus', function(){
        openDropdown();
        showAll();
        input.select();
    });
    input.addEventListener('input', function(){
        openDropdown();
        filter();
        filterPage();
    });
    input.addEventListener('keydown', function(e){
        if (e.key === 'Escape') {
            closeDropdown(true);
            input.blur();
        }
    });

    function openDropdown(){
        drop.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown(restoreSelection){
        drop.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');

        if (restoreSelection) {
            restoreSelectedValue();
            filterPage();
        }
    }

    function restoreSelectedValue(){
        var currentVal = val.value;
        var selectedOpt = Array.from(opts).find(function(o) {
            return o.dataset.value === currentVal;
        });
        input.value = selectedOpt ? selectedOpt.dataset.label : '';
    }

    function showAll(){
        opts.forEach(function(o){
            o.style.display = '';
        });
        empty.classList.add('hidden');
    }

    function filter(){
        var q = input.value.toLowerCase().trim();
        var matchedOptions = 0;
        opts.forEach(function(o){
            var isAllOption = o.dataset.value === '';
            var show = q === ''
                ? true
                : (! isAllOption && o.dataset.label.toLowerCase().includes(q));
            o.style.display = show ? '' : 'none';
            if (show && ! isAllOption) matchedOptions++;
        });
        empty.classList.toggle('hidden', ! (q !== '' && matchedOptions === 0));
    }

    function filterPage() {
        var q = input.value.toLowerCase().trim();
        var cards = document.querySelectorAll('.anggota-group-card');
        
        cards.forEach(function(card) {
            var name = card.dataset.namaAnggota || '';
            var matchesName = name.includes(q);
            
            var blockRows = card.querySelectorAll('.block-row');
            var matchedBlocksCount = 0;
            
            blockRows.forEach(function(row) {
                var blockName = row.dataset.namaBlok || '';
                var matchesBlock = blockName.includes(q);
                
                if (matchesName || matchesBlock) {
                    row.style.display = '';
                    matchedBlocksCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            if (q === '' || matchesName || matchedBlocksCount > 0) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    opts.forEach(function(o){
        o.addEventListener('click', function(){
            val.value = this.dataset.value;
            input.value = this.dataset.label;
            closeDropdown(false);
            // Submit form
            var form = document.getElementById('{{ $formId ?? "" }}');
            if(form) form.submit();
        });
    });

    document.addEventListener('click', function(e){ 
        if(!wrap.contains(e.target)) {
            closeDropdown(true);
        }
    });
})();
</script>
