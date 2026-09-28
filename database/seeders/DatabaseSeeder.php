<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@campuscoin.edu'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'is_active' => true,
                'academic_year' => 'Staff',
                'monthly_allowance' => 0.00,
                'savings_goal' => 0.00,
                'university' => 'Campus Coin Central Admin',
                'program' => 'Platform Administration',
                'phone' => '+92 300 0000000',
                'dob' => '1995-01-01',
                'student_id' => 'ADMIN-001',
                'campus' => 'Main Campus',
            ]
        );

        // 2. Student User A (Hunzala Khan)
        $userA = User::firstOrCreate(
            ['email' => 'hunzala@campuscoin.edu'],
            [
                'name' => 'Hunzala Khan',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => true,
                'academic_year' => 'Year 3',
                'monthly_allowance' => 35000.00,
                'savings_goal' => 7500.00,
                'university' => 'National University of Sciences & Technology (NUST)',
                'program' => 'Computer Science',
                'phone' => '+92 300 1234567',
                'dob' => '2003-04-15',
                'student_id' => 'NUST-2021-CS-0847',
                'campus' => 'H-12, Islamabad',
            ]
        );

        // 3. Student User B (Ayesha Khan)
        $userB = User::firstOrCreate(
            ['email' => 'ayesha@campuscoin.edu'],
            [
                'name' => 'Ayesha Khan',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => true,
                'academic_year' => 'Year 2',
                'monthly_allowance' => 28000.00,
                'savings_goal' => 5000.00,
                'university' => 'NUST',
                'program' => 'Software Engineering',
                'phone' => '+92 301 9876543',
                'dob' => '2004-09-10',
                'student_id' => 'NUST-2022-SE-0192',
                'campus' => 'H-12, Islamabad',
            ]
        );

        // 4. Admin Tip Template / System Announcement
        \App\Models\AdminTipTemplate::firstOrCreate(
            ['title' => 'Welcome to Campus Coin 2026!'],
            [
                'message' => 'Track your daily expenses, set monthly category budgets, and leverage AI insights for smart financial planning.',
                'type' => 'announcement',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // 5. Seed Demonstration Transactions & Budgets for Hunzala if empty
        if (\App\Models\Transaction::where('user_id', $userA->id)->count() === 0) {
            $foodCat = \App\Models\Category::where('name', 'Food')->where('type', 'expense')->first();
            $transportCat = \App\Models\Category::where('name', 'Transport')->where('type', 'expense')->first();
            $academicsCat = \App\Models\Category::where('name', 'Academics')->where('type', 'expense')->first();
            $subsCat = \App\Models\Category::where('name', 'Subscriptions')->where('type', 'expense')->first();
            $allowanceCat = \App\Models\Category::where('name', 'Allowance')->where('type', 'income')->first();
            $partTimeCat = \App\Models\Category::where('name', 'Part-time Job')->where('type', 'income')->first();

            $currentMonth = date('Y-m');
            $now = \Carbon\Carbon::now();

            // Income
            \App\Models\Transaction::create([
                'user_id' => $userA->id,
                'category_id' => $allowanceCat ? $allowanceCat->id : null,
                'type' => 'income',
                'amount' => 35000.00,
                'description' => 'Monthly Student Allowance',
                'transaction_date' => $now->copy()->startOfMonth()->toDateString(),
            ]);

            \App\Models\Transaction::create([
                'user_id' => $userA->id,
                'category_id' => $partTimeCat ? $partTimeCat->id : null,
                'type' => 'income',
                'amount' => 12000.00,
                'description' => 'Freelance Web Tutoring',
                'transaction_date' => $now->copy()->startOfMonth()->addDays(5)->toDateString(),
            ]);

            // Expenses
            $t1 = \App\Models\Transaction::create([
                'user_id' => $userA->id,
                'category_id' => $foodCat ? $foodCat->id : null,
                'type' => 'expense',
                'amount' => 1200.00,
                'description' => 'Campus Dining Hall Weekly Pass',
                'transaction_date' => $now->copy()->subDays(2)->toDateString(),
            ]);

            \App\Models\Transaction::create([
                'user_id' => $userA->id,
                'category_id' => $transportCat ? $transportCat->id : null,
                'type' => 'expense',
                'amount' => 3500.00,
                'description' => 'Monthly Metro Student Card',
                'transaction_date' => $now->copy()->startOfMonth()->addDays(2)->toDateString(),
            ]);

            \App\Models\Transaction::create([
                'user_id' => $userA->id,
                'category_id' => $academicsCat ? $academicsCat->id : null,
                'type' => 'expense',
                'amount' => 4500.00,
                'description' => 'Algorithms & Data Structures Textbook',
                'transaction_date' => $now->copy()->startOfMonth()->addDays(7)->toDateString(),
            ]);

            \App\Models\Transaction::create([
                'user_id' => $userA->id,
                'category_id' => $subsCat ? $subsCat->id : null,
                'type' => 'expense',
                'amount' => 600.00,
                'description' => 'Spotify Student Plan',
                'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->toDateString(),
            ]);

            // Budgets for current month
            if ($foodCat) {
                \App\Models\Budget::create([
                    'user_id' => $userA->id,
                    'category_id' => $foodCat->id,
                    'limit_amount' => 12000.00,
                    'month' => $currentMonth,
                ]);
            }

            if ($transportCat) {
                \App\Models\Budget::create([
                    'user_id' => $userA->id,
                    'category_id' => $transportCat->id,
                    'limit_amount' => 5000.00,
                    'month' => $currentMonth,
                ]);
            }

            if ($academicsCat) {
                \App\Models\Budget::create([
                    'user_id' => $userA->id,
                    'category_id' => $academicsCat->id,
                    'limit_amount' => 8000.00,
                    'month' => $currentMonth,
                ]);
            }

            // Bookmark and note on t1
            if ($t1) {
                \App\Models\TransactionBookmark::create([
                    'user_id' => $userA->id,
                    'transaction_id' => $t1->id,
                ]);

                \App\Models\TransactionNote::create([
                    'user_id' => $userA->id,
                    'transaction_id' => $t1->id,
                    'note' => 'Weekly campus dining meal plan purchase.',
                ]);
            }
        }
    }
}
