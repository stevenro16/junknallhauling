@php
    $collapsible = $collapsible ?? false;
    $mobile = $mobile ?? false;
    $path = request()->path();
    $onDashboard = $path === 'admin';
    $qsection = request()->query('section', 'inquiries');
    $current = $onDashboard && in_array($qsection, ['inquiries', 'stats', 'services', 'equipment', 'admins', 'content'], true) ? $qsection : ($onDashboard ? 'inquiries' : null);
    $onCalendar = $path === 'admin/calendar';
    $onCustomers = $path === 'admin/customers';
    $onEod = $path === 'admin/eod-report';
    $isEmployee = session('admin_role') === 'employee';

    if ($isEmployee) {
        // Employees only get their own schedule.
        $main = [
            ['key' => 'my-schedule', 'label' => 'My Schedule', 'icon' => 'calendar', 'href' => route('admin.my-schedule'), 'active' => str_starts_with($path, 'admin/my-schedule')],
        ];
        $settingsItems = [];
    } else {
        $main = [
            ['key' => 'inquiries', 'label' => 'Quotes', 'icon' => 'file-text', 'href' => route('admin.dashboard', ['section' => 'inquiries']), 'active' => $current === 'inquiries'],
            ['key' => 'calendar', 'label' => 'Calendar', 'icon' => 'calendar', 'href' => route('admin.calendar'), 'active' => $onCalendar],
            ['key' => 'customers', 'label' => 'Customers', 'icon' => 'user', 'href' => route('admin.customers'), 'active' => $onCustomers],
            ['key' => 'eod', 'label' => 'EOD Report', 'icon' => 'clock', 'href' => route('admin.eod-report'), 'active' => $onEod],
            ['key' => 'stats', 'label' => 'Analytics', 'icon' => 'bar-chart', 'href' => route('admin.dashboard', ['section' => 'stats']), 'active' => $current === 'stats'],
        ];
        $settingsItems = [
            ['key' => 'content', 'label' => 'Site Content', 'icon' => 'pencil', 'href' => route('admin.dashboard', ['section' => 'content']), 'active' => $current === 'content'],
            ['key' => 'admins', 'label' => 'Account Management', 'icon' => 'users', 'href' => route('admin.dashboard', ['section' => 'admins']), 'active' => $current === 'admins'],
            ['key' => 'services', 'label' => 'Service Catalog', 'icon' => 'package', 'href' => route('admin.dashboard', ['section' => 'services']), 'active' => $current === 'services'],
            ['key' => 'equipment', 'label' => 'Equipment Catalog', 'icon' => 'truck', 'href' => route('admin.dashboard', ['section' => 'equipment']), 'active' => $current === 'equipment'],
        ];
    }
    $activeCls = 'bg-[#F8C820]/10 text-[#F8C820] border border-[#EAB308]/30';
    $idleCls = 'text-gray-300 hover:bg-charcoal-700 hover:text-white';
@endphp

