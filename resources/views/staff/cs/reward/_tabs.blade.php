@php
    $tabs = [
        ['Stok',          'staff.cs.reward.index',            'staff.cs.reward.index'],
        ['Riwayat Masuk', 'staff.cs.reward.stock-ins.index',  'staff.cs.reward.stock-ins.*'],
        ['Riwayat Keluar',   'staff.cs.reward.stock-outs.index', 'staff.cs.reward.stock-outs.*'],
    ];
@endphp

<div class="mb-4 flex flex-wrap gap-2">
    @foreach ($tabs as [$label, $route, $pattern])
        <a href="{{ route($route) }}" class="btn {{ request()->routeIs($pattern) ? 'btn-brand' : 'btn-outline' }}">{{ $label }}</a>
    @endforeach
</div>