<?php

// Admin tools available to the mobile bottom toolbar (and its Site Content picker).
// `section` tools open the dashboard with ?section=…; others use their own route.
return [
    'inquiries' => ['label' => 'Quotes', 'icon' => 'file-text', 'route' => 'admin.dashboard', 'section' => 'inquiries'],
    'calendar' => ['label' => 'Calendar', 'icon' => 'calendar', 'route' => 'admin.calendar', 'path' => 'admin/calendar'],
    'customers' => ['label' => 'Customers', 'icon' => 'user', 'route' => 'admin.customers', 'path' => 'admin/customers'],
    'eod' => ['label' => 'EOD', 'icon' => 'clock', 'route' => 'admin.eod-report', 'path' => 'admin/eod-report'],
    'stats' => ['label' => 'Analytics', 'icon' => 'bar-chart', 'route' => 'admin.dashboard', 'section' => 'stats'],
    'content' => ['label' => 'Content', 'icon' => 'pencil', 'route' => 'admin.dashboard', 'section' => 'content'],
    'admins' => ['label' => 'Accounts', 'icon' => 'user', 'route' => 'admin.dashboard', 'section' => 'admins'],
    'services' => ['label' => 'Services', 'icon' => 'package', 'route' => 'admin.dashboard', 'section' => 'services'],
    'equipment' => ['label' => 'Equipment', 'icon' => 'truck', 'route' => 'admin.dashboard', 'section' => 'equipment'],
];
