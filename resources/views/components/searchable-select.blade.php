{{--
    Searchable Select Component — Mobile-safe, portal-based dropdown
    Usage: @include('components.searchable-select', [...])
--}}

@php
    $uid = 'ss-' . $name . '-' . uniqid();
@endphp

<div style="position:relative; min-width:0;" id="{{ $uid }}-wrapper">
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
        {{ $label }} @if($required ?? false)<span class="text-red-400">*</span>@endif
    </label>

    {{-- Hidden input yang dikirim ke server --}}
    <input type="hidden" name="{{ $name }}" id="{{ $uid }}-value" value="{{ $selected ?? '' }}">

    {{-- Display input (searchable) --}}
    <div style="position:relative; min-width:0;">
        <input type="text" id="{{ $uid }}-search"
            placeholder="{{ $placeholder ?? 'Cari...' }}"
            autocomplete="off"
            style="width:100%; box-sizing:border-box; padding:10px 36px 10px 14px; border:1px solid {{ ($error ?? false) ? '#f87171' : '' }}; border-radius:12px; font-size:14px; outline:none; min-width:0;"
            value="{{ $selected ? ($options->firstWhere('id', $selected)?->{$displayField} ?? '') : '' }}">
        <div style="position:absolute; right:10px; top:50%; transform:translateY(-50%); pointer-events:none; color:#94a3b8;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="max-width:none!important;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    {{-- Dropdown panel — akan dipindahkan ke body via JS (portal pattern) --}}
    <div id="{{ $uid }}-dropdown"
        data-uid="{{ $uid }}"
        class="ss-panel"
        style="display:none; position:fixed; border:1px solid transparent; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.16); z-index:4999; overflow:hidden;">

        {{-- Scroll indicator (tampil hanya jika opsi banyak) --}}
        @if($options->count() > 4)
        <div class="ss-arrow-up" style="display:none; align-items:center; justify-content:center; gap:4px; padding:5px 12px; border-bottom:1px solid transparent; cursor:pointer; user-select:none; transition:opacity 0.15s;"
            onclick="document.getElementById('{{ $uid }}-dropdown').querySelector('.ss-options-scroll').scrollBy({top:-80,behavior:'smooth'})">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="max-width:none!important; flex-shrink:0; color:#64748b;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
            </svg>
            <span style="font-size:10px; color:#64748b; font-weight:600;">Scroll ke atas</span>
        </div>
        @endif

        {{-- Scrollable options list --}}
        <div class="ss-options-scroll" style="max-height:220px; overflow-y:auto; overscroll-behavior:contain;">
            @foreach($options as $opt)
            <div class="ss-option"
                 data-value="{{ $opt->id }}" data-label="{{ $opt->{$displayField} }}"
                 style="padding:11px 14px; cursor:pointer; font-size:13px; border-bottom:1px solid transparent; word-break:break-word; line-height:1.4;">
                {{ $opt->{$displayField} }}
            </div>
            @endforeach
            <div class="ss-empty" style="display:none; padding:12px 14px; font-size:13px; text-align:center;">Tidak ditemukan</div>
        </div>

        {{-- Bottom scroll arrow --}}
        @if($options->count() > 4)
        <div class="ss-arrow-down" style="display:flex; align-items:center; justify-content:center; gap:4px; padding:5px 12px; border-top:1px solid transparent; cursor:pointer; user-select:none; transition:opacity 0.15s;"
            onclick="document.getElementById('{{ $uid }}-dropdown').querySelector('.ss-options-scroll').scrollBy({top:80,behavior:'smooth'})">
            <span style="font-size:10px; color:#64748b; font-weight:600;">Scroll ke bawah</span>
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="max-width:none!important; flex-shrink:0; color:#64748b;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        @endif
    </div>

    @if($error ?? false)
        <p style="margin-top:6px; font-size:12px; color:#ef4444;">{{ $error }}</p>
    @endif
    @if($helpText ?? false)
        <p style="margin-top:6px; font-size:12px; color:#94a3b8;">{!! $helpText !!}</p>
    @endif
