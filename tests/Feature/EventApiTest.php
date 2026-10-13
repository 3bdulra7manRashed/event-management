<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_paginated_events_list(): void
    {
        $user = User::factory()->create();
        Event::factory(3)->for($user)->create();

        $response = $this->getJson('/api/events');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'start_time',
                        'end_time',
                        'user' => [
                            'id',
                            'name',
                            'email',
                        ],
                    ],
                ],
                'links',
                'meta',
            ]);
    }
}
