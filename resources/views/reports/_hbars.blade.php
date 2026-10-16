{{--
    Biểu đồ thanh ngang.
    Tham số: $rows = [['label' => ..., 'value' => số, 'display' => chuỗi hiển thị, 'color' => (tuỳ chọn), 'href' => (tuỳ chọn)], ...]
--}}
@php $max = max(1, collect($rows)->max('value')); @endphp
<div class="hbars">
    @forelse ($rows as $r)
        <div class="hbar-row">
            <div class="hbar-label" title="{{ $r['label'] }}">
                @if (! empty($r['dot'])) <span class="dot" style="background: {{ $r['dot'] }}"></span> @endif
                @if (! empty($r['href'])) <a href="{{ $r['href'] }}" class="text-reset">{{ $r['label'] }}</a> @else {{ $r['label'] }} @endif
            </div>
            <div class="hbar-track"><div class="hbar-fill" style="width: {{ $r['value'] / $max * 100 }}%; {{ ! empty($r['color']) ? 'background:' . $r['color'] : '' }}"></div></div>
            <div class="hbar-value">{{ $r['display'] }}</div>
        </div>
    @empty
        <p class="text-muted small mb-0">Chưa có dữ liệu trong khoảng thời gian này.</p>
    @endforelse
</div>
