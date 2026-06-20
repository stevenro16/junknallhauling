@php
    $path = request()->path();
    $onDashboard = $path === 'admin';
    $qsection = request()->query('section', 'inquiries');
    $current = $onDashboard && in_array($qsection, ['inquiries', 'stats', 'services', 'equipment', 'admins', 'content'], true) ? $qsection : ($onDashboard ? 'inquiries' : null);
    $onCalendar = $path === 'admin/calendar';
    $isAnySettingActive = in_array($current, ['admins', 'services', 'equipment', 'content'], true);

    $main = [
        ['key' => 'inquiries', 'label' => 'Quotes', 'icon' => 'file-text', 'href' => route('admin.dashboard', ['section' => 'inquiries']), 'active' => $current === 'inquiries'],
        ['key' => 'calendar', 'label' => 'Calendar', 'icon' => 'calendar', 'href' => route('admin.calendar'), 'active' => $onCalendar],
        ['key' => 'stats', 'label' => 'Analytics', 'icon' => 'bar-chart', 'href' => route('admin.dashboard', ['section' => 'stats']), 'active' => $current === 'stats'],
    ];
    $settingsItems = [
        ['key' => 'content', 'label' => 'Site Content', 'icon' => 'pencil', 'href' => route('admin.dashboard', ['section' => 'content']), 'active' => $current === 'content'],
        ['key' => 'admins', 'label' => 'Admin Accounts', 'icon' => 'users', 'href' => route('admin.dashboard', ['section' => 'admins']), 'active' => $current === 'admins'],
        ['key' => 'services', 'label' => 'Service Catalog', 'icon' => 'package', 'href' => route('admin.dashboard', ['section' => 'services']), 'active' => $current === 'services'],
        ['key' => 'equipment', 'label' => 'Equipment Catalog', 'icon' => 'truck', 'href' => route('admin.dashboard', ['section' => 'equipment']), 'active' => $current === 'equipment'],
    ];
    $activeCls = 'bg-[#F8C820]/10 text-[#F8C820] border border-[#EAB308]/30';
    $idleCls = 'text-gray-300 hover:bg-charcoal-700 hover:text-white';
@endphp

<div class="w-64 bg-charcoal-800 border-r border-charcoal-700 flex flex-col h-full">
    <div class="p-4 border-b border-charcoal-700">
        <a href="{{ route('home') }}" @click="mobileOpen = false"
           title="Back to main website"
           class="flex items-center hover:opacity-90 transition-opacity">
            <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('business.name') }}" width="160" height="42"
                 class="h-9 w-auto drop-shadow-[0_1px_2px_rgba(255,255,255,0.9)]">
        </a>
        <div class="text-[11px] uppercase tracking-widest text-gray-400 mt-2">Admin Panel</div>
    </div>

    <nav class="flex-1 p-2 space-y-1 overflow-y-auto">
        @foreach($main as $item)
            <a href="{{ $item['href'] }}" @click="mobileOpen = false"
               class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors {{ $item['active'] ? $activeCls : $idleCls }}">
                <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0"/>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if($item['key'] === 'inquiries' && ($workqueueTotal ?? 0) > 0)
                    <span class="ml-auto min-w-[20px] text-center text-xs font-bold px-1.5 py-0.5 rounded-full bg-[#F8C820] text-charcoal-900">{{ $workqueueTotal }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="mt-auto border-t border-charcoal-700">
        <button @click="settingsOpen = !settingsOpen"
                class="w-full flex items-center justify-between px-3 py-2.5 text-sm text-gray-300 hover:bg-charcoal-700 hover:text-white transition-colors">
            <div class="flex items-center gap-3"><x-icon name="settings" class="w-4 h-4 shrink-0"/><span>Settings</span></div>
            <x-icon name="chevron-down" class="w-4 h-4 transition-transform" ::class="(settingsOpen || {{ $isAnySettingActive ? 'true' : 'false' }}) && 'rotate-180'"/>
        </button>

        <div x-show="settingsOpen || {{ $isAnySettingActive ? 'true' : 'false' }}" class="pl-4 pb-2 space-y-1 bg-charcoal-900/40">
            @foreach($settingsItems as $item)
                <a href="{{ $item['href'] }}" @click="mobileOpen = false"
                   class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $item['active'] ? $activeCls : $idleCls }}">
                    <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0"/><span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="border-t border-charcoal-700">
            <button @click="userMenuOpen = !userMenuOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm text-gray-300 hover:bg-charcoal-700 hover:text-white transition-colors">
                <div class="flex items-center gap-3 min-w-0"><x-icon name="user" class="w-4 h-4 shrink-0"/><span class="truncate">{{ $currentUsername ?? 'Account' }}</span></div>
                <x-icon name="chevron-down" class="w-4 h-4 shrink-0 transition-transform" ::class="userMenuOpen && 'rotate-180'"/>
            </button>
            <div x-show="userMenuOpen" x-cloak class="pb-2 bg-charcoal-900/40">
                <button @click="openAccount()" class="w-full flex items-center gap-3 px-5 py-2 text-sm text-gray-300 hover:bg-charcoal-700 hover:text-white transition-colors text-left">
                    <x-icon name="user" class="w-4 h-4 shrink-0"/><span>Account Settings</span>
                </button>
                <button @click="openPwd()" class="w-full flex items-center gap-3 px-5 py-2 text-sm text-gray-300 hover:bg-charcoal-700 hover:text-white transition-colors text-left">
                    <x-icon name="key-round" class="w-4 h-4 shrink-0"/><span>Change Password</span>
                </button>
                <button @click="signOut()" class="w-full flex items-center gap-3 px-5 py-2 text-sm text-red-400 hover:bg-charcoal-700 hover:text-red-300 transition-colors text-left">
                    <x-icon name="log-out" class="w-4 h-4 shrink-0"/><span class="truncate">{{ $currentUsername ? 'Sign out '.$currentUsername : 'Sign Out' }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
