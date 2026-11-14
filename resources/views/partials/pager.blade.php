@if ($paginator->hasPages())
<nav class="pager" aria-label="Halaman">
  @if ($paginator->onFirstPage())<span class="off">‹</span>@else<a href="{{ $paginator->previousPageUrl() }}">‹</a>@endif
  @foreach ($elements as $el)
    @if (is_string($el))<span class="off">{{ $el }}</span>@endif
    @if (is_array($el))
      @foreach ($el as $page => $url)
        @if ($page == $paginator->currentPage())<span class="on">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
      @endforeach
    @endif
  @endforeach
  @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">›</a>@else<span class="off">›</span>@endif
</nav>
@endif

