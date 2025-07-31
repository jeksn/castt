<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Podcast;
use Carbon\Carbon;
use SimplePie\SimplePie;

class RssFeedService
{
    public function parseFeed(string $rssUrl): array
    {
        $feed = new SimplePie();
        $feed->set_feed_url($rssUrl);
        $feed->set_cache_location(storage_path('app/cache'));
        $feed->set_cache_duration(3600); // 1 hour
        $feed->init();
        
        if ($feed->error()) {
            throw new \Exception('Error parsing RSS feed: ' . $feed->error());
        }

        return [
            'title' => $feed->get_title(),
            'description' => $feed->get_description(),
            'image_url' => $feed->get_image_url(),
            'author' => $feed->get_author()->name ?? null,
            'website_url' => $feed->get_link(),
            'episodes' => $this->parseEpisodes($feed),
        ];
    }

    public function refreshPodcast(Podcast $podcast): int
    {
        $feedData = $this->parseFeed($podcast->rss_url);
        
        // Update podcast metadata
        $podcast->update([
            'title' => $feedData['title'] ?? $podcast->title,
            'description' => $feedData['description'] ?? $podcast->description,
            'image_url' => $feedData['image_url'] ?? $podcast->image_url,
            'author' => $feedData['author'] ?? $podcast->author,
            'website_url' => $feedData['website_url'] ?? $podcast->website_url,
            'last_refreshed_at' => now(),
        ]);

        $newEpisodesCount = 0;

        if (isset($feedData['episodes']) && is_array($feedData['episodes'])) {
            foreach ($feedData['episodes'] as $episodeData) {
                $episode = Episode::where('guid', $episodeData['guid'])->first();
                
                if (!$episode) {
                    Episode::create([
                        'podcast_id' => $podcast->id,
                        'title' => $episodeData['title'],
                        'description' => $episodeData['description'],
                        'audio_url' => $episodeData['audio_url'],
                        'guid' => $episodeData['guid'],
                        'thumbnail_url' => $episodeData['thumbnail_url'],
                        'duration_seconds' => $episodeData['duration_seconds'],
                        'duration_formatted' => $episodeData['duration_formatted'],
                        'published_at' => $episodeData['published_at'],
                    ]);
                    $newEpisodesCount++;
                }
            }
        }

        return $newEpisodesCount;
    }

private function parseEpisodes(SimplePie $feed): array
    {
        $episodes = [];
        $items = $feed->get_items();

        if (is_null($items)) {
            return $episodes; // Return empty array if no items
        }
        
        foreach ($items as $item) {
            $enclosure = $item->get_enclosure();
            $audioUrl = $enclosure ? $enclosure->get_link() : null;
            
            if (!$audioUrl) {
                continue; // Skip items without audio
            }

            $duration = $this->parseDuration($item);
            
            $episodes[] = [
                'title' => $item->get_title(),
                'description' => strip_tags($item->get_description()),
                'audio_url' => $audioUrl,
                'guid' => $item->get_id(),
                'thumbnail_url' => $this->getEpisodeThumbnail($item),
                'duration_seconds' => $duration['seconds'],
                'duration_formatted' => $duration['formatted'],
                'published_at' => Carbon::parse($item->get_date()),
            ];
        }

        return $episodes;
    }

    private function parseDuration($item): array
    {
        $duration = null;
        $durationFormatted = null;
        
        // Try to get duration from iTunes tags
        $durationTags = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'duration');
        if ($durationTags) {
            foreach ($durationTags as $tag) {
                $durationString = $tag['data'];
                
                // Parse different duration formats
                if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $durationString, $matches)) {
                    // HH:MM:SS format
                    $duration = ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3];
                    $durationFormatted = $durationString;
                } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $durationString, $matches)) {
                    // MM:SS format
                    $duration = ($matches[1] * 60) + $matches[2];
                    $durationFormatted = $durationString;
                } elseif (is_numeric($durationString)) {
                    // Seconds only
                    $duration = (int) $durationString;
                    $durationFormatted = $this->formatDuration($duration);
                }
                break;
            }
        }

        return [
            'seconds' => $duration,
            'formatted' => $durationFormatted,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
    }

    private function getEpisodeThumbnail($item): ?string
    {
        // Try to get thumbnail from various sources
        $imageTags = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'image');
        if ($imageTags) {
            foreach ($imageTags as $tag) {
                if (isset($tag['attribs']['']['href'])) {
                    return $tag['attribs']['']['href'];
                }
            }
        }

        // Fallback to podcast image
        return null;
    }
}
