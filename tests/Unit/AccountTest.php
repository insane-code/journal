<?php

namespace Insane\Payment\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Insane\Payment\Tests\TestCase;

class AccountTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
public function it_belongs_to_user()
{
    $user = factory(\Insane\Payment\User::class)->create();
    $Account  = factory(\Insane\Payment\Account::class)->create(['user_id' => $user->id]);
    $this->assertInstanceOf(\Insane\Payment\User::class, $Account->user);
}/** @test */
public function it_belongs_to_team()
{
    $team = factory(\Insane\Payment\team::class)->create();
    $Account  = factory(\Insane\Payment\Account::class)->create(['team_id' => $team->id]);
    $this->assertInstanceOf(\Insane\Payment\team::class, $Account->team);
}
}
