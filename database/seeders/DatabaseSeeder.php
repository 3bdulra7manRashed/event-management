<?php

namespace Database\Seeders;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $users = User::factory(30)->create();
        $events = Event::factory(20)->recycle($users)->create();

        foreach ($events as $event) {
            // بنختار من 2 لـ 8 مستخدمين عشوائيين مختلفين لحضور الإيفنت ده
            $attendees = $users->random(rand(2, 8));

            foreach ($attendees as $user) {
                Attendee::factory()->create([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                ]);
            }
        }

    }
}
