<?php

namespace Insane\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Insane\Payment\Account;
use Insane\Payment\Tests\TestCase;

class AccountTest extends TestCase
{
    use DatabaseMigrations;

    public function create_account($args = [], $num = null)
    {
        return factory(Account::class, $num)->create($args);
    }

    /** @test */
    public function api_can_give_all_account()
    {
        $this->create_account();
        $this->getJson(route('account.index'))->assertOk()->assertJsonStructure(['data']);
    }

    /** @test */
    public function api_can_give_single_account()
    {
        $account = $this->create_account();
        $this->getJson(route('account.show', $account->id))->assertJsonStructure(['data']);
    }

    /** @test */
    public function api_can_store_new_account()
    {
        $account = factory(Account::class)->make(['display_id'=>'Laravel']);
        $this->postJson(route('account.store'), $account->toArray())
        ->assertStatus(201);
        $this->assertDatabaseHas('accounts', ['display_id'=>'Laravel']);
    }

    /** @test */
    public function api_can_update_account()
    {
        $account = $this->create_account();
        $this->putJson(route('account.update', $account->id), ['display_id'=>'UpdatedValue'])
        ->assertStatus(202);
        $this->assertDatabaseHas('accounts', ['display_id'=>'UpdatedValue']);
    }

    /** @test */
    public function api_can_delete_account()
    {
        $account = $this->create_account();
        $this->deleteJson(route('account.destroy', $account->id))->assertStatus(204);
        $this->assertDatabaseMissing('accounts', ['display_id'=>$account->display_id]);
    }
}
