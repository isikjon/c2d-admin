<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Ипотечное страхование',
                'slug' => 'mortgage-insurance',
                'description' => 'Страхование недвижимости при ипотеке',
                'required_fields' => json_encode([
                    'property_value', 'city', 'loan_amount', 
                    'payment_term', 'coborrowers', 'full_name'
                ]),
            ],
            [
                'name' => 'КАСКО',
                'slug' => 'kasko',
                'description' => 'Комплексное автострахование',
                'required_fields' => json_encode([
                    'brand', 'model', 'year', 'power', 
                    'driver_age', 'experience', 'insured_persons'
                ]),
            ],
            [
                'name' => 'ОСАГО',
                'slug' => 'osago',
                'description' => 'Обязательное страхование автогражданской ответственности',
                'required_fields' => json_encode([
                    'brand', 'model', 'year', 'power'
                ]),
            ],
            [
                'name' => 'Имущественное страхование',
                'slug' => 'property-insurance',
                'description' => 'Страхование недвижимости',
                'required_fields' => json_encode([
                    'city', 'address', 'area', 'year_built', 'risks'
                ]),
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
