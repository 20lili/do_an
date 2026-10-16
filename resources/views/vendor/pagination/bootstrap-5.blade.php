{{-- Phân trang Bootstrap 5 rút gọn (tiếng Việt). Ghi đè view mặc định của Laravel. --}}
@if ($paginator->hasPages())
    <nav aria-label="Phân trang">
        <ul class="pagination pagination-sm flex-wrap">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled"><span class="page-link">&lsaquo; Trước</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Trước</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau &rsaquo;</a></li>
            @else
                <li class="page-item disabled"><span class="page-link">Sau &rsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