<div class="bg-charcoal-800 flex flex-col transition-[width] duration-200 {{ $mobile ? '' : 'h-full border-r border-charcoal-700' }} {{ (! $collapsible && ! $mobile) ? 'w-64' : '' }}"
     @if($collapsible) :class="navExpanded ? 'w-64' : 'w-16'" @endif>
    @unless($mobile)
    <div class="p-4 border-b border-charcoal-700">
        @if($collapsible)
            <div class="flex items-center gap-2" :class="navExpanded ? 'justify-between' : 'justify-center'">
                <a href="{{ route('home') }}" @click="mobileOpen = false" x-show="navExpanded" x-cloak
                   title="Back to main website"
                   class="flex items-center hover:opacity-90 transition-opacity">
                    <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('business.name') }}" width="160" height="42"
                         class="h-9 w-auto drop-shadow-[0_1px_2px_rgba(255,255,255,0.9)]">
                </a>
                <button @click="navExpanded = !navExpanded" class="p-1.5 text-gray-400 hover:text-white shrink-0"
                        :title="navExpanded ? 'Collapse menu' : 'Expand menu'">
                    <x-icon name="menu" class="w-5 h-5"/>
                </button>
            </div>
            <div class="text-[11px] uppercase tracking-widest text-gray-400 mt-2" x-show="navExpanded" x-cloak>Admin Panel</div>
        @else
            <a href="{{ route('home') }}" @click="mobileOpen = false"
               title="Back to main website"
               class="flex items-center hover:opacity-90 transition-opacity">
                <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('business.name') }}" width="160" height="42"
                     class="h-9 w-auto drop-shadow-[0_1px_2px_rgba(255,255,255,0.9)]">
            </a>
            <div class="text-[11px] uppercase tracking-widest text-gray-400 mt-2">Admin Panel</div>
        @endif
    </div>
    @endunless

    <nav class="flex-1 p-2 space-y-1 overflow-y-auto overflow-x-hidden">
        @foreach($main as $item)
            <a href="{{ $item['href'] }}" @click="mobileOpen = false"
               class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors {{ $item['active'] ? $activeCls : $idleCls }}"
               @if($collapsible) :class="navExpanded ? '' : 'justify-center'" :title="navExpanded ? '' : @js($item['label'])" @endif>
                <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0"/>
                <span class="flex-1 truncate" @if($collapsible) x-show="navExpanded" x-cloak @endif>{{ $item['label'] }}</span>
                @if($item['key'] === 'inquiries' && ($workqueueTotal ?? 0) > 0)
                    <span class="ml-auto min-w-[20px] text-center text-xs font-bold px-1.5 py-0.5 rounded-full bg-[#F8C820] text-charcoal-900"
                          @if($collapsible) x-show="navExpanded" x-cloak @endif>{{ $workqueueTotal }}</span>
                @endif
            </a>
        @endforeach

        @if(count($settingsItems))
            @if($collapsible)
                <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-gray-500" x-show="navExpanded" x-cloak>Settings</div>
                <div class="mx-3 my-3 border-t border-charcoal-700" x-show="!navExpanded" x-cloak></div>
            @else
                <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-gray-500">Settings</div>
            @endif
        @endif

        @foreach($settingsItems as $item)
            <a href="{{ $item['href'] }}" @click="mobileOpen = false"
               class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors {{ $item['active'] ? $activeCls : $idleCls }}"
               @if($collapsible) :class="navExpanded ? '' : 'justify-center'" :title="navExpanded ? '' : @js($item['label'])" @endif>
                <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0"/>
                <span class="flex-1 truncate" @if($collapsible) x-show="navExpanded" x-cloak @endif>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-auto border-t border-charcoal-700">
        <button @click="{{ $collapsible ? 'navExpanded ? (userMenuOpen = !userMenuOpen) : (navExpanded = true)' : 'userMenuOpen = !userMenuOpen' }}"
                class="w-full flex items-center justify-between px-3 py-2.5 text-sm text-gray-300 hover:bg-charcoal-700 hover:text-white transition-colors"
                @if($collapsible) :class="navExpanded ? '' : 'justify-center'" :title="navExpanded ? '' : @js($currentUsername ?? 'Account')" @endif>
            <div class="flex items-center gap-3 min-w-0"><x-icon name="user" class="w-4 h-4 shrink-0"/><span class="truncate" @if($collapsible) x-show="navExpanded" x-cloak @endif>{{ $currentUsername ?? 'Account' }}</span></div>
            <x-icon name="chevron-down" class="w-4 h-4 shrink-0 transition-transform" ::class="userMenuOpen && 'rotate-180'" @if($collapsible) x-show="navExpanded" x-cloak @endif/>
        </button>
        <div x-show="userMenuOpen @if($collapsible) && navExpanded @endif" x-cloak class="pb-2 bg-charcoal-900/40">
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
