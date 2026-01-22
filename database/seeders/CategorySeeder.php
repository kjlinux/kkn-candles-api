<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Bougies Parfumées',
                'slug' => 'bougies-parfumees',
                'description' => 'Nos bougies parfumées artisanales aux senteurs uniques',
                'sort_order' => 1,
            ],
            [
                'name' => 'Bougies Décoratives',
                'slug' => 'bougies-decoratives',
                'description' => 'Bougies décoratives pour embellir votre intérieur',
                'sort_order' => 2,
            ],
            [
                'name' => 'Éditions Limitées',
                'slug' => 'editions-limitees',
                'description' => 'Collections exclusives en édition limitée',
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
