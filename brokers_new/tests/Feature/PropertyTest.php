<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;

class PropertyTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testNewUserRegistration()
    {
        $user = factory(User::class)->create();
        
        $this->actingAs($user, 'web');

        $response = $this->json('POST', '/properties/store', ['title' => 'Hello World']);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'created' => true,
            ]);
    }
}
