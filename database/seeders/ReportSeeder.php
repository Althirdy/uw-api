<?php

namespace Database\Seeders;

use App\Models\Report;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing users to ensure we have valid user_ids
        $users = \App\Models\User::take(5)->get();
        
        if ($users->isEmpty()) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }

        $reports = [
            [
                'user_id'        => $users->first()->id,
                'report_type'    => 'CCTV',
                'transcript'     => 'Illegal dumping of construction waste near Barangay 176E boundary.',
                'description'    => 'Large amount of construction debris and materials dumped along the street near the barangay boundary, blocking part of the road.',
                'latitute'       => '14.7705',
                'longtitude'     => '121.0480',
                'is_acknowledge' => false,
                'status'        => 'pending',
                'acknowledge_by' => $users->first()->id,
            ],
            [
                'user_id'        => $users->skip(1)->first()?->id ?? $users->first()->id,
                'report_type'    => 'CCTV',
                'transcript'     => 'Stray dogs gathering near Barangay 176E health center.',
                'description'    => 'Pack of approximately 5-7 stray dogs congregating near the health center entrance, causing concern for residents and patients.',
                'latitute'       => '14.7708',
                'longtitude'     => '121.0483',
                'is_acknowledge' => false,
                'status'        => 'pending',
                'acknowledge_by' => $users->skip(1)->first()?->id ?? $users->first()->id,
            ],
            [
                'user_id'        => $users->skip(2)->first()?->id ?? $users->first()->id,
                'report_type'    => 'CCTV',
                'transcript'     => 'Motorcycle without license plate parked in Barangay 176E public area.',
                'description'    => 'Unregistered motorcycle has been parked in the same spot for over a week near the basketball court, appears abandoned.',
                'latitute'       => '14.7709',
                'longtitude'     => '121.0486',
                'is_acknowledge' => false,
                'status'        => 'pending',
                'acknowledge_by' => $users->skip(3)->first()?->id ?? $users->first()->id,
            ],
            [
                'user_id'        => $users->skip(3)->first()?->id ?? $users->first()->id,
                'report_type'    => 'CCTV',
                'transcript'     => 'Children playing on main road in Barangay 176E creating safety hazard.',
                'description'    => 'Group of children playing ball games on the main road during afternoon hours, creating potential traffic safety concerns.',
                'latitute'       => '14.7711',
                'longtitude'     => '121.0484',
                'is_acknowledge' => false,
                'status'        => 'pending',
                'acknowledge_by' => $users->first()->id,
            ],
            [
                'user_id'        => $users->skip(4)->first()?->id ?? $users->first()->id,
                'report_type'    => 'CCTV',
                'transcript'     => 'Unauthorized vendor setup blocking entrance at Barangay 176E covered court.',
                'description'    => 'Multiple street vendors have occupied the covered court entrance area, blocking access and creating congestion.',
                'latitute'       => '14.7715',
                'longtitude'     => '121.0490',
                'is_acknowledge' => false,
                'status'        => 'pending',
                'acknowledge_by' => $users->skip(2)->first()?->id ?? $users->first()->id,
            ],
        ];

        foreach ($reports as $reportData) {
            Report::firstOrCreate(
                [
                    'user_id' => $reportData['user_id'],
                    'transcript' => $reportData['transcript'],
                ],
                $reportData
            );
        }

        $this->command->info('Report seeder completed successfully.');
    }
}