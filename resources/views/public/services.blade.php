@extends('layouts.public')

@section('title', 'Our Services & Pricing | '.config('business.name'))

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
        {{-- Junk Removal --}}
        <div class="grid lg:grid-cols-5 gap-12 mb-20 items-start">
            <div data-reveal="left" class="lg:col-span-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-100 mb-5">
                    <x-icon name="truck" class="w-8 h-8 text-orange-600"/>
                </div>
                <h2 class="text-4xl font-black tracking-tight mb-4">Junk Removal</h2>
                <p class="text-lg text-slate-600">Book by the load. We do all the loading and hauling.</p>
            </div>
            <div data-reveal="right" data-reveal-delay="120" class="lg:col-span-3">
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach([
                        ['load' => '1/4 LOAD', 'desc' => 'Small cleanouts, single items, furniture'],
                        ['load' => '1/2 LOAD', 'desc' => 'Garage cleanouts, multiple rooms'],
                        ['load' => '3/4 LOAD', 'desc' => 'Full house or large renovation debris'],
                        ['load' => 'FULL LOAD', 'desc' => 'Whole property cleanouts & construction'],
                    ] as $item)
                        <div class="card p-6 border-l-4 border-orange-500">
                            <div class="font-black text-2xl tracking-tight">{{ $item['load'] }}</div>
                            <div class="text-slate-600 mt-1">{{ $item['desc'] }}</div>
                        </div>
                    @endforeach
                </div>
                <p class="text-sm text-slate-500 mt-4">Same-day service available in most areas. Call before 10am for afternoon pickup.</p>
            </div>
        </div>

        {{-- Dumpster Rentals --}}
        <div class="grid lg:grid-cols-5 gap-12 mb-20 items-start border-t border-gray-100 pt-16">
            <div data-reveal="left" class="lg:col-span-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-100 mb-5">
                    <x-icon name="package" class="w-8 h-8 text-orange-600"/>
                </div>
                <h2 class="text-4xl font-black tracking-tight mb-4">Dumpster Rentals</h2>
                <p class="text-lg text-slate-600">10 &amp; 20 yard roll-offs delivered to your driveway.</p>
            </div>
            <div data-reveal="right" data-reveal-delay="120" class="lg:col-span-3 space-y-6">
                <div class="card p-7">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-black text-2xl">10 Yard Dumpster</div>
                            <div class="text-sm text-orange-600 font-semibold mt-0.5">1 TON DISPOSAL INCLUDED</div>
                        </div>
                        <div class="text-right text-sm text-slate-500">One Day &bull; Multi-Day</div>
                    </div>
                    <ul class="mt-5 space-y-1.5 text-[15px] text-slate-600">
                        <li>&bull; Perfect for small renovations, garage cleanouts, landscaping</li>
                        <li>&bull; Additional tons: <span class="font-semibold">$64</span> each</li>
                        <li>&bull; Extended rental: <span class="font-semibold">$45/day</span></li>
                    </ul>
                </div>
                <div class="card p-7">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-black text-2xl">20 Yard Dumpster</div>
                            <div class="text-sm text-orange-600 font-semibold mt-0.5">2 TONS DISPOSAL INCLUDED</div>
                        </div>
                        <div class="text-right text-sm text-slate-500">One Day &bull; Multi-Day</div>
                    </div>
                    <ul class="mt-5 space-y-1.5 text-[15px] text-slate-600">
                        <li>&bull; Ideal for construction debris, whole-home cleanouts, large projects</li>
                        <li>&bull; Additional tons: <span class="font-semibold">$64</span> each</li>
                        <li>&bull; Extended rental: <span class="font-semibold">$45/day</span></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Other Services --}}
        <div class="border-t border-gray-100 pt-16">
            <div class="grid lg:grid-cols-5 gap-12 items-start">
                <div data-reveal="left" class="lg:col-span-2">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-100 mb-5">
                        <x-icon name="wrench" class="w-8 h-8 text-orange-600"/>
                    </div>
                    <h2 class="text-4xl font-black tracking-tight mb-4">Equipment &amp; Specialty</h2>
                    <p class="text-lg text-slate-600">Scissor lifts, excavators, and specialty hauling available.</p>
                </div>
                <div data-reveal="right" data-reveal-delay="120" class="lg:col-span-3">
                    <div class="card p-8">
                        <h3 class="font-bold text-xl mb-3">Additional Services</h3>
                        <ul class="grid sm:grid-cols-2 gap-x-8 gap-y-2 text-[15px] text-slate-700">
                            <li>&bull; Rental Items #1</li>
                            <li>&bull; Rental Items #2</li>
                            <li>&bull; Light Demolition</li>
                            <li>&bull; Concrete &amp; heavy debris hauling</li>
                            <li>&bull; Property cleanouts (foreclosure, estate)</li>
                            <li>&bull; Appliance &amp; furniture removal</li>
                        </ul>
                        <div class="mt-8">
                            <a href="{{ route('contact') }}" class="btn-primary">Get a Quote for Specialty Work</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div data-reveal="up" class="mt-16 pt-10 border-t text-center">
            <p class="text-lg mb-4">Ready to book or need a custom quote?</p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('contact') }}" class="btn-primary">Get Free Quote with Photos</a>
                <a href="tel:{{ config('business.phoneRaw') }}" class="btn-outline flex items-center gap-2">
                    <x-icon name="phone" class="w-4 h-4"/> Call {{ config('business.phone') }}
                </a>
            </div>
            <p class="text-xs text-slate-500 mt-6">Serving {{ implode(', ', config('business.areas')) }}</p>
        </div>
    </section>
</div>
@endsection
