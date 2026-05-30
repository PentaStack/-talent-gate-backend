<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            // Egypt
            'Cairo', 'Alexandria', 'Giza', 'Luxor', 'Aswan', 'Mansoura',
            'Tanta', 'Zagazig', 'Ismailia', 'Suez', 'Port Said',
            // Remote / hybrid
            'Remote', 'Hybrid',
            // Gulf
            'Dubai', 'Abu Dhabi', 'Riyadh', 'Jeddah', 'Doha', 'Kuwait City',
            // Global tech hubs
            'London', 'Berlin', 'Amsterdam', 'Paris', 'Toronto',
            'New York', 'San Francisco', 'Austin',
        ];

        foreach ($cities as $city) {
            Location::firstOrCreate(
                ['slug' => Str::slug($city)],
                ['name' => $city],
            );
        }
    }
}
