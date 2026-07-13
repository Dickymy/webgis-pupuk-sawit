{{--
    Custom Select Component — Fully custom, mobile-safe, no native browser dropdown
    Usage: @include('components.custom-select', [
        'name'     => 'jenis_tanah',
        'label'    => 'Jenis Tanah',
        'options'  => ['Tanah Lempung', 'Tanah Berpasir'],
        'selected' => old('jenis_tanah', $value ?? ''),
        'placeholder' => '— Pilih —',
        'required' => true,
        'error'    => $errors->first('jenis_tanah'),
        'id'       => 'jenis_tanah',   // optional, auto-generated if not set
        'helpText' => 'Teks bantuan',  // optional
    ])
--}}

@php
    $cid       = $id ?? ('cs-' . $name . '-' . uniqid());
    $selLabel  = '';
    $selValue  = $selected ?? '';
    $opts      = $options ?? [];
    $ph        = $placeholder ?? '— Pilih —';

    // Cari label dari value yang sudah dipilih
    foreach ($opts as $opt) {
        if (is_array($opt)) {
            if (($opt['value'] ?? '') == $selValue) { $selLabel = $opt['label']; break; }
        } else {
            if ($opt == $selValue) { $selLabel = $opt; break; }
        }
    }
@endphp

<div>
    @if($label ?? false)
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5" for="{{ $cid }}-btn">
        {{ $label }} @if($required ?? false)<span class="text-red-400">*</span>@endif
    </label>
    @endif

    {{-- Hidden input untuk form submission --}}
    <input type="hidden" name="{{ $name }}" id="{{ $cid }}-val" value="{{ $selValue }}">

    {{-- Custom select wrapper (anchor point untuk posisi) --}}
    <div class="cs-wrapper" id="{{ $cid }}-wrapper" style="position:relative; min-width:0;">

        {{-- Trigger button --}}
        <button type="button" id="{{ $cid }}-btn"
            onclick="csToggle('{{ $cid }}')"
            style="width:100%; min-width:0; box-sizing:border-box; display:flex; align-items:center; justify-content:space-between; gap:8px; padding:10px 12px; border:1px solid {{ ($error ?? false) ? '#f87171' : '' }}; border-radius:12px; font-size:14px; cursor:pointer; text-align:left; transition:border-color 0.15s;"
            class="cs-btn-trigger">
            <span id="{{ $cid }}-display" style="flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:14px; {{ $selValue ? '' : 'opacity:0.6;' }}">
                {{ $selLabel ?: $ph }}
            </span>
            <svg id="{{ $cid }}-arrow" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                style="flex-shrink:0; max-width:none!important; color:#94a3b8; transition:transform 0.2s;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Dropdown panel — akan dipindahkan ke body via JS (portal pattern) --}}
        <div id="{{ $cid }}-panel"
            data-cid="{{ $cid }}"
            class="cs-panel"
            style="display:none; position:fixed; border:1px solid transparent; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.16); z-index:4999; overflow:hidden;">

            @if(count($opts) > 4)
            {{-- Panah atas — muncul saat bisa scroll ke atas --}}
            <div class="cs-arrow-up" style="display:none; align-items:center; justify-content:center; gap:4px; padding:5px 12px; border-bottom:1px solid transparent; cursor:pointer; user-select:none; transition:opacity 0.15s;"
                onclick="csScrollBy('{{ $cid }}', -80)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="max-width:none!important; flex-shrink:0; color:#64748b;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                </svg>
                <span style="font-size:10px; color:#64748b; font-weight:600;">Scroll ke atas</span>
            </div>
            @endif

            {{-- Scrollable options container --}}
            <div class="cs-options-scroll" style="max-height:220px; overflow-y:auto; overscroll-behavior:contain;">
                @foreach($opts as $opt)
                @php
                    $optVal   = is_array($opt) ? ($opt['value'] ?? $opt['label']) : $opt;
                    $optLabel = is_array($opt) ? ($opt['label'] ?? $opt['value']) : $opt;
                    $isActive = ($optVal == $selValue);
                @endphp
                <div class="cs-option"
                     data-value="{{ $optVal }}"
                     data-label="{{ $optLabel }}"
                     data-cid="{{ $cid }}"
                     onclick="csSelect('{{ $cid }}', this)"
                     style="padding:11px 14px; cursor:pointer; font-size:13px; border-bottom:1px solid transparent; word-break:break-word; line-height:1.4; display:flex; align-items:center; justify-content:space-between; gap:8px; font-weight:{{ $isActive ? '600' : '400' }};">
                    <span style="flex:1; min-width:0;">{{ $optLabel }}</span>
                    @if($isActive)
                    <svg class="cs-check" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0; max-width:none!important; color:#10b981;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                    @endif
                </div>
                @endforeach
            </div>

            @if(count($opts) > 4)
            {{-- Panah bawah — muncul saat masih ada konten di bawah --}}
            <div class="cs-arrow-down" style="display:flex; align-items:center; justify-content:center; gap:4px; padding:5px 12px; border-top:1px solid transparent; cursor:pointer; user-select:none; transition:opacity 0.15s;"
                onclick="csScrollBy('{{ $cid }}', 80)">
                <span style="font-size:10px; color:#64748b; font-weight:600;">Scroll ke bawah</span>
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="max-width:none!important; flex-shrink:0; color:#64748b;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            @endif
        </div>
    </div>

    @if($error ?? false)
        <p style="margin-top:6px; font-size:11px; color:#ef4444;">{{ $error }}</p>
    @endif
    @if($helpText ?? false)
        <p style="margin-top:6px; font-size:11px; color:#94a3b8;">{!! $helpText !!}</p>
    @endif
