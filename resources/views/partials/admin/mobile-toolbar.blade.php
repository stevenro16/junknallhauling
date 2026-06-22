{{-- Mobile bottom toolbar — admin quick-jump buttons, configurable in Site Content
     (config/admin_tools.php × the 'admin_mobile_tools' setting). Admins only. --}}
@php
    $toolKeys = session('admin_role') === 'admin' ? \App\Models\SiteContent::list('admin_mobile_tools') : [];
    $allTools = config('admin_tools', []);
    $path = request()->path();
    $section = request()->query('section', 'inquiries');

    $tools = [];
    foreach ($toolKeys as $k) {
        if (! isset($allTools[$k])) {
            continue;
        }
        $t = $allTools[$k];
        $active = isset($t['section'])
            ? ($path === 'admin' && $section === $t['section'])
            : (isset($t['path']) && str_starts_with($path, $t['path']));
        $href = isset($t['section']) ? route('admin.dashboard', ['section' => $t['section']]) : route($t['route']);
        $tools[] = $t + ['href' => $href, 'active' => $active];
    }
@endphp

@if(count($tools))
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 h-14 bg-white border-t border-gray-200 flex print:hidden shadow-[0_-1px_3px_rgba(0,0,0,0.06)]">
        @foreach($tools as $t)
            <a href="{{ $t['href'] }}"
               class="flex-1 min-w-0 flex flex-col items-center justify-center gap-0.5 py-2 transition-colors {{ $t['active'] ? 'text-amber-600' : 'text-gray-500 hover:text-gray-700' }}">
                <x-icon name="{{ $t['icon'] }}" class="w-5 h-5 shrink-0"/>
                <span class="text-[10px] font-medium truncate max-w-full px-0.5">{{ $t['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endif
