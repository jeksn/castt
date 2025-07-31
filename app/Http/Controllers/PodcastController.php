<?php

namespace App\Http\Controllers;

use App\Models\Podcast;
use App\Models\UserPodcast;
use App\Services\RssFeedService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PodcastController extends Controller
{
    public function __construct(
        private RssFeedService $rssFeedService
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        $podcasts = $user->podcasts()->orderBy('title')->get();
        
        return Inertia::render('Dashboard', [
            'podcasts' => $podcasts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'rss_url' => 'required|url',
        ]);

        try {
            $feedData = $this->rssFeedService->parseFeed($request->rss_url);
            
            // Check if podcast already exists
            $podcast = Podcast::where('rss_url', $request->rss_url)->first();
            
            if (!$podcast) {
                // Create new podcast if it doesn't exist
                $podcast = Podcast::create([
                    'title' => $feedData['title'],
                    'description' => $feedData['description'],
                    'rss_url' => $request->rss_url,
                    'image_url' => $feedData['image_url'],
                    'author' => $feedData['author'],
                    'website_url' => $feedData['website_url'],
                    'last_refreshed_at' => now(),
                ]);

                // Add episodes
                if (isset($feedData['episodes']) && is_array($feedData['episodes'])) {
                    foreach ($feedData['episodes'] as $episodeData) {
                        $podcast->episodes()->create($episodeData);
                    }
                }
            }
            
            // Subscribe user to podcast
            UserPodcast::firstOrCreate([
                'user_id' => Auth::id(),
                'podcast_id' => $podcast->id,
            ], [
                'subscribed_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Podcast added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['rss_url' => 'Failed to parse RSS feed: ' . $e->getMessage()]);
        }
    }

    public function refresh(Podcast $podcast): JsonResponse
    {
        // Check if user is subscribed to this podcast
        $userPodcast = UserPodcast::where('user_id', Auth::id())
                                 ->where('podcast_id', $podcast->id)
                                 ->first();
        
        if (!$userPodcast) {
            return response()->json([
                'error' => 'You are not subscribed to this podcast.',
            ], 403);
        }
        
        try {
            $newEpisodesCount = $this->rssFeedService->refreshPodcast($podcast);
            
            return response()->json([
                'message' => "Feed refreshed successfully! Added {$newEpisodesCount} new episodes.",
                'new_episodes_count' => $newEpisodesCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to refresh feed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Podcast $podcast): RedirectResponse
    {
        // Remove user's subscription to the podcast
        UserPodcast::where('user_id', Auth::id())
                   ->where('podcast_id', $podcast->id)
                   ->delete();
        
        // If no other users are subscribed, delete the podcast and its episodes
        if ($podcast->users()->count() === 0) {
            $podcast->delete();
        }
        
        return redirect()->back()->with('success', 'Podcast removed successfully!');
    }
}
