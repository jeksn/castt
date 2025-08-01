<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\UserEpisode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EpisodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $subscribedPodcastIds = $user->podcasts()->pluck('podcasts.id');
        
        $query = Episode::with(['podcast', 'userEpisodes' => function ($query) {
            $query->where('user_id', Auth::id());
        }])->whereIn('podcast_id', $subscribedPodcastIds);

        // Filter by podcast
        if ($request->filled('podcast_id')) {
            $query->where('podcast_id', $request->podcast_id);
        }

        // Search episodes
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Hide completed episodes
        if ($request->boolean('hide_completed')) {
            $query->whereDoesntHave('userEpisodes', function ($q) {
                $q->where('user_id', Auth::id())
                  ->where('is_completed', true);
            });
        }

        // Sort order
        $sortOrder = $request->get('sort_order', 'newest');
        if ($sortOrder === 'oldest') {
            $query->orderBy('published_at', 'asc');
        } else {
            $query->orderBy('published_at', 'desc');
        }

        $episodes = $query->paginate(20);

        // Add completion status to each episode
        $episodes->getCollection()->transform(function ($episode) {
            $episode->is_completed = $episode->userEpisodes->isNotEmpty() && $episode->userEpisodes->first()->is_completed;
            $episode->completed_at = $episode->userEpisodes->isNotEmpty() ? $episode->userEpisodes->first()->completed_at : null;
            unset($episode->userEpisodes); // Remove the relation to clean up the response
            return $episode;
        });

        return response()->json($episodes);
    }

    public function toggleCompleted(Episode $episode): JsonResponse
    {
        $userEpisode = UserEpisode::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'episode_id' => $episode->id,
            ],
            [
                'is_completed' => false,
            ]
        );

        $userEpisode->update([
            'is_completed' => !$userEpisode->is_completed,
            'completed_at' => !$userEpisode->is_completed ? now() : null,
        ]);

        return response()->json([
            'is_completed' => $userEpisode->is_completed,
            'completed_at' => $userEpisode->completed_at,
        ]);
    }

    public function markAllCompleted(Request $request): JsonResponse
    {
        $user = Auth::user();
        $subscribedPodcastIds = $user->podcasts()->pluck('podcasts.id');
        
        $query = Episode::whereIn('podcast_id', $subscribedPodcastIds);
        
        // Apply the same filters as the index method
        if ($request->filled('podcast_id')) {
            $query->where('podcast_id', $request->podcast_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Get all episode IDs that match the current filters
        $episodeIds = $query->pluck('id');
        
        $updatedCount = 0;
        
        foreach ($episodeIds as $episodeId) {
            $userEpisode = UserEpisode::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'episode_id' => $episodeId,
                ],
                [
                    'is_completed' => true,
                    'completed_at' => now(),
                ]
            );
            
            // If it wasn't already completed, mark it as completed
            if (!$userEpisode->is_completed) {
                $userEpisode->update([
                    'is_completed' => true,
                    'completed_at' => now(),
                ]);
                $updatedCount++;
            }
        }
        
        return response()->json([
            'message' => "Marked {$updatedCount} episodes as completed.",
            'updated_count' => $updatedCount,
        ]);
    }

    public function markAllIncomplete(Request $request): JsonResponse
    {
        $user = Auth::user();
        $subscribedPodcastIds = $user->podcasts()->pluck('podcasts.id');
        
        $query = Episode::whereIn('podcast_id', $subscribedPodcastIds);
        
        // Apply the same filters as the index method
        if ($request->filled('podcast_id')) {
            $query->where('podcast_id', $request->podcast_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Get all episode IDs that match the current filters
        $episodeIds = $query->pluck('id');
        
        // Update existing UserEpisode records to mark as incomplete
        $updatedCount = UserEpisode::where('user_id', Auth::id())
            ->whereIn('episode_id', $episodeIds)
            ->where('is_completed', true)
            ->update([
                'is_completed' => false,
                'completed_at' => null,
            ]);
        
        return response()->json([
            'message' => "Marked {$updatedCount} episodes as incomplete.",
            'updated_count' => $updatedCount,
        ]);
    }

    public function getCompletionStats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $podcastId = $request->route('podcast');
        
        // Verify user is subscribed to this podcast
        $subscribedPodcastIds = $user->podcasts()->pluck('podcasts.id');
        if (!$subscribedPodcastIds->contains($podcastId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Get total episodes for this podcast
        $totalEpisodes = Episode::where('podcast_id', $podcastId)->count();
        
        // Get completed episodes count for this user and podcast
        $completedEpisodes = UserEpisode::where('user_id', Auth::id())
            ->where('is_completed', true)
            ->whereHas('episode', function ($query) use ($podcastId) {
                $query->where('podcast_id', $podcastId);
            })
            ->count();
        
        return response()->json([
            'completed' => $completedEpisodes,
            'total' => $totalEpisodes,
        ]);
    }
}
