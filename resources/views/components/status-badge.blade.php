{{--
    Status Badge Component (D6)
    Usage: @include('components.status-badge', ['status' => $status])
    Or:    @include('components.status-badge', ['status' => $status, 'size' => 'sm'])
--}}
@php
    $size = $size ?? 'md';
    $sizeClass = match($size) {
        'sm' => 'px-1.5 py-0.5 text-[9px]',
        'lg' => 'px-3 py-1 text-xs',
        default => 'px-2 py-0.5 text-[10px]',
    };

    $statusConfig = match($status ?? null) {
        'Darurat' => [
            'bg' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800',
            'label' => 'Defisiensi Berat',
        ],
        'Segera' => [
            'bg' => 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 ring-1 ring-orange-200 dark:ring-orange-800',
            'label' => 'Perlu Pupuk',
        ],
        'Normal' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800',
            'label' => 'Sehat',
        ],
        'Tunda' => [
            'bg' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-600',
            'label' => 'Tunda Pupuk',
        ],
        default => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-800',
            'label' => 'Belum Dicek',
        ],
    };
@endphp

<span class="inline-flex items-center rounded-full font-semibold {{ $sizeClass }} {{ $statusConfig['bg'] }}">
    {{ $statusConfig['label'] }}
</span>
