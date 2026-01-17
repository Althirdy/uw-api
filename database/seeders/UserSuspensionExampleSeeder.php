<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Database\Seeder;

class UserSuspensionExampleSeeder extends Seeder
{
    /**
     * Seed example suspension data for testing
     *
     * This seeder creates example scenarios:
     * 1. User with no suspensions (can receive Warning 1)
     * 2. User with expired Warning 1 (can receive Warning 2 or Permanent)
     * 3. User with active Warning 1 (can receive Warning 2 or Permanent)
     * 4. User with Warning 2 (can only receive Permanent)
     * 5. Permanently suspended user (no more options)
     */
    public function run(): void
    {
        // Get or create an admin user to apply suspensions
        $admin = User::where('role_id', 1)->first();

        if (! $admin) {
            $this->command->error('No admin user found. Please create an admin user first.');

            return;
        }

        // Find some citizen users (role_id = 3)
        $citizens = User::where('role_id', 3)->limit(5)->get();

        if ($citizens->count() < 5) {
            $this->command->warn('Not enough citizen users found. Creating example suspensions for available users only.');
        }

        // Scenario 1: User with expired Warning 1
        if ($citizens->count() > 0) {
            $user1 = $citizens[0];
            UserSuspension::create([
                'user_id' => $user1->id,
                'punishment_type' => 'warning_1',
                'duration_days' => 3,
                'suspended_at' => now()->subDays(10),
                'expires_at' => now()->subDays(7),
                'status' => 'expired',
                'reason' => 'First offense: Submitted fake fire emergency report',
                'suspended_by' => $admin->id,
            ]);
            $this->command->info("✓ Created expired Warning 1 for user: {$user1->email}");
        }

        // Scenario 2: User with active Warning 1
        if ($citizens->count() > 1) {
            $user2 = $citizens[1];
            UserSuspension::create([
                'user_id' => $user2->id,
                'punishment_type' => 'warning_1',
                'duration_days' => 3,
                'suspended_at' => now()->subDay(),
                'expires_at' => now()->addDays(2),
                'status' => 'active',
                'reason' => 'Submitted multiple bluff reports about non-existent accidents',
                'suspended_by' => $admin->id,
            ]);
            $this->command->info("✓ Created active Warning 1 for user: {$user2->email}");
        }

        // Scenario 3: User with Warning 1 and Warning 2
        if ($citizens->count() > 2) {
            $user3 = $citizens[2];

            // First warning (expired)
            UserSuspension::create([
                'user_id' => $user3->id,
                'punishment_type' => 'warning_1',
                'duration_days' => 3,
                'suspended_at' => now()->subDays(30),
                'expires_at' => now()->subDays(27),
                'status' => 'expired',
                'reason' => 'First offense: Fake medical emergency',
                'suspended_by' => $admin->id,
            ]);

            // Second warning (active)
            UserSuspension::create([
                'user_id' => $user3->id,
                'punishment_type' => 'warning_2',
                'duration_days' => 7,
                'suspended_at' => now()->subDays(2),
                'expires_at' => now()->addDays(5),
                'status' => 'active',
                'reason' => 'Second offense: Continued to submit fake reports despite warning',
                'suspended_by' => $admin->id,
            ]);
            $this->command->info("✓ Created Warning 1 + active Warning 2 for user: {$user3->email}");
        }

        // Scenario 4: Permanently suspended user
        if ($citizens->count() > 3) {
            $user4 = $citizens[3];

            // Warning 1 (expired)
            UserSuspension::create([
                'user_id' => $user4->id,
                'punishment_type' => 'warning_1',
                'duration_days' => 3,
                'suspended_at' => now()->subDays(60),
                'expires_at' => now()->subDays(57),
                'status' => 'expired',
                'reason' => 'First offense',
                'suspended_by' => $admin->id,
            ]);

            // Warning 2 (expired)
            UserSuspension::create([
                'user_id' => $user4->id,
                'punishment_type' => 'warning_2',
                'duration_days' => 7,
                'suspended_at' => now()->subDays(30),
                'expires_at' => now()->subDays(23),
                'status' => 'expired',
                'reason' => 'Second offense',
                'suspended_by' => $admin->id,
            ]);

            // Permanent suspension
            UserSuspension::create([
                'user_id' => $user4->id,
                'punishment_type' => 'suspension',
                'duration_days' => null,
                'suspended_at' => now()->subDays(10),
                'expires_at' => null,
                'status' => 'active',
                'reason' => 'Third offense: Persistent abuse of emergency reporting system. Permanent ban applied.',
                'suspended_by' => $admin->id,
            ]);

            // Update user status to suspended
            if ($user4->citizenDetails) {
                $user4->citizenDetails->update(['status' => 'suspended']);
            }

            $this->command->info("✓ Created permanent suspension for user: {$user4->email}");
        }

        // Scenario 5: User with revoked suspension
        if ($citizens->count() > 4) {
            $user5 = $citizens[4];

            UserSuspension::create([
                'user_id' => $user5->id,
                'punishment_type' => 'warning_1',
                'duration_days' => 3,
                'suspended_at' => now()->subDays(5),
                'expires_at' => now()->subDays(2),
                'status' => 'revoked',
                'reason' => 'Misunderstanding - report was legitimate. Revoked by admin.',
                'suspended_by' => $admin->id,
            ]);

            $this->command->info("✓ Created revoked Warning 1 for user: {$user5->email}");
        }

        $this->command->info("\n📊 Suspension scenarios created successfully!");
        $this->command->info('You can now test the suspension system with these users.');
        $this->command->info("\nTo test:");
        $this->command->info('1. Visit /users in your browser');
        $this->command->info('2. Click suspend on different users to see available punishments');
        $this->command->info('3. Check that the correct options appear based on their history');
    }
}
