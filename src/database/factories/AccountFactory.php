<?php

use Faker\Generator as Faker;

$factory->define(Insane\Payment\Account::class, function (Faker $faker) {
    return [
        'display_id' => $faker->word,
        'name' => $faker->word,
        'description' => $faker->word,
        'currency_code' => $faker->word,
        'index' => $faker->numberBetween(1,100),
        'archivable' => $faker->boolean(),
        'archived' => $faker->boolean(),
    ];
});
