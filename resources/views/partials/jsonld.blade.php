@php
    $biz = config('business');
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $biz['name'],
        'description' => 'Junk removal, dumpster rental, light demolition, and equipment rental serving the Inland Empire.',
        'url' => url('/'),
        'telephone' => $biz['phoneRaw'],
        'email' => $biz['email'],
        'image' => asset('images/trailer.jpg'),
        'priceRange' => '$$',
        'areaServed' => array_map(fn ($city) => ['@type' => 'City', 'name' => $city.', CA'], $biz['areas']),
    ];
@endphp
<script type="application/ld+json">@json($jsonLd)</script>
