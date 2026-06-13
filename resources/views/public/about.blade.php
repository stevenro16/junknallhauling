@extends('layouts.public')

@section('title', 'About '.config('business.name'))

@section('content')
<div>
    <section class="bg-charcoal-800 py-16 text-white">
        <div class="container-wide">
            <p class="section-label !text-orange-400">Established 2019</p>
            <h1 class="text-6xl font-black tracking-tighter">About Junk N All Hauling</h1>
            <p class="mt-4 max-w-2xl text-2xl text-white/80">Locally owned. Professionally operated. Serving the Inland Empire with pride.</p>
        </div>
    </section>

    <section class="container-wide py-16">
        <div class="grid lg:grid-cols-2 gap-14">
            <div data-reveal="left" class="text-slate-700">
                <p class="text-xl mb-5">Junk N All Hauling is a family-owned and operated junk removal and dumpster rental company proudly serving Yucaipa, Redlands, Beaumont, Highland, and all of the Inland Empire since 2019.</p>
                <p class="mb-5">We believe in showing up on time, giving honest upfront pricing with no surprises, and doing everything we can to recycle or donate usable items instead of sending them to the landfill.</p>
                <p>From single-item pickups to full property cleanouts and construction debris, our experienced team handles it all safely and efficiently.</p>

                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach([
                        'Licensed & fully insured',
                        'Upfront pricing — no hidden fees',
                        'Same-day service available',
                        'Eco-friendly disposal & donation',
                        'Residential & commercial',
                        'Professional, uniformed crews',
                    ] as $item)
                        <div class="flex items-start gap-3 text-base">
                            <x-icon name="check-circle" class="w-5 h-5 text-orange-500 mt-0.5 shrink-0"/>
                            <span>{{ $item }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div data-reveal="right" data-reveal-delay="120" class="relative rounded-3xl overflow-hidden shadow-2xl aspect-[16/10] lg:aspect-auto lg:min-h-[420px]">
                <img src="{{ asset('images/about-team.jpg') }}" alt="Our hauling team" class="absolute inset-0 w-full h-full object-cover">
            </div>
        </div>

        {{-- Values --}}
        <div class="mt-20">
            <p class="section-label text-center">Our Promise</p>
            <h2 class="text-center text-4xl font-black tracking-tight mb-10">Fast. Honest. Responsible.</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach([
                    ['title' => 'On Time, Every Time', 'desc' => 'We show up when we say we will. Your time matters.'],
                    ['title' => 'No Surprise Pricing', 'desc' => 'You get the price before we start. What we quote is what you pay.'],
                    ['title' => 'Do the Right Thing', 'desc' => 'We recycle and donate whenever possible because it\'s the right thing to do.'],
                ] as $v)
                    <div data-reveal="up" data-reveal-delay="{{ $loop->index * 120 }}" class="card p-8 text-center">
                        <div class="font-black text-2xl mb-3 text-orange-600">{{ $v['title'] }}</div>
                        <p class="text-slate-600">{{ $v['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div data-reveal="up" class="mt-16 text-center border-t pt-12">
            <p class="text-2xl font-medium mb-6">Let us handle the heavy lifting for you.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('contact') }}" class="btn-primary px-10">Get a Free Quote</a>
                <a href="tel:{{ config('business.phoneRaw') }}" class="btn-outline px-8 flex items-center justify-center gap-2">
                    <x-icon name="phone" class="w-5 h-5"/> Call {{ config('business.phone') }}
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
