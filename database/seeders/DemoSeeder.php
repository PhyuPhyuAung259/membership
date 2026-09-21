<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Member;
use App\Models\MemberType;
use Illuminate\Database\Seeder;

/**
 * Demo members spread across every billing state, so the dashboard and the
 * reminder job have something realistic to work on.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Refuses to run unless mail is going to the log: these addresses are fake
 * and mailing them would damage your sending reputation.
 */
class DemoSeeder extends Seeder
{
    private const FIRST = ['Aisha', 'Budi', 'Chen', 'Dewi', 'Eka', 'Farid', 'Gita', 'Hasan',
        'Indra', 'Joko', 'Kartika', 'Lina', 'Maya', 'Nadia', 'Omar', 'Putri', 'Rizki', 'Siti'];

    private const LAST = ['Santoso', 'Wijaya', 'Kusuma', 'Hidayat', 'Pratama', 'Lestari',
        'Nugroho', 'Halim', 'Suryana', 'Rahman', 'Setiawan', 'Maulana'];

    private const FEES = ['standard' => 25, 'student' => 12, 'senior' => 15, 'family' => 40];

    public function run(int $count = 140): void
    {
        if (! in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->command->error('MAIL_MAILER is not "log". Demo addresses are fake and would bounce.');

            return;
        }

        $memberTypes = collect(self::FEES)->map(
            fn ($fee, $name) => MemberType::firstOrCreate(['name' => ucfirst($name)], ['monthly_fee' => $fee])
        );

        foreach (range(1, $count) as $i) {
            $first = self::FIRST[array_rand(self::FIRST)];
            $last = self::LAST[array_rand(self::LAST)];
            $type = array_rand(self::FEES);

            $member = Member::create([
                'company_name' => "{$first} {$last}",
                'email' => strtolower("{$first}.{$last}{$i}@example.invalid"),
                'phone' => '08' . random_int(1_000_000_00, 9_999_999_99),
                'member_type_id' => $memberTypes[$type]->id,
                'join_date' => now()->subDays(random_int(30, 900))->toDateString(),
                'marketing_opt_in' => random_int(1, 100) > 8,
            ]);

            // Roughly matching a real association: most people current, a
            // tail of late payers, a few who never paid after joining.
            $roll = random_int(1, 100);

            if ($roll > 92) {
                continue; // never paid
            }

            $coverEndsInDays = match (true) {
                $roll <= 62 => random_int(1, 40),
                $roll <= 74 => 0,
                default => -random_int(1, 25),
            };

            $monthsPaid = random_int(2, 12);
            $end = now()->addDays($coverEndsInDays)->startOfDay();
            $start = $end->copy()->subMonthsNoOverflow($monthsPaid)->addDay();

            $member->payments()->create([
                'amount' => self::FEES[$type] * $monthsPaid,
                'paid_on' => $start->toDateString(),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'method' => ['bank_transfer', 'bank_transfer', 'cash', 'card'][array_rand([0, 1, 2, 3])],
                'reference' => 'TRF' . random_int(10_000_000, 99_999_999),
            ]);
        }

        Event::insert([
            [
                'title' => "Monthly members' meeting",
                'body' => "Our regular monthly gathering. We will review the year so far and open the floor for questions.\n\nLight refreshments from 6pm.",
                'event_date' => now()->addDays(12)->toDateString(),
                'event_time' => '6:30 PM',
                'location' => 'Community Hall, Room 2',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'title' => 'Volunteer day at the riverside',
                'body' => 'Bring gloves and a hat. We will provide tools, water and lunch.',
                'event_date' => now()->addDays(26)->toDateString(),
                'event_time' => '8:00 AM',
                'location' => 'Riverside Park, north gate',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $this->command->info("Created {$count} demo members and 2 events.");
    }
}