</div>

@pushOnce('scripts')
<script>
(function() {
    // ID panel yang sedang terbuka
    var _openCid = null;
    var _scrollRAF = null;

    // ── Hitung dan terapkan posisi panel ke viewport ──────────────
    function csPositionPanel(cid) {
        var btn   = document.getElementById(cid + '-btn');
        var panel = document.getElementById(cid + '-panel');
        if (!btn || !panel) return;

        var rect        = btn.getBoundingClientRect();
        var panelH      = panel.offsetHeight;
        var viewH       = window.innerHeight;
        var viewW       = window.innerWidth;
        var isMobile    = viewW < 640;

        // Tentukan lebar & posisi horizontal
        if (isMobile) {
            panel.style.left  = '8px';
            panel.style.right = '8px';
            panel.style.width = 'auto';
        } else {
            panel.style.left  = rect.left + 'px';
            panel.style.width = rect.width + 'px';
            panel.style.right = 'auto';
        }

        // Tentukan max-height berdasarkan ruang yang tersedia
        var spaceBelow = viewH - rect.bottom - 10;
        var spaceAbove = rect.top - 10;
        var maxH       = Math.min(220, Math.max(spaceBelow, spaceAbove));

        var scrollEl = panel.querySelector('.cs-options-scroll');
        if (scrollEl) scrollEl.style.maxHeight = maxH + 'px';

        // Tampilkan ke bawah atau ke atas
        if (spaceBelow >= Math.min(160, panelH) || spaceBelow >= spaceAbove) {
            panel.style.top    = (rect.bottom + 4) + 'px';
            panel.style.bottom = 'auto';
        } else {
            panel.style.bottom = (viewH - rect.top + 4) + 'px';
            panel.style.top    = 'auto';
        }
    }

    // ── Toggle open/close ─────────────────────────────────────────
    window.csToggle = function(cid) {
        var panel = document.getElementById(cid + '-panel');
        if (!panel) return;

        if (_openCid === cid) {
            csClose(cid);
        } else {
            if (_openCid) csClose(_openCid);
            csOpen(cid);
        }
    };

    window.csOpen = function(cid) {
        var panel = document.getElementById(cid + '-panel');
        var arrow = document.getElementById(cid + '-arrow');
        var btn   = document.getElementById(cid + '-btn');
        if (!panel) return;

        // Portal: pindahkan panel ke body agar tidak terpengaruh scroll parent
        if (panel.parentElement !== document.body) {
            document.body.appendChild(panel);
        }

        panel.style.display = 'block';
        _openCid = cid;

        // Hitung posisi awal
        csPositionPanel(cid);

        if (arrow) arrow.style.transform = 'rotate(180deg)';
        if (btn)   btn.style.borderColor = '#10b981';

        // Scroll ke item aktif
        var valEl   = document.getElementById(cid + '-val');
        var scrollEl = panel.querySelector('.cs-options-scroll');
        if (valEl && scrollEl) {
            var active = scrollEl.querySelector('.cs-option[data-value="' + valEl.value + '"]');
            if (active) {
                setTimeout(function() { active.scrollIntoView({ block: 'nearest' }); }, 50);
            }
        }

        // Update panah scroll
        updateScrollArrows(cid);
    };

    window.csClose = function(cid) {
        var panel = document.getElementById(cid + '-panel');
        var arrow = document.getElementById(cid + '-arrow');
        var btn   = document.getElementById(cid + '-btn');
        if (!panel) return;
        panel.style.display = 'none';
        if (arrow) arrow.style.transform = '';
        if (btn)   btn.style.borderColor = '';
        if (_openCid === cid) _openCid = null;
    };

    window.csSelect = function(cid, el) {
        var val   = el.dataset.value;
        var label = el.dataset.label;

        var valEl     = document.getElementById(cid + '-val');
        var displayEl = document.getElementById(cid + '-display');
        var panel     = document.getElementById(cid + '-panel');

        if (valEl)     valEl.value = val;
        if (displayEl) {
            displayEl.textContent = label;
            displayEl.style.color = '';
            displayEl.style.opacity = '1';
        }

        // Update aktif state semua option
        var dk = document.documentElement.classList.contains('dark');
        if (panel) {
            panel.querySelectorAll('.cs-option').forEach(function(opt) {
                var isMe = opt.dataset.value === val;
                opt.style.background  = isMe ? (dk ? '#064e3b' : '#f0fdf4') : (dk ? '#1e293b' : '#fff');
                opt.style.color       = isMe ? (dk ? '#6ee7b7' : '#065f46') : (dk ? '#e2e8f0' : '#374151');
                opt.style.fontWeight  = isMe ? '600' : '400';

                var existing = opt.querySelector('.cs-check');
                if (isMe && !existing) {
                    var chk = document.createElement('svg');
                    chk.className = 'cs-check';
                    chk.setAttribute('width', '14'); chk.setAttribute('height', '14');
                    chk.setAttribute('fill', 'none'); chk.setAttribute('stroke', 'currentColor');
                    chk.setAttribute('viewBox', '0 0 24 24');
                    chk.style.cssText = 'flex-shrink:0; max-width:none!important; color:#10b981;';
                    chk.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>';
                    opt.appendChild(chk);
                } else if (!isMe && existing) {
                    existing.remove();
                }
            });
        }

        if (valEl) {
            valEl.dispatchEvent(new Event('change', { bubbles: true }));
            valEl.dispatchEvent(new Event('input',  { bubbles: true }));
        }

        csClose(cid);
    };

    // ── Update panah atas/bawah sesuai posisi scroll ─────────────
    function updateScrollArrows(cid) {
        var panel = document.getElementById(cid + '-panel');
        if (!panel) return;
        var scrollEl  = panel.querySelector('.cs-options-scroll');
        var arrowUp   = panel.querySelector('.cs-arrow-up');
        var arrowDown = panel.querySelector('.cs-arrow-down');
        if (!scrollEl) return;

        var atTop    = scrollEl.scrollTop <= 2;
        var atBottom = scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - 2;

        if (arrowUp)   arrowUp.style.display  = atTop    ? 'none' : 'flex';
        if (arrowDown) arrowDown.style.display = atBottom ? 'none' : 'flex';
    }

    // ── Scroll konten panel sejumlah px ──────────────────────────
    window.csScrollBy = function(cid, amount) {
        var panel = document.getElementById(cid + '-panel');
        if (!panel) return;
        var scrollEl = panel.querySelector('.cs-options-scroll');
        if (!scrollEl) return;
        scrollEl.scrollBy({ top: amount, behavior: 'smooth' });
    };

    // ── Reposisi saat scroll/resize (pakai rAF agar smooth) ──────
    function onScrollResize() {
        if (!_openCid) return;
        if (_scrollRAF) cancelAnimationFrame(_scrollRAF);
        _scrollRAF = requestAnimationFrame(function() {
            if (_openCid) csPositionPanel(_openCid);
        });
    }

    window.addEventListener('scroll',  onScrollResize, { passive: true, capture: true });
    window.addEventListener('resize',  onScrollResize, { passive: true });

    // ── Tutup panel saat klik di luar ────────────────────────────
    function handleCsOutsideClick(e) {
        if (!_openCid) return;
        var btn   = document.getElementById(_openCid + '-btn');
        var panel = document.getElementById(_openCid + '-panel');
        if (btn   && btn.contains(e.target))   return;
        if (panel && panel.contains(e.target)) return;
        csClose(_openCid);
    }
    document.addEventListener('click', handleCsOutsideClick, true);
    document.addEventListener('touchstart', handleCsOutsideClick, { passive: true, capture: true });

    // ── Hover style untuk option ─────────────────────────────────
    document.addEventListener('mouseover', function(e) {
        var opt = e.target.closest('.cs-option');
        if (!opt) return;
        var dk = document.documentElement.classList.contains('dark');
        var activeBg = dk ? 'rgb(6, 78, 59)' : 'rgb(240, 253, 244)';
        if (opt.style.background !== activeBg) {
            opt.style.background = dk ? '#334155' : '#f8fafc';
        }
    });
    document.addEventListener('mouseout', function(e) {
        var opt = e.target.closest('.cs-option');
        if (!opt) return;
        var dk = document.documentElement.classList.contains('dark');
        var hoverBg = dk ? 'rgb(51, 65, 85)' : 'rgb(248, 250, 252)';
        if (opt.style.background === hoverBg) {
            opt.style.background = dk ? '#1e293b' : '#fff';
        }
    });

    // ── Scroll-arrow update saat user scroll di dalam panel ──────
    document.addEventListener('scroll', function(e) {
        var scrollEl = e.target;
        if (scrollEl && scrollEl.classList && scrollEl.classList.contains('cs-options-scroll')) {
            var panel = scrollEl.closest('.cs-panel');
            if (panel) updateScrollArrows(panel.dataset.cid);
        }
    }, true);
})();
</script>
@endPushOnce
