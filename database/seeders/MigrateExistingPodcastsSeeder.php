<?php

namespace Database\Seeders;

use App\Models\Podcast;
use App\Models\User;
use App\Models\UserPodcast;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MigrateExistingPodcastsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the test user (assuming it's the first user)
        $testUser = User::where('email', 'test@example.com')->first();
        
        if (!$testUser) {
            $this->command->error('Test user not found. Please run UserSeeder first.');
            return;
        }
        
        // Associate all existing podcasts with the test user
        $podcasts = Podcast::all();
        
        foreach ($podcasts as $podcast) {
            UserPodcast::firstOrCreate([
                'user_id' => $testUser->id,
                'podcast_id' => $podcast->id,
            ], [
                'subscribed_at' => now(),
            ]);
        }
        
        $this->command->info("Associated {$podcasts->count()} podcasts with the test user.");
    }
}
