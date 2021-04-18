<?php

use Faker\Generator as Faker;

$factory->define(Insane\Payment\Category::class, function (Faker $faker) {
    return [
        'team_id' => 1,
        'user_id' => 1,
        'parent_id' => 1,
        'resource_type_id' => 1,
        'resource_type' => '',
        'name' => '',
        'description' => '',
        'index' => 0,
        'depth' => 0
    ];
});
