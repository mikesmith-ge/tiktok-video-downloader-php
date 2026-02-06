<?php

require_once 'TikTokDownloader.php';

use Instaboost\Tools\TikTokDownloader;

$downloader = new TikTokDownloader();

try {
    // Download video metadata from a public TikTok post
    $video = $downloader->download('https://www.tiktok.com/@user/video/1234567890');
    
    echo "Video URL: " . $video['video_url'] . "\n";
    echo "Thumbnail: " . $video['thumbnail'] . "\n";
    echo "Title: " . $video['title'] . "\n";
    echo "Author: " . $video['author'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
