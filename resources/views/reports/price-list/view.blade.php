<div class="container">
    @if($type == '1' || $type == '')
    <iframe src="{{ asset('reports/inventory_.pdf') }}" width="100%" height="100%"></iframe>
    @else
    <iframe src="{{ asset('reports/inventory-by-item_.pdf') }}" width="100%" height="100%"></iframe>
    @endif
</div>
