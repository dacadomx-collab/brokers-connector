<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Property;
use Faker\Generator as Faker;

$factory->define(Property::class, function (Faker $faker) {
    return [
        'created_by' => $faker->numberBetween($min = 1, $max = 4),
        'agent_id' => $faker->numberBetween($min = 1, $max = 4),
        'company_id' => 1,
        'prop_status_id' => $faker->numberBetween($min = 1, $max = 5),
        'prop_type_id' => $faker->numberBetween($min = 1, $max = 6),
        'title' => $faker->catchPhrase,
        'description' => $faker->realText($maxNbChars = 200, $indexSize = 2),
        'bedrooms' => $faker->numberBetween($min = 1, $max = 6),
        'baths' => $faker->numberBetween($min = 1, $max = 3),
        'floor' => $faker->numberBetween($min = 1, $max = 15),
        'medium_baths' => $faker->numberBetween($min = 1, $max = 3),
        'parking_lots' => $faker->numberBetween($min = 1, $max = 5),
        'total_area' => 1000,
        'built_area' => 500,
        'price' => $faker->numberBetween($min = 5000, $max = 300000),
        'currency' => $faker->numberBetween($min = 1, $max = 2),
        'lat' => $faker->latitude($min = -90, $max = 90),
        'lng' => $faker->longitude($min = -180, $max = 180),
        'local_id' =>  79,
        'commission' => $faker->numberBetween($min = 5000, $max = 300000),
        'type_commission' => $faker->numberBetween($min = 1, $max = 2),
        'length' => 2500.2,
        'front' => 1035.56,
        // 'copmany_id' => 1,
        'antiquity' => 1994,
        'key' => $faker->uuid,
        'published' => $faker->numberBetween($min = 0, $max = 1),
        'video' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    ];
});
