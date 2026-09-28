<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            // Income Categories
            [
                'name' => 'Allowance',
                'type' => 'income',
                'icon' => 'bi-cash-coin',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Part-time Job',
                'type' => 'income',
                'icon' => 'bi-briefcase',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Scholarship',
                'type' => 'income',
                'icon' => 'bi-mortarboard',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Gift',
                'type' => 'income',
                'icon' => 'bi-gift',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Other Income',
                'type' => 'income',
                'icon' => 'bi-wallet',
                'is_default' => true,
                'user_id' => null,
            ],

            // Expense Categories
            [
                'name' => 'Food',
                'type' => 'expense',
                'icon' => 'bi-cup-hot',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Transport',
                'type' => 'expense',
                'icon' => 'bi-bus-front',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Hostel/Rent',
                'type' => 'expense',
                'icon' => 'bi-house',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Academics',
                'type' => 'expense',
                'icon' => 'bi-book',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Subscriptions',
                'type' => 'expense',
                'icon' => 'bi-play-circle',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Entertainment',
                'type' => 'expense',
                'icon' => 'bi-controller',
                'is_default' => true,
                'user_id' => null,
            ],
            [
                'name' => 'Miscellaneous',
                'type' => 'expense',
                'icon' => 'bi-tags',
                'is_default' => true,
                'user_id' => null,
            ],
        ];

        foreach ($defaultCategories as $category) {
            Category::updateOrCreate(
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'is_default' => true,
                    'user_id' => null,
                ],
                [
                    'icon' => $category['icon'],
                ]
            );
        }
    }
}
