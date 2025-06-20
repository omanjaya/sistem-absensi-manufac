<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Holiday;
use App\Models\WorkPeriod;
use App\Models\Salary;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clear existing data
        User::truncate();
        Schedule::truncate();
        Attendance::truncate();
        Leave::truncate();
        Holiday::truncate();
        WorkPeriod::truncate();
        Salary::truncate();

        // Create admin user (if not exists)
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@absensi.com',
            'password' => Hash::make('password'),
            'employee_id' => 'ADM001',
            'phone' => '+628123456789',
            'department' => 'Management',
            'position' => 'System Administrator',
            'role' => 'admin',
            'join_date' => Carbon::now()->subYear(),
            'basic_salary' => 10000000,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create sample employees
        $employees = [
            [
                'name' => 'John Doe',
                'email' => 'john@absensi.com',
                'employee_id' => 'EMP001',
                'department' => 'IT',
                'position' => 'Software Engineer',
                'basic_salary' => 8000000,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@absensi.com',
                'employee_id' => 'EMP002',
                'department' => 'HR',
                'position' => 'HR Manager',
                'basic_salary' => 7500000,
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@absensi.com',
                'employee_id' => 'EMP003',
                'department' => 'Finance',
                'position' => 'Accountant',
                'basic_salary' => 6500000,
            ],
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@absensi.com',
                'employee_id' => 'EMP004',
                'department' => 'Marketing',
                'position' => 'Marketing Specialist',
                'basic_salary' => 6000000,
            ],
        ];

        foreach ($employees as $emp) {
            User::create([
                'name' => $emp['name'],
                'email' => $emp['email'],
                'password' => Hash::make('password'),
                'employee_id' => $emp['employee_id'],
                'phone' => '+628' . rand(100000000, 999999999),
                'department' => $emp['department'],
                'position' => $emp['position'],
                'role' => 'employee',
                'join_date' => Carbon::now()->subMonths(rand(3, 12)),
                'basic_salary' => $emp['basic_salary'],
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        // Create holidays
        $holidays = [
            ['name' => 'New Year', 'date' => '2024-01-01', 'type' => 'national'],
            ['name' => 'Independence Day', 'date' => '2024-08-17', 'type' => 'national'],
            ['name' => 'Christmas', 'date' => '2024-12-25', 'type' => 'national'],
            ['name' => 'Eid al-Fitr', 'date' => '2024-04-10', 'type' => 'religious'],
            ['name' => 'Company Anniversary', 'date' => '2024-06-15', 'type' => 'custom'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create([
                'name' => $holiday['name'],
                'start_date' => $holiday['date'],
                'end_date' => $holiday['date'],
                'duration_days' => 1,
                'type' => $holiday['type'],
                'description' => $holiday['name'] . ' holiday',
                'is_recurring' => $holiday['type'] !== 'custom',
            ]);
        }

        // TODO: Fix WorkPeriod structure mismatch between migration and model
        // Skipping WorkPeriod creation for now to get basic functionality working

        // TODO: Fix Schedule structure mismatch
        // Skipping Schedule creation for now to get basic functionality working

        // Get users for creating sample data
        $users = User::where('role', 'employee')->get();

        // TODO: Fix Attendance structure mismatch
        // Skipping Attendance creation for now to get basic functionality working

        // Create sample leave requests
        Leave::create([
            'user_id' => $users->first()->id,
            'start_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'days' => 3,
            'type' => 'annual',
            'reason' => 'Annual vacation',
            'status' => 'pending',
        ]);

        Leave::create([
            'user_id' => $users->skip(1)->first()->id,
            'start_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
            'end_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'days' => 3,
            'type' => 'sick',
            'reason' => 'Flu symptoms',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => Carbon::now()->subDays(4),
        ]);

        // TODO: Fix Salary structure mismatch
        // Skipping Salary creation for now to get basic functionality working

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@absensi.com / password');
        $this->command->info('Employees: john@absensi.com, jane@absensi.com, bob@absensi.com, alice@absensi.com / password');
    }
}
