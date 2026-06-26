<?php

// Ported from the Next.js app's lib/constants.ts — single source of truth for
// business identity, public service options, and inquiry status vocabulary.

return [
    'name' => 'Junk N All Hauling',
    'shortName' => 'Haul',
    'phone' => '(909) 459-9503',
    'phoneRaw' => '+19094599503',
    'email' => 'junknallhauling@gmail.com',

    'areas' => [
        'Yucaipa', 'Redlands', 'Beaumont', 'Highland', 'Loma Linda',
        'San Bernardino', 'Calimesa', 'Banning', 'Cherry Valley', 'Mentone',
    ],

    // Public service dropdown (value => label).
    'service_options' => [
        ['value' => 'junk-removal',  'label' => 'Junk Removal'],
        ['value' => '10yd-dumpster', 'label' => '10 Yard Dumpster Rental'],
        ['value' => '20yd-dumpster', 'label' => '20 Yard Dumpster Rental'],
        ['value' => 'equipment',     'label' => 'Equipment Rental (Scissor Lift / Excavator)'],
        ['value' => 'other',         'label' => 'Other / Not Sure'],
    ],

    // Canonical admin status flow (left_voicemail + equipment_delivered are off-path actions).
    'status_options' => [
        'new', 'reviewing', 'quoted', 'scheduled',
        'service_performed', 'completed', 'cancelled',
    ],

    'status_labels' => [
        'new' => 'New',
        'left_voicemail' => 'Left Voicemail',
        'reviewing' => 'Reviewing',
        'quoted' => 'Quoted',
        'finalize_scheduling' => 'Finalize Scheduling',
        'scheduled' => 'Scheduled',
        'equipment_delivered' => 'Equipment Delivered',
        'equipment_picked_up' => 'Equipment Picked Up',
        'service_performed' => 'Service Performed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    // Events an admin can subscribe to notifications for (Notifications settings page).
    // The keys are stored in each admin's notification_preferences; labels/descriptions
    // drive the settings UI. (Sending isn't wired up yet — this is the control panel.)
    'notification_events' => [
        'new_quote' => [
            'label' => 'New quote request',
            'description' => 'A customer submits a quote request from the website.',
        ],
        'details_submitted' => [
            'label' => 'Customer submitted details',
            'description' => 'A customer completes the request-details form for their quote.',
        ],
        'agreement_signed' => [
            'label' => 'Rental agreement signed',
            'description' => 'A customer signs a rental agreement.',
        ],
        'payment_received' => [
            'label' => 'Payment received',
            'description' => 'A customer pays online, or a payment is recorded for a quote.',
        ],
    ],
];
