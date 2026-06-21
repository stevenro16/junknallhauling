@extends('layouts.public')

@section('content')
<div class="overflow-hidden">
    {{-- Hero --}}
    <section class="relative min-h-[620px] flex items-center pt-16 pb-12 sm:pb-0"
             style="background-image: linear-gradient(to left, rgba(15,23,42,0.18), rgba(0,0,0,1)), url('{{ asset('images/hero-truck.jpg') }}'); background-size: cover; background-position: center 30%;">
        <div class="container-wide relative z-10">
            <div class="max-w-3xl text-white">
                <div data-reveal="up" class="inline-block uppercase tracking-[3px] text-xs font-bold bg-orange-500/90 px-4 py-1 rounded mb-6">
                    Locally Owned &amp; Operated &bull; Since 2019
                </div>
                <h1 data-reveal="up" data-reveal-delay="80" class="text-6xl sm:text-7xl font-black tracking-[-2.5px] leading-[0.92] mb-6">
                    PROFESSIONAL<br>JUNK REMOVAL<br>&amp; DUMPSTER RENTAL
                </h1>
                <p data-reveal="up" data-reveal-delay="160" class="text-2xl text-white/90 mb-4 font-medium">
                    Serving Yucaipa, Redlands, Beaumont, Highland and the Inland Empire
                </p>
                <p data-reveal="up" data-reveal-delay="220" class="text-lg text-white/70 mb-10 max-w-lg">
                    Fast, reliable, and upfront pricing. We handle the heavy lifting so you don't have to.
                </p>
                <div data-reveal="up" data-reveal-delay="300" class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('contact') }}" class="btn-primary text-base px-10 shadow-lg">
                        GET A QUOTE <x-icon name="arrow-right" class="w-5 h-5"/>
                    </a>
                    <a href="tel:{{ config('business.phoneRaw') }}"
                       class="btn-outline text-base px-9 border-white text-white hover:bg-white hover:text-slate-900 flex items-center gap-2">
                        <x-icon name="phone" class="w-5 h-5"/> {{ config('business.phone') }}
                    </a>
                    <a href="{{ route('status') }}" class="btn-outline text-base px-9 border-white/70 text-white hover:bg-white/10">
                        Check Status
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section class="py-20 bg-white border-b border-gray-100">
        <div class="container-wide">
            <div data-reveal="up" class="text-center mb-12">
                <p class="section-label">What We Offer</p>
                <h2 class="text-5xl font-black tracking-tighter">Our Services</h2>
                <p class="mt-3 text-xl text-slate-600 max-w-md mx-auto">
                    Professional hauling solutions for homes and businesses across the Inland Empire.
                </p>
            </div>

            @php($serviceCards = \App\Models\SiteContent::cards('home_service_cards'))
            <div class="flex flex-wrap justify-center gap-6">
                @foreach($serviceCards as $i => $card)
                    <div data-reveal="up" @if($i) data-reveal-delay="{{ $i * 100 }}" @endif
                         class="service-card basis-full sm:basis-[calc(50%-0.75rem)] lg:basis-[calc(25%-1.125rem)] max-w-sm">
                        <div class="w-16 h-16 rounded-2xl bg-orange-100 flex items-center justify-center mb-6 overflow-hidden">
                            @if(!empty($card['image']))
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] ?? '' }}" class="w-10 h-10 object-contain">
                            @else
                                <x-icon :name="$card['icon'] ?? 'truck'" class="w-9 h-9 text-orange-600"/>
                            @endif
                        </div>
                        <h3 class="font-black text-2xl mb-3">{{ $card['title'] ?? '' }}</h3>
                        @if(!empty($card['subheader']))
                            <p class="text-slate-500 text-sm mb-3">{{ $card['subheader'] }}</p>
                        @endif
                        <div class="text-slate-600 text-[15px] mb-6 flex-1 cms-content">{!! $card['body'] ?? '' !!}</div>
                        @php($ctaLabel = trim($card['link_label'] ?? ''))
                        @php($ctaUrl = trim($card['link_url'] ?? ''))
                        @if($ctaLabel !== '' && $ctaUrl !== '')
                            <a href="{{ \Illuminate\Support\Str::startsWith($ctaUrl, ['http://', 'https://', 'mailto:', 'tel:', '#']) ? $ctaUrl : url($ctaUrl) }}"
                               class="text-orange-600 font-semibold inline-flex items-center gap-1.5 hover:gap-2 transition-all">{{ $ctaLabel }} <x-icon name="arrow-right" class="w-4 h-4"/></a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-10">
                <a href="{{ route('services') }}" class="inline-flex items-center gap-2 text-orange-600 font-semibold hover:text-orange-500">
                    View full pricing &amp; details <x-icon name="arrow-right" class="w-4 h-4"/>
                </a>
            </div>
        </div>
    </section>

    {{-- About --}}
    <section class="py-20 bg-slate-50">
        <div class="container-wide">
            <div class="grid lg:grid-cols-2 gap-14 items-center">
                <div data-reveal="left" class="relative rounded-3xl overflow-hidden shadow-xl aspect-[4/3] lg:aspect-auto lg:h-[520px]">
                    <img src="{{ asset('images/trailer.jpg') }}" alt="Junk N All Hauling team at work" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-r from-black/40 to-black/10"></div>
                </div>
                <div data-reveal="right" data-reveal-delay="120">
                    <p class="section-label">About Us</p>
                    <h2 class="text-5xl font-black tracking-tighter mb-6">ABOUT JUNK N ALL HAULING</h2>
                    <div class="text-lg text-slate-700 leading-relaxed cms-content">{!! \App\Models\SiteContent::html('home_about') !!}</div>
                    <div class="mt-8 grid sm:grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-gray-200 bg-white p-6">
                            <div class="font-bold text-orange-600 mb-1">Licensed &amp; Insured</div>
                            <div class="text-sm text-slate-600">Fully bonded professionals you can trust on your property.</div>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-6">
                            <div class="font-bold text-orange-600 mb-1">Same-Day Service</div>
                            <div class="text-sm text-slate-600">Call in the morning — often on-site that afternoon.</div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('contact') }}" class="btn-primary inline-flex">Get a Free Quote Today</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust bar --}}
    <section class="border-b border-gray-100 py-8 bg-white">
        <div class="container-wide">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-y-6 text-center">
                <div data-reveal="up"><div class="font-black text-4xl text-orange-500">6+</div><div class="text-sm uppercase tracking-widest text-slate-500 mt-1">Years Serving the IE</div></div>
                <div data-reveal="up" data-reveal-delay="100"><div class="font-black text-4xl text-orange-500">1000s</div><div class="text-sm uppercase tracking-widest text-slate-500 mt-1">Jobs Completed</div></div>
                <div data-reveal="up" data-reveal-delay="200"><div class="font-black text-4xl text-orange-500">Same Day</div><div class="text-sm uppercase tracking-widest text-slate-500 mt-1">Service Available</div></div>
                <div data-reveal="up" data-reveal-delay="300"><div class="font-black text-4xl text-orange-500">100%</div><div class="text-sm uppercase tracking-widest text-slate-500 mt-1">Upfront Pricing</div></div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-16">
        <div data-reveal="up" class="container-wide text-center">
            <h2 class="text-4xl font-black tracking-tight mb-4">Ready to get rid of that junk?</h2>
            <p class="text-xl text-slate-600 mb-8 max-w-md mx-auto">Upload photos for the fastest, most accurate quote. We usually reply the same day.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('contact') }}" class="btn-primary text-base px-12">Request Quote with Photos</a>
                <a href="tel:{{ config('business.phoneRaw') }}" class="btn-outline text-base px-10 flex items-center justify-center gap-2">
                    <x-icon name="phone" class="w-5 h-5"/> Call {{ config('business.phone') }}
                </a>
            </div>
            <p class="mt-6 text-xs text-slate-500">Serving all of Yucaipa, Redlands, Beaumont, Highland, Loma Linda, San Bernardino &amp; surrounding areas.</p>
        </div>
    </section>
</div>
@endsection
