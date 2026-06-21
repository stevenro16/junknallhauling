<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Generates a batch of realistic, randomized quotes for local/demo use —
 * varied services, equipment rentals, statuses, prices, and image attachments.
 *
 *   php artisan db:seed --class=RandomQuoteSeeder
 */
class RandomQuoteSeeder extends Seeder
{
    private const COUNT = 20;

    public function run(): void
    {
        $first = ['James', 'Maria', 'Robert', 'Linda', 'Michael', 'Patricia', 'David', 'Jennifer', 'William', 'Elizabeth', 'Carlos', 'Ana', 'Kevin', 'Sandra', 'Brian', 'Nicole', 'Jose', 'Emily', 'Daniel', 'Rachel', 'Tyler', 'Megan'];
        $last = ['Smith', 'Johnson', 'Garcia', 'Martinez', 'Nguyen', 'Patel', 'Brown', 'Davis', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Hernandez', 'Moore', 'Kline', 'Reyes', 'Foster', 'Bennett', 'Cole', 'Ramirez'];
        $streets = ['Oak St', 'Pine Ave', 'Maple Dr', 'Cedar Ln', 'Sunset Blvd', 'Highland Ave', 'Yucaipa Blvd', 'Citrus Ct', 'Palm Way', 'Ridge Rd', '5th St', 'Orange Tree Ln'];
        $areas = [
            'Yucaipa' => '92399', 'Redlands' => '92374', 'Beaumont' => '92223', 'Highland' => '92346',
            'Loma Linda' => '92354', 'San Bernardino' => '92408', 'Calimesa' => '92320', 'Banning' => '92220', 'Mentone' => '92359',
        ];
        $equipment = ['Scissor Lift (19-26 ft)', 'Mini Excavator (3-5 ton)', 'Skid Steer Loader', 'Boom Lift (40-60 ft)', 'Forklift (5-8k lb)', 'Towable Manlift'];
        $statuses = ['new', 'reviewing', 'quoted', 'scheduled', 'service_performed', 'completed', 'completed', 'cancelled', 'left_voicemail'];
        $payMethods = ['Cash', 'Check', 'Credit/Debit Card', 'Venmo', 'Zelle', 'Online Payment'];
        $contactMethods = ['phone', 'email'];
        $prefDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Any weekday'];
        $prefTimes = ['Morning', 'Afternoon', 'Evening', 'Flexible'];

        $serviceDesc = [
            'junk-removal' => ['Garage cleanout — old furniture and boxes.', 'Couch, mattress, and ~10 bags of yard waste.', 'Estate cleanout, full single-car garage.', 'Broken appliances and scrap metal.'],
            '10yd-dumpster' => ['Bathroom remodel debris, need it ~3 days.', 'Roofing tear-off, single layer.', 'Small kitchen reno — tile and drywall.'],
            '20yd-dumpster' => ['Full home renovation debris.', 'Large landscaping/concrete removal job.', 'Commercial cleanout, week-long rental.'],
            'equipment' => ['Need it to reach a 2nd-story roofline.', 'Backyard grading and trenching.', 'Pallet moving for a warehouse move.', 'Tree-trimming access on a slope.'],
            'other' => ['Not sure what I need — please call to discuss.', 'Light demolition of a backyard shed.', 'Mixed job, want an on-site estimate.'],
        ];

        $employeeIds = Admin::where('role', 'employee')->pluck('id')->all();

        for ($n = 0; $n < self::COUNT; $n++) {
            $service = $this->pick(['junk-removal', '10yd-dumpster', '20yd-dumpster', 'equipment', 'other']);
            $isEquip = $service === 'equipment';
            $status = $this->pick($statuses);

            $area = array_rand($areas);
            $zip = $areas[$area];
            $name = $this->pick($first).' '.$this->pick($last);

            // Backdate creation across the last ~120 days; schedule the visit a few days later.
            $createdAt = Carbon::now()->subDays(mt_rand(0, 120))->subHours(mt_rand(0, 23));
            $visit = (clone $createdAt)->addDays(mt_rand(2, 10))->setTime(mt_rand(7, 16), $this->pick([0, 0, 30]));

            $data = [
                'name' => $name,
                'phone' => sprintf('(%d) %03d-%04d', $this->pick([909, 951]), mt_rand(200, 899), mt_rand(1000, 9999)),
                'email' => strtolower(str_replace(' ', '.', $name)).mt_rand(1, 99).'@example.com',
                'service_type' => $service,
                'description' => $this->pick($serviceDesc[$service]),
                'address' => mt_rand(100, 9999).' '.$this->pick($streets).', '.$area.' CA '.$zip,
                'zip_code' => $zip,
                'status' => $status,
                'preferred_contact_method' => $this->pick($contactMethods),
                'preferred_day' => $this->pick($prefDays),
                'preferred_time' => $this->pick($prefTimes),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if ($isEquip) {
                $data['equipment_type'] = $this->pick($equipment);
                $unit = $this->pick(['hours', 'days']);
                $data['equipment_rental_unit'] = $unit;
                $data['equipment_rental_duration'] = $unit === 'days' ? mt_rand(1, 5) : mt_rand(2, 8);
            }

            // Estimate shown to most customers up front.
            if (mt_rand(0, 100) < 80) {
                $data['initial_estimated_quote'] = $this->pick([150, 200, 250, 300, 375, 450, 600, 750]);
            }

            // Quoted price + schedule once the lead has progressed.
            if (in_array($status, ['quoted', 'scheduled', 'service_performed', 'completed'], true)) {
                $data['quoted_price'] = $this->pick([175, 225, 285, 350, 425, 500, 650, 800, 950]);
                $data['expected_duration_minutes'] = $this->pick([60, 90, 120, 180, 240]);
            }
            if (in_array($status, ['scheduled', 'service_performed', 'completed'], true)) {
                $data['confirmed_date_time'] = $visit->format('Y-m-d\TH:i:s');
                if ($employeeIds && mt_rand(0, 1)) {
                    $data['assigned_employee_id'] = $this->pick($employeeIds);
                }
            }
            if ($status === 'completed') {
                $data['payment_method'] = $this->pick($payMethods);
                $data['payment_date'] = $visit->format('Y-m-d\TH:i:s');
            }
            if (in_array($status, ['reviewing', 'quoted', 'scheduled', 'service_performed', 'completed'], true) && mt_rand(0, 1)) {
                $data['admin_notes'] = $this->pick(['Confirmed details by phone.', 'Customer requested same-day if possible.', 'Gate code on file; call on arrival.', 'Bring extra straps for the load.']);
            }

            // ~45% get an image attachment (a generated colored thumbnail).
            if (mt_rand(0, 100) < 45) {
                $data['photo_base64'] = $this->makePng();
                $data['photo_mime'] = 'image/png';
            }

            Inquiry::withoutTimestamps(fn () => Inquiry::create($data));
        }

        $this->command?->info('Created '.self::COUNT.' random quotes.');
    }

    private function pick(array $a)
    {
        return $a[array_rand($a)];
    }

    /** Tiny generated PNG so attachments look real; falls back to a 1x1 if GD is missing. */
    private function makePng(): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        }
        $w = 160;
        $h = 120;
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, mt_rand(40, 220), mt_rand(40, 220), mt_rand(40, 220)));
        for ($i = 0; $i < 6; $i++) {
            $c = imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagefilledrectangle($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $c);
        }
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return base64_encode($bytes);
    }
}
