{{-- Privacy Policy + Terms & Conditions pop-ups. Toggled via the footer's
     x-data `legal` ('privacy' | 'terms' | null). Static content — these are
     standard templates; have them reviewed for your business as needed. --}}
@php($bizName = config('business.name'))
@php($bizPhone = config('business.phone'))
@php($bizEmail = config('business.email'))

<div x-show="legal" x-cloak
     x-effect="document.documentElement.style.overflow = legal ? 'hidden' : ''"
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
     role="dialog" aria-modal="true"
     x-transition.opacity>

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60" @click="legal = null"></div>

    {{-- Panel --}}
    <div class="relative bg-white text-gray-700 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col"
         @keydown.escape.window="legal = null">
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-200 shrink-0">
            <h2 class="text-lg font-bold text-charcoal-900" x-text="legal === 'terms' ? 'Terms & Conditions' : 'Privacy Policy'"></h2>
            <button type="button" @click="legal = null" class="p-2 -mr-2 text-gray-400 hover:text-gray-700 transition-colors" aria-label="Close">
                <x-icon name="x" class="w-5 h-5"/>
            </button>
        </div>

        <div class="overflow-y-auto px-6 py-5 text-sm leading-relaxed">
            {{-- ===================== Privacy Policy ===================== --}}
            <div x-show="legal === 'privacy'" class="space-y-3">
                <p class="text-xs text-gray-400">Last updated: June 2026</p>

                <p>{{ $bizName }} (“we,” “us,” or “our”) respects your privacy. This Privacy Policy explains how we collect, use, and protect your information when you use our website, request a quote, or communicate with us.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Information We Collect</h3>
                <ul class="list-disc pl-5 space-y-1 text-gray-600">
                    <li>Contact details you provide: name, phone number, email address, and service address.</li>
                    <li>Details about your service request, including any photos you choose to upload.</li>
                    <li>Basic technical data (such as IP address) used to operate and secure the site.</li>
                </ul>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">How We Use Your Information</h3>
                <p class="text-gray-600">We use your information to respond to quote requests, schedule and perform services, process payments, send service-related updates, and improve our services. We do not sell your personal information.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">SMS / Text Messaging</h3>
                <p class="text-gray-600">By providing your mobile number and opting in, you consent to receive service-related text messages from {{ $bizName }} (for example, quote confirmations, appointment reminders, and payment notifications).</p>
                <ul class="list-disc pl-5 space-y-1 text-gray-600">
                    <li><strong>Message frequency varies</strong> based on your interactions with us — for example, when you request a quote, confirm an appointment, or make a payment.</li>
                    <li><strong>Message and data rates may apply.</strong></li>
                    <li>You can opt out at any time by replying <strong>STOP</strong>. Reply <strong>HELP</strong> for help, or contact us at {{ $bizPhone }} or {{ $bizEmail }}.</li>
                </ul>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Mobile Information — No Sharing</h3>
                <p class="text-gray-600">We do <strong>not</strong> share, sell, rent, or trade your mobile phone number or SMS opt-in/consent information with any third parties or affiliates for marketing or promotional purposes. Mobile opt-in data is used solely to deliver the messages you have requested.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">How We Protect Your Information</h3>
                <p class="text-gray-600">We use reasonable administrative and technical safeguards to protect your information. No method of transmission or storage is 100% secure, but we work to keep your data safe.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Third-Party Services</h3>
                <p class="text-gray-600">We use trusted service providers (such as payment and messaging providers) only as needed to operate our services. These providers are required to protect your information and may not use it for their own marketing.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Your Choices</h3>
                <p class="text-gray-600">You may request access to, correction of, or deletion of your personal information by contacting us. You may opt out of text messages at any time by replying STOP.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Contact Us</h3>
                <p class="text-gray-600">{{ $bizName }} &middot; {{ $bizPhone }} &middot; {{ $bizEmail }}</p>
            </div>

            {{-- ===================== Terms & Conditions ===================== --}}
            <div x-show="legal === 'terms'" class="space-y-3">
                <p class="text-xs text-gray-400">Last updated: June 2026</p>

                <p>These Terms &amp; Conditions (“Terms”) govern your use of the {{ $bizName }} website and services. By using our site or requesting services, you agree to these Terms.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Services</h3>
                <p class="text-gray-600">{{ $bizName }} provides junk removal, dumpster rental, light demolition, and equipment rental services in the Inland Empire, California. Service availability, pricing, and scheduling are subject to confirmation.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Quotes &amp; Pricing</h3>
                <p class="text-gray-600">Quotes are estimates based on the information you provide and may change after an on-site assessment of the actual volume, weight, materials, or access involved. Final pricing is confirmed before work is completed.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Payment</h3>
                <p class="text-gray-600">Payment is due as agreed at the time of service unless otherwise arranged. We accept the payment methods shown at checkout or arranged with our team.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Scheduling, Cancellations &amp; Access</h3>
                <p class="text-gray-600">You agree to provide safe and lawful access to the service location and to notify us promptly of any changes. Missed appointments or late cancellations may be subject to a fee.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Dumpster &amp; Equipment Rentals</h3>
                <p class="text-gray-600">You are responsible for rented dumpsters and equipment while in your possession, including safe and lawful use and any damage, overloading, or prohibited materials. Overage, additional rental days, and disposal of restricted items may incur additional charges.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Prohibited Items</h3>
                <p class="text-gray-600">Certain hazardous or restricted materials (such as hazardous waste, chemicals, flammable liquids, or asbestos) may not be accepted. Please ask us if you are unsure.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Limitation of Liability</h3>
                <p class="text-gray-600">To the fullest extent permitted by law, {{ $bizName }} is not liable for indirect, incidental, or consequential damages arising from our services or your use of this site. Our total liability will not exceed the amount paid for the service in question.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Text Messaging</h3>
                <p class="text-gray-600">If you opt in to text messages, our SMS program is governed by our Privacy Policy. Message frequency varies and message and data rates may apply. Reply STOP to opt out or HELP for help.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Changes to These Terms</h3>
                <p class="text-gray-600">We may update these Terms from time to time. Continued use of our services constitutes acceptance of the updated Terms.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Governing Law</h3>
                <p class="text-gray-600">These Terms are governed by the laws of the State of California, without regard to conflict-of-law principles.</p>

                <h3 class="text-base font-semibold text-charcoal-900 pt-2">Contact Us</h3>
                <p class="text-gray-600">{{ $bizName }} &middot; {{ $bizPhone }} &middot; {{ $bizEmail }}</p>
            </div>
        </div>
    </div>
</div>
