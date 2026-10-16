{{--
    Biểu đồ cột doanh thu theo ngày (HTML/CSS, không dùng thư viện).
    Tham số: $points = collection [['date' => Carbon, 'revenue' => float, 'orders' => int], ...]
             $metric = 'revenue' (mặc định, vẽ doanh thu) | 'orders' (vẽ số đơn – dùng cho nhân viên, mục 5.8)
--}}
@php
    use App\Services\ReportService;
    $metric = $metric ?? 'revenue';
    $isMoney = $metric === 'revenue';
    $max   = ReportService::niceMax((float) $points->max($metric));
    $fmtY  = fn ($v) => $isMoney ? ReportService::shortMoney($v) : rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',');
    $n     = $points->count();
    $every = max(1, (int) ceil($n / 8));            // chỉ ghi nhãn ngày cách quãng để không chồng chữ
@endphp
<div class="colchart" role="img" aria-label="Biểu đồ {{ $isMoney ? 'doanh thu' : 'số đơn' }} theo ngày, cao nhất {{ $isMoney ? \App\Models\Product::vnd($points->max('revenue')) : $points->max('orders') . ' đơn' }}">
    <div class="colchart-y">
        <span>{{ $fmtY($max) }}</span>
        <span>{{ $fmtY($max / 2) }}</span>
        <span>0</span>
    </div>
    <div class="colchart-plot">
        <div class="colchart-grid"><i></i><i></i><i></i></div>
        <div class="colchart-bars" style="--n: {{ $n }}">
            @foreach ($points as $i => $p)
                <div class="col-slot" tabindex="0"
                     data-tip="{{ $p['date']->format('d/m') }}: {{ $isMoney ? \App\Models\Product::vnd($p['revenue']) . ' · ' : '' }}{{ $p['orders'] }} đơn">
                    <div class="col-bar {{ $p[$metric] > 0 ? '' : 'zero' }}" style="height: {{ $p[$metric] / $max * 100 }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="colchart-x" style="--n: {{ $n }}">
            @foreach ($points as $i => $p)
                <span>{{ ($n - 1 - $i) % $every === 0 ? $p['date']->format('d/m') : '' }}</span>
            @endforeach
        </div>
    </div>
</div>
