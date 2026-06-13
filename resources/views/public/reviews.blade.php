@extends('layouts.public')

@section('title', 'Customer Reviews | '.config('business.name'))
@section('description', 'See what customers are saying about '.config('business.name').' — trusted junk removal and hauling services in the Inland Empire.')

@section('content')
<div>
    <section class="bg-charcoal-800 py-16 text-white">
        <div class="container-wide">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex">
                    @for($i = 0; $i < 5; $i++)
                        <x-icon name="star" class="w-6 h-6 text-[#F8C820]"/>
                    @endfor
                </div>
                <span class="text-[#F8C820] font-semibold">4.9 / 5 on Google</span>
            </div>
            <h1 class="text-6xl font-black tracking-tighter">Customer Reviews</h1>
            <p class="mt-4 max-w-2xl text-2xl text-white/80">Real feedback from our customers across the Inland Empire.</p>
        </div>
    </section>

    <section class="container-wide py-16">
        <div data-reveal="up" class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-semibold tracking-tight">What Our Customers Are Saying</h2>
                <p class="text-slate-600 mt-2 max-w-md mx-auto">Real reviews pulled directly from our Google Business Profile.</p>
            </div>
            <div class="text-center mb-10">
                <p class="text-xl text-slate-600">We're proud of the relationships we build with every job. Here's what people are saying about {{ config('business.name') }}.</p>
            </div>
        </div>

        {{-- Reviews widget --}}
        <div data-reveal="up" class="max-w-[1120px] mx-auto mb-12">
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm overflow-hidden">
                <div class="elfsight-app-8a68d883-d43f-4ed8-a3a1-274cbf6528ad" data-elfsight-app-lazy></div>
            </div>
            <p class="text-center text-xs text-gray-500 mt-3">Reviews update automatically from Google</p>
        </div>

        {{-- Maps embed --}}
        <div data-reveal="up" class="max-w-[1120px] mx-auto mb-12">
            <h3 class="text-xl font-semibold mb-4 text-center">Find us on Google Maps</h3>
            <div class="rounded-2xl overflow-hidden border border-gray-200 shadow-sm">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d232800!2d-117.179!3d34.004!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40f441654f4fd1c7%3A0x844e352fea78ce39!2sJunk-N-All%20Hauling!5e0!3m2!1sen!2sus!4v1710000000000"
                    width="100%" height="525" style="border:0" allowfullscreen loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade" title="{{ config('business.name') }} on Google Maps"></iframe>
            </div>
        </div>

        {{-- CTA --}}
        <div data-reveal="up" class="max-w-4xl mx-auto text-center border-t pt-10">
            <p class="text-lg mb-4 text-slate-600">Had a great experience with us?</p>
            <a href="https://maps.app.goo.gl/ricPF3H1gLiibiev7" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2 px-8 py-4 rounded-lg bg-[#F8C820] text-charcoal-900 font-bold text-sm uppercase tracking-wider hover:bg-[#EAB308] transition-colors">
                Leave a Review on Google
                <x-icon name="external-link" class="w-4 h-4"/>
            </a>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://elfsightcdn.com/platform.js" async></script>
@endpush
