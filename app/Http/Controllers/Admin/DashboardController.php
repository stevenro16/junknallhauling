<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\EquipmentType;
use App\Models\Inquiry;
use App\Models\ServiceCatalog;
use App\Services\DemoSeeder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        DemoSeeder::ensure();

        $section = $request->query('section', 'inquiries');
        if (! in_array($section, ['inquiries', 'stats', 'services', 'equipment', 'admins', 'content'], true)) {
            $section = 'inquiries';
        }

        $inquiries = Inquiry::with('rentalAgreements')->orderByDesc('created_at')->get();

        $byStatus = $inquiries->countBy('status');
        $newCount = ($byStatus['new'] ?? 0) + ($byStatus['reviewing'] ?? 0) + ($byStatus['quoted'] ?? 0);
        $scheduled = $byStatus['scheduled'] ?? 0;
        $pending = $byStatus['service_performed'] ?? 0;
        $cutoff = now()->subDays(30)->toISOString();
        $completedRecent = $inquiries->where('status', 'completed')
            ->filter(fn (Inquiry $i) => (string) $i->created_at >= $cutoff)->count();

        $counts = [
            'new'            => $newCount,
            'scheduled'      => $scheduled,
            'pending'        => $pending,
            'completed30'    => $completedRecent,
            'workqueueTotal' => $newCount + $scheduled + $pending,
        ];

        // Slim payload (no base64 photos) for client-side analytics + map.
        $statsInquiries = $inquiries->map(fn (Inquiry $i) => [
            'id' => $i->id, 'ref' => $i->ref, 'name' => $i->name, 'status' => $i->status,
            'service_type' => $i->service_type, 'quoted_price' => $i->quoted_price,
            'created_at' => $i->created_at, 'confirmed_date_time' => $i->confirmed_date_time,
            'address' => $i->address, 'zip_code' => $i->zip_code,
            'latitude' => $i->latitude, 'longitude' => $i->longitude,
            'payment_method' => $i->payment_method,
        ])->values();

        return view('admin.dashboard', [
            'section'        => $section,
            'inquiries'      => $inquiries,
            'statsInquiries' => $statsInquiries,
            'counts'         => $counts,
            'services'       => ServiceCatalog::orderByDesc('active')->orderBy('key')->get(),
            'equipment'      => EquipmentType::orderByDesc('active')->orderBy('name')->get(),
            'admins'         => Admin::orderBy('created_at')->get(),
        ]);
    }
}
