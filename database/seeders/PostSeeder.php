<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::all()->each(function (User $user) {
            for ($i = 7; $i > 0; $i--) {
                Post::factory()->create([
                    'user_id' => $user->id,
                    'published_at' => now()->subDays($i),
                ]);
            }
            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'This is Scheduled Post',
                'published_at' => now()->addDays(1),
            ]);
            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'This is Draft Post',
                'is_draft' => true,
                'published_at' => null,
            ]);
        });
    }
}
