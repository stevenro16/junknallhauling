<?php

// Quote workqueue filter cards (Quotes section). The admin can reorder / pick
// which appear via Site Content ('quote_filters'). Keyed by the setFilter() value.
//   label — card caption
//   count — the inquiryDashboard getter that supplies the number
//   color — Tailwind text color for the count
//   icon  — optional leading icon (x-icon name)
return [
    'new' => ['label' => 'New', 'count' => 'countNew', 'color' => 'text-blue-600'],
    'reviewing_quoted' => ['label' => 'Reviewing / Quoted', 'count' => 'countReviewingQuoted', 'color' => 'text-indigo-600'],
    'finalize_scheduling' => ['label' => 'Finalize Scheduling', 'count' => 'countFinalizeScheduling', 'color' => 'text-pink-600'],
    'scheduled' => ['label' => 'Scheduled', 'count' => 'countScheduled', 'color' => 'text-purple-600'],
    'service_performed' => ['label' => 'Service Performed', 'count' => 'countServicePerformed', 'color' => 'text-teal-600'],
    'equipment_delivered' => ['label' => 'Equipment Tracker', 'count' => 'countEquipmentOut', 'color' => 'text-cyan-600', 'icon' => 'truck'],
    'left_voicemail' => ['label' => 'Follow Up', 'count' => 'countFollowUp', 'color' => 'text-rose-600'],
    'completed30' => ['label' => 'Completed (30 days)', 'count' => 'countCompleted30', 'color' => 'text-green-700'],
];
