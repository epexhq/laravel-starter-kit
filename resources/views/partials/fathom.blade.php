@if (filled(config('services.fathom.site_id')) && ! config('app.debug'))
    <script src="https://cdn.usefathom.com/script.js" data-site="{{ config('services.fathom.site_id') }}" data-spa="auto" defer></script>
@endif
