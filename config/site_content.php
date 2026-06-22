<?php

// Editable site content — single source of truth for keys, admin labels, types,
// and DEFAULT values (the current hardcoded copy). The public site renders these
// defaults until an admin overrides them via Admin → Site Content.
//
// type: 'html' (rich text) | 'list' (one item per line, e.g. serving areas)
// group: heading the field is shown under in the admin editor.

return [

    // ---- Get a Quote page -----------------------------------------------
    'show_quote_form' => [
        'group'   => 'Get a Quote Page',
        'label'   => 'Show the customer quote form',
        'type'    => 'boolean',
        'default' => true,
    ],

    // ---- About Us -------------------------------------------------------
    'home_about' => [
        'group'   => 'About Us',
        'label'   => 'Home page — “About” section',
        'type'    => 'html',
        'default' => '<p>We are a locally owned and operated hauling company proudly serving the Inland Empire since 2019. Our team shows up on time, gives honest upfront pricing, and works hard to recycle or donate as much material as possible.</p><p>Whether you need a single piece of furniture removed, a full property cleanout, or a dumpster for your renovation — we make the process simple, fast, and stress-free.</p>',
    ],
    'about_page' => [
        'group'   => 'About Us',
        'label'   => 'About page — main text',
        'type'    => 'html',
        'default' => '<p>Junk N All Hauling is a family-owned and operated junk removal and dumpster rental company proudly serving Yucaipa, Redlands, Beaumont, Highland, and all of the Inland Empire since 2019.</p><p>We believe in showing up on time, giving honest upfront pricing with no surprises, and doing everything we can to recycle or donate usable items instead of sending them to the landfill.</p><p>From single-item pickups to full property cleanouts and construction debris, our experienced team handles it all safely and efficiently.</p>',
    ],

    // ---- Services (home cards) ------------------------------------------
    // A managed list of up to 4 cards (icon + title + rich body) shown in the
    // "Our Services" grid on the home page. Fewer than 4 are centered.
    'home_service_cards' => [
        'group'   => 'Services — Home cards',
        'label'   => 'Service cards (up to 4)',
        'type'    => 'cards',
        'max'     => 4,
        'default' => [
            ['icon' => 'truck', 'title' => 'Junk Removal', 'body' => '<p>1/4, 1/2, 3/4 or full truck loads. We load everything — furniture, debris, appliances, yard waste.</p>', 'link_label' => 'Request a Quote', 'link_url' => '/contact'],
            ['icon' => 'package', 'title' => 'Dumpster Rental', 'body' => '<p>10-yard and 20-yard roll-offs. 1-2 tons disposal included. Perfect for renovations and cleanouts.</p>', 'link_label' => 'Request a Quote', 'link_url' => '/contact'],
            ['icon' => 'hammer', 'title' => 'Light Demolition', 'body' => '<p>Our light demolition services include:</p><ul><li>Kitchen/Bathroom Gutting: Ripping out old cabinetry, countertops, sinks, and built-in vanities.</li><li>Flooring Removal: Tearing up carpeting, hardwood, laminate, or chipping away old ceramic tile and subflooring.</li></ul>', 'link_label' => 'Request a Quote', 'link_url' => '/contact'],
            ['icon' => 'wrench', 'title' => 'Hauling & Equipment', 'body' => '<p>Specialty hauling, scissor lifts, excavators, and heavy equipment rentals for larger jobs.</p>', 'link_label' => 'Request a Quote', 'link_url' => '/contact'],
        ],
    ],

    // ---- Services page cards (full sections on /services) ----------------
    'services_page_cards' => [
        'group'   => 'Services — Services page',
        'label'   => 'Service sections (shown on the Services & Pricing page)',
        'type'    => 'cards',
        'max'     => 6,
        'default' => [
            [
                'icon'      => 'truck',
                'title'     => 'Junk Removal',
                'subheader' => 'Book by the load. We do all the loading and hauling.',
                'body'      => '<ul><li><strong>1/4 Load</strong> — Small cleanouts, single items, furniture</li>'
                    .'<li><strong>1/2 Load</strong> — Garage cleanouts, multiple rooms</li>'
                    .'<li><strong>3/4 Load</strong> — Full house or large renovation debris</li>'
                    .'<li><strong>Full Load</strong> — Whole property cleanouts &amp; construction</li></ul>'
                    .'<p>Same-day service available in most areas. Call before 10am for afternoon pickup.</p>',
            ],
            [
                'icon'      => 'package',
                'title'     => 'Dumpster Rentals',
                'subheader' => '10 & 20 yard roll-offs delivered to your driveway.',
                'body'      => '<p><strong>10 Yard Dumpster</strong> — 1 ton disposal included. Perfect for small renovations, garage cleanouts, landscaping. Additional tons: $64 each. Extended rental: $45/day.</p>'
                    .'<p><strong>20 Yard Dumpster</strong> — 2 tons disposal included. Ideal for construction debris, whole-home cleanouts, large projects. Additional tons: $64 each. Extended rental: $45/day.</p>',
            ],
            [
                'icon'      => 'wrench',
                'title'     => 'Equipment & Specialty',
                'subheader' => 'Scissor lifts, excavators, and specialty hauling available.',
                'body'      => '<p><strong>Additional Services:</strong></p>'
                    .'<ul><li>Rental Items #1</li><li>Rental Items #2</li><li>Light Demolition</li>'
                    .'<li>Concrete &amp; heavy debris hauling</li><li>Property cleanouts (foreclosure, estate)</li>'
                    .'<li>Appliance &amp; furniture removal</li></ul>',
            ],
        ],
    ],

    // ---- Serving areas (shown on the quote page) ------------------------
    // Default mirrors config('business.areas').
    'serving_areas' => [
        'group'   => 'Serving Areas',
        'label'   => 'Serving areas (shown on the Get a Quote page)',
        'type'    => 'list',
        'default' => [
            'Yucaipa', 'Redlands', 'Beaumont', 'Highland', 'Loma Linda',
            'San Bernardino', 'Calimesa', 'Banning', 'Cherry Valley', 'Mentone',
        ],
    ],

    // ---- Admin mobile toolbar -------------------------------------------
    // Which admin tools appear as quick buttons in the mobile bottom toolbar
    // (keys from config/admin_tools.php). Edited via checkboxes, stored as a list.
    'admin_mobile_tools' => [
        'group'   => 'Admin — Mobile Toolbar',
        'label'   => 'Quick buttons (mobile bottom toolbar)',
        'type'    => 'list',
        'default' => ['calendar', 'inquiries'],
    ],

];
