<footer class="bg-charcoal-800 border-t border-white/10 mt-auto text-white">
    <div class="container-wide py-14">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            {{-- Brand --}}
            <div>
                <a href="{{ route('home') }}" class="inline-block mb-3">
                    <img src="/images/logo.jpg" alt="{{ config('business.name') }}" width="240" height="63"
                         class="h-[63px] w-auto drop-shadow-[0_1px_2px_rgba(255,255,255,0.9)]">
                </a>
                <p class="text-slate-400 text-sm leading-relaxed mb-5">
                    Professional junk removal and dumpster rental serving the Inland Empire. Reliable, affordable, and licensed.
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach(config('business.areas') as $city)
                        <span class="text-xs bg-white/10 text-slate-300 px-2.5 py-1 rounded-full border border-white/10">{{ $city }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h3 class="font-black text-sm uppercase tracking-widest text-orange-500 mb-4">Quick Links</h3>
                <ul class="flex flex-col gap-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="text-slate-400 hover:text-orange-400">Home</a></li>
                    <li><a href="{{ route('services') }}" class="text-slate-400 hover:text-orange-400">Services</a></li>
                    <li><a href="{{ route('about') }}" class="text-slate-400 hover:text-orange-400">About Us</a></li>
                    <li><a href="{{ route('reviews') }}" class="text-slate-400 hover:text-orange-400">Reviews</a></li>
                    <li><a href="{{ route('contact') }}" class="text-slate-400 hover:text-orange-400">Get a Quote</a></li>
                    <li><a href="{{ route('status') }}" class="text-slate-400 hover:text-orange-400">Check Request Status</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <h3 class="font-black text-sm uppercase tracking-widest text-orange-500 mb-4">Contact Us</h3>
                <ul class="flex flex-col gap-3 text-sm">
                    <li>
                        <a href="tel:{{ config('business.phoneRaw') }}" class="flex items-center gap-3 text-slate-400 hover:text-orange-400">
                            <x-icon name="phone" class="w-4 h-4 text-orange-500"/> {{ config('business.phone') }}
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ config('business.email') }}" class="flex items-center gap-3 text-slate-400 hover:text-orange-400">
                            <x-icon name="mail" class="w-4 h-4 text-orange-500"/> {{ config('business.email') }}
                        </a>
                    </li>
                    <li class="flex items-start gap-3 text-slate-400">
                        <x-icon name="map-pin" class="w-4 h-4 text-orange-500 mt-0.5"/>
                        <span>Yucaipa, Redlands, Beaumont, Highland &amp; surrounding Inland Empire</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-white/10 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} {{ config('business.name') }}. All rights reserved. &nbsp;&bull;&nbsp; Locally Owned &amp; Operated Since 2019
        </div>
    </div>
</footer>
