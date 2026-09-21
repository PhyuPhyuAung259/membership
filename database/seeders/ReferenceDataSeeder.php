<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\MemberType;
use Illuminate\Database\Seeder;

/**
 * The lookup tables the member form depends on.
 *
 * Unlike DemoSeeder, this one is safe to run in production — it seeds the
 * tiers and industry categories staff will actually pick from, and uses
 * firstOrCreate so re-running it changes nothing.
 *
 *   php artisan db:seed --class=ReferenceDataSeeder
 *
 * Edit the fees and names below to match your association before running it.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'Platinum', 'monthly_fee' => 200, 'sort_order' => 10,
             'description' => 'Full benefits, priority listing in the directory, speaking slots at events.'],
            ['name' => 'Gold', 'monthly_fee' => 120, 'sort_order' => 20,
             'description' => 'Full benefits and an enhanced directory listing.'],
            ['name' => 'Silver', 'monthly_fee' => 60, 'sort_order' => 30,
             'description' => 'Standard membership with a directory listing.'],
            ['name' => 'Associate', 'monthly_fee' => 25, 'sort_order' => 40,
             'description' => 'For sole traders and small firms. Events and newsletter only.'],
        ];

        foreach ($tiers as $tier) {
            MemberType::firstOrCreate(['name' => $tier['name']], $tier);
        }

        $industries = [
            'Agriculture', 'Banking and Finance', 'Construction', 'Education',
            'Energy and Utilities', 'Food and Beverage', 'Healthcare',
            'Hospitality and Tourism', 'Information Technology', 'Legal Services',
            'Logistics and Transport', 'Manufacturing', 'Media and Advertising',
            'Professional Services', 'Real Estate', 'Retail and Wholesale',
            'Telecommunications', 'Textiles and Garments', 'Other',
        ];

        foreach ($industries as $name) {
            BusinessType::firstOrCreate(['name' => $name]);
        }

        $this->command?->info(sprintf(
            'Reference data ready: %d tiers, %d business types.',
            MemberType::count(),
            BusinessType::count(),
        ));
    }
}
