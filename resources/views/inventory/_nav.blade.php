{{-- Thanh điều hướng con của module kho --}}
<nav class="sub-nav" aria-label="Chức năng kho">
    <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.index') ? 'active' : '' }}"><i class="bi bi-boxes"></i> Tồn kho</a>
    @can('receive-stock')
        <a href="{{ route('inventory.receive') }}" class="{{ request()->routeIs('inventory.receive') ? 'active' : '' }}"><i class="bi bi-box-arrow-in-down"></i> Nhập kho</a>
    @endcan
    <a href="{{ route('inventory.transactions') }}" class="{{ request()->routeIs('inventory.transactions') ? 'active' : '' }}"><i class="bi bi-clock-history"></i> Lịch sử nhập – xuất</a>
</nav>
