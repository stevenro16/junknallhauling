@extends('layouts.public')

@section('title', 'Our Services & Pricing | '.config('business.name'))
@section('description', 'Junk removal, 10 and 20 yard dumpster rentals, and equipment rentals with upfront pricing in Yucaipa, Redlands, and the Inland Empire. Call '.config('business.phone').'.')

@section('content')
<div>
    <section class="bg-charcoal-800 py-16 text-white">
        <div class="container-wide">
            <p class="uppercase tracking-[2px] text-orange-400 text-sm font-bold mb-3">Transparent Pricing</p>
            <h1 class="text-6xl font-black tracking-tighter">Our Services &amp; Pricing</h1>
            <p class="mt-4 max-w-xl text-xl text-white/70">Load-based junk removal and dumpster rentals with no hidden fees. 1-2 tons disposal included.</p>
        </div>
    </section>

    <section class="container-wide py-16">
        {{-- Service sections — managed in Admin → Site Content --}}
        @php($servicePageCards = \App\Models\SiteContent::cards('services_page_cards'))
        @foreach($servicePageCards as $i => $card)
            <div class="grid lg:grid-cols-5 gap-12 items-start {{ $i ? 'border-t border-gray-100 pt-16 mt-16' : 'mb-4' }}">
                <div data-reveal="left" class="lg:col-span-2">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-100 mb-5 overflow-hidden">
                        @if(!empty($card['image']))
                            <img src="{{ $card['image'] }}" alt="{{ $card['title'] ?? '' }}" class="w-8 h-8 object-contain">
                        @else
                            <x-icon :name="$card['icon'] ?? 'truck'" class="w-8 h-8 text-orange-600"/>
                        @endif
                    </div>
                    <h2 class="text-4xl font-black tracking-tight {{ !empty($card['subheader']) ? 'mb-3' : 'mb-4' }}">{{ $card['title'] ?? '' }}</h2>
                    @if(!empty($card['subheader']))
                        <p class="text-lg text-slate-600">{{ $card['subheader'] }}</p>
                    @endif
                </div>
                <div data-reveal="right" data-reveal-delay="120" class="lg:col-span-3">
                    <div class="card p-7 text-[15px] text-slate-700">
                        <div class="cms-content">{!! $card['body'] ?? '' !!}</div>
                        @php($ctaLabel = trim($card['link_label'] ?? ''))
                        @php($ctaUrl = trim($card['link_url'] ?? ''))
                        @if($ctaLabel !== '' && $ctaUrl !== '')
                            <a href="{{ \Illuminate\Support\Str::startsWith($ctaUrl, ['http://', 'https://', 'mailto:', 'tel:', '#']) ? $ctaUrl : url($ctaUrl) }}"
                               class="mt-5 text-orange-600 font-semibold inline-flex items-center gap-1.5 hover:gap-2 transition-all">{{ $ctaLabel }} <x-icon name="arrow-right" class="w-4 h-4"/></a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div data-reveal="up" class="mt-16 pt-10 border-t text-center">
            <p class="text-lg mb-4">Ready to book or need a custom quote?</p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('contact') }}" class="btn-primary">Get Free Quote with Photos</a>
                <a href="tel:{{ config('business.phoneRaw') }}" class="btn-outline flex items-center gap-2">
                    <x-icon name="phone" class="w-4 h-4"/> Call {{ config('business.phone') }}
                </a>
            </div>
            <p class="text-xs text-slate-500 mt-6">Serving {{ implode(', ', \App\Models\SiteContent::list('serving_areas')) }}</p>
        </div>
    </section>
</div>
@endsection
