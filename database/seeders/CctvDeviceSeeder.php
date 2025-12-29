<?php

namespace Database\Seeders;

use App\Models\cctvDevices;
use App\Models\Locations;
use Illuminate\Database\Seeder;

class CctvDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get location IDs to use in CCTV devices
        $locations = Locations::all();
        
        if ($locations->isEmpty()) {
            $this->command->error('No locations found! Please run LocationSeeder first.');
            return;
        }

        // Sample CCTV devices data
        $cctvDevices = [
            [
                'location_id' => $locations->first()->id,
                'device_name' => 'CCTV-BH-001',
                'primary_rtsp_url' => 'rtsp://admin:admin123@192.168.1.101:554/stream1',
                'backup_rtsp_url' => 'rtsp://admin:admin123@192.168.1.101:554/stream2',
                'status' => 'Active',
                'brand' => 'Hikvision',
                'model' => 'DS-2CD2043G2-I',
                'resolution' => '1920x1080',
                'fps' => 30,
                'installation_date' => '2024-01-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location_id' => $locations->skip(1)->first()->id ?? $locations->first()->id,
                'device_name' => 'CCTV-MP-002',
                'primary_rtsp_url' => 'rtsp://admin:admin123@192.168.1.102:554/stream1',
                'backup_rtsp_url' => null,
                'status' => 'Active',
                'brand' => 'Dahua',
                'model' => 'IPC-HFW5831E-Z5E',
                'resolution' => '3840x2160',
                'fps' => 25,
                'installation_date' => '2024-02-20',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location_id' => $locations->skip(2)->first()->id ?? $locations->first()->id,
                'device_name' => 'CCTV-MR-003',
                'primary_rtsp_url' => 'rtsp://admin:admin123@192.168.1.103:554/stream1',
                'backup_rtsp_url' => 'rtsp://admin:admin123@192.168.1.103:554/stream2',
                'status' => 'Active',
                'brand' => 'Axis',
                'model' => 'P3245-LVE',
                'resolution' => '1920x1080',
                'fps' => 30,
                'installation_date' => '2024-03-10',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location_id' => $locations->first()->id,
                'device_name' => 'CCTV-BH-004',
                'primary_rtsp_url' => 'rtsp://admin:admin123@192.168.1.104:554/stream1',
                'backup_rtsp_url' => 'rtsp://admin:admin123@192.168.1.104:554/stream2',
                'status' => 'Maintenance',
                'brand' => 'Hikvision',
                'model' => 'DS-2CD2386G2-IU',
                'resolution' => '3840x2160',
                'fps' => 20,
                'installation_date' => '2024-01-25',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location_id' => $locations->skip(1)->first()->id ?? $locations->first()->id,
                'device_name' => 'CCTV-MP-005',
                'primary_rtsp_url' => 'rtsp://admin:admin123@192.168.1.105:554/stream1',
                'backup_rtsp_url' => null,
                'status' => 'Inactive',
                'brand' => 'Uniview',
                'model' => 'IPC3615SB-ADF40KM-I0',
                'resolution' => '2560x1440',
                'fps' => 30,
                'installation_date' => '2023-12-05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location_id' => $locations->skip(2)->first()->id ?? $locations->first()->id,
                'device_name' => 'CCTV-MR-006',
                'primary_rtsp_url' => 'rtsp://admin:admin123@192.168.1.106:554/stream1',
                'backup_rtsp_url' => 'rtsp://admin:admin123@192.168.1.106:554/stream2',
                'status' => 'Active',
                'brand' => 'Dahua',
                'model' => 'DH-IPC-HFW2831S-S-S2',
                'resolution' => '3840x2160',
                'fps' => 25,
                'installation_date' => '2024-04-18',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert CCTV devices
        cctvDevices::insert($cctvDevices);

        $this->command->info('CCTV devices seeded successfully!');
    }
}

