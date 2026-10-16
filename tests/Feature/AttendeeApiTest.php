<?php

namespace Tests\Feature;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_paginated_attendees_list(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        Attendee::factory(2)->for($event)->create();

        $response = $this->getJson("/api/events/{$event->id}/attendees");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }
}