</div>

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var _openUid   = null;
    var _scrollRAF = null;

    // ── Hitung posisi dropdown ke viewport ────────────────────────
    function ssPositionDropdown(uid) {
        var searchEl = document.getElementById(uid + '-search');
        var dropdown = document.getElementById(uid + '-dropdown');
        if (!searchEl || !dropdown) return;

        var rect    = searchEl.getBoundingClientRect();
        var viewH   = window.innerHeight;
        var viewW   = window.innerWidth;
        var isMobile = viewW < 640;

        // Horizontal
        if (isMobile) {
            dropdown.style.left  = '8px';
            dropdown.style.right = '8px';
            dropdown.style.width = 'auto';
        } else {
            dropdown.style.left  = rect.left + 'px';
            dropdown.style.width = rect.width + 'px';
            dropdown.style.right = 'auto';
        }

        // Max-height
        var spaceBelow = viewH - rect.bottom - 10;
        var spaceAbove = rect.top - 10;
        var maxH       = Math.min(220, Math.max(spaceBelow, spaceAbove));
        var scrollEl   = dropdown.querySelector('.ss-options-scroll');
        if (scrollEl) scrollEl.style.maxHeight = maxH + 'px';

        // Atas atau bawah
        if (spaceBelow >= Math.min(160, dropdown.offsetHeight) || spaceBelow >= spaceAbove) {
            dropdown.style.top    = (rect.bottom + 4) + 'px';
            dropdown.style.bottom = 'auto';
        } else {
            dropdown.style.bottom = (viewH - rect.top + 4) + 'px';
            dropdown.style.top    = 'auto';
        }
    }

    function updateScrollArrows(uid) {
        var dropdown = document.getElementById(uid + '-dropdown');
        if (!dropdown) return;
        var scrollEl  = dropdown.querySelector('.ss-options-scroll');
        var arrowUp   = dropdown.querySelector('.ss-arrow-up');
        var arrowDown = dropdown.querySelector('.ss-arrow-down');
        if (!scrollEl) return;

        var atTop    = scrollEl.scrollTop <= 2;
        var atBottom = scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - 2;

        if (arrowUp)   arrowUp.style.display  = atTop    ? 'none' : 'flex';
        if (arrowDown) arrowDown.style.display = atBottom ? 'none' : 'flex';
    }

    // ── Reposisi saat scroll/resize ───────────────────────────────
    function onScrollResize() {
        if (!_openUid) return;
        if (_scrollRAF) cancelAnimationFrame(_scrollRAF);
        _scrollRAF = requestAnimationFrame(function() {
            if (_openUid) ssPositionDropdown(_openUid);
        });
    }
    window.addEventListener('scroll', onScrollResize, { passive: true, capture: true });
    window.addEventListener('resize', onScrollResize, { passive: true });

    // ── Tutup saat klik di luar ───────────────────────────────────
    function handleOutsideClick(e) {
        if (!_openUid) return;
        var search   = document.getElementById(_openUid + '-search');
        var dropdown = document.getElementById(_openUid + '-dropdown');
        if (search   && search.contains(e.target))   return;
        if (dropdown && dropdown.contains(e.target)) return;
        if (dropdown && typeof dropdown._closeDropdown === 'function') {
            dropdown._closeDropdown(_openUid);
        }
    }
    document.addEventListener('click', handleOutsideClick, true);
    document.addEventListener('touchstart', handleOutsideClick, { passive: true, capture: true });

    // ── Scroll-arrow update saat user scroll dalam panel ─────────
    document.addEventListener('scroll', function(e) {
        var scrollEl = e.target;
        if (scrollEl && scrollEl.classList && scrollEl.classList.contains('ss-options-scroll')) {
            var panel = scrollEl.closest('.ss-panel');
            if (panel) updateScrollArrows(panel.dataset.uid);
        }
    }, true);

    // ── Setup per instance ────────────────────────────────────────
    document.querySelectorAll('.ss-panel').forEach(function(panel) {
        var uid      = panel.dataset.uid;
        var searchEl = document.getElementById(uid + '-search');
        var valueEl  = document.getElementById(uid + '-value');
        var dropdown = document.getElementById(uid + '-dropdown');
        if (!searchEl || !valueEl || !dropdown) return;

        var scrollEl = dropdown.querySelector('.ss-options-scroll');
        var items    = scrollEl ? scrollEl.querySelectorAll('.ss-option') : [];
        var emptyEl  = scrollEl ? scrollEl.querySelector('.ss-empty') : null;

        function filterOptions() {
            var q = searchEl.value.toLowerCase().trim();
            var visible = 0;
            items.forEach(function(item) {
                var match = item.dataset.label.toLowerCase().includes(q);
                item.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (emptyEl) emptyEl.style.display = visible === 0 ? '' : 'none';
        }

        function showAllOptions() {
            items.forEach(function(item) {
                item.style.display = '';
            });
            if (emptyEl) emptyEl.style.display = 'none';
        }

        function openDropdown() {
            // Portal: pindahkan ke body
            if (dropdown.parentElement !== document.body) {
                document.body.appendChild(dropdown);
            }
            showAllOptions();
            dropdown.style.display = 'block';
            _openUid = uid;
            ssPositionDropdown(uid);
            updateScrollArrows(uid);
            // Highlight border
            searchEl.style.borderColor = '#10b981';
            // Select text
            searchEl.select();
        }

        function closeDropdown(targetUid) {
            if (targetUid && targetUid !== uid) return;
            dropdown.style.display = 'none';
            searchEl.style.borderColor = '';
            
            // Restore search input value to match the hidden valueEl value
            var currentVal = valueEl.value;
            var selectedOpt = Array.from(items).find(function(item) {
                return item.dataset.value === currentVal;
            });
            if (selectedOpt) {
                searchEl.value = selectedOpt.dataset.label;
            } else {
                searchEl.value = '';
            }
            
            if (_openUid === uid) _openUid = null;
        }

        // Expose closeDropdown untuk event global
        dropdown._closeDropdown = closeDropdown;

        // Hover effect
        items.forEach(function(item) {
            item.addEventListener('mouseenter', function() { this.style.background = document.documentElement.classList.contains('dark') ? '#334155' : '#f0fdf4'; });
            item.addEventListener('mouseleave', function() { this.style.background = ''; });
            item.addEventListener('click', function() {
                valueEl.value  = this.dataset.value;
                searchEl.value = this.dataset.label;
                valueEl.setAttribute('value', this.dataset.value);
                valueEl.dispatchEvent(new Event('change', { bubbles: true }));
                closeDropdown(uid);
            });
        });

        // Scroll-fade update saat scroll di dalam dropdown
        if (scrollEl) {
            scrollEl.addEventListener('scroll', function() {
                updateScrollArrows(uid);
            });
        }

        searchEl.addEventListener('focus', openDropdown);

        var debounce;
        searchEl.addEventListener('input', function() {
            clearTimeout(debounce);
            debounce = setTimeout(function() {
                filterOptions();
                if (dropdown.style.display === 'none') openDropdown();
            }, 150);
        });
    });
});
</script>
@endPushOnce
