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

    public function test_can_show_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }

    public function test_returns_404_when_event_not_found(): void
    {
        $response = $this->getJson('/api/events/99999');

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_create_event(): void
    {
        $response = $this->postJson('/api/events', [
            'name' => 'Laravel Meetup',
            'start_time' => now()->addDays(2)->toDateTimeString(),
            'end_time' => now()->addDays(3)->toDateTimeString(),
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_event(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'PHP Cairo Meetup',
            'description' => 'A meetup for PHP and Laravel enthusiasts',
            'start_time' => now()->addDays(2)->toDateTimeString(),
            'end_time' => now()->addDays(3)->toDateTimeString(),
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/events', $payload);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'PHP Cairo Meetup',
                    'description' => 'A meetup for PHP and Laravel enthusiasts',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'name' => 'PHP Cairo Meetup',
            'user_id' => $user->id,
        ]);
    }

    public function test_event_creation_validates_dates(): void
    {
        $user = User::factory()->create();

        // 1. start_time in the past
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/events', [
            'name' => 'Old Event',
            'start_time' => now()->subDay()->toDateTimeString(),
            'end_time' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);

        // 2. end_time before start_time
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/events', [
            'name' => 'Wrong Dates Event',
            'start_time' => now()->addDays(5)->toDateTimeString(),
            'end_time' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }

    public function test_event_owner_can_update_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/events/{$event->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_non_owner_cannot_update_event(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->for($owner)->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')->putJson("/api/events/{$event->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_event_owner_can_delete_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/events/{$event->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_non_owner_cannot_delete_event(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser, 'sanctum')->deleteJson("/api/events/{$event->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }
}
