<?php

namespace Instaboost\Tools;

/**
 * TikTokDownloader
 * 
 * A lightweight PHP class to extract video URLs from public TikTok posts
 * by parsing Open Graph meta tags. No API key required.
 * 
 * @author Instaboost Team
 * @license MIT
 * @version 1.0.0
 */
class TikTokDownloader
{
    /**
     * User-Agent string to mimic a real browser request
     */
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    
    /**
     * Timeout for cURL requests (seconds)
     */
    private const TIMEOUT = 15;
    
    /**
     * Download video metadata from a public TikTok URL
     * 
     * @param string $url TikTok video URL (e.g., https://www.tiktok.com/@user/video/1234567890)
     * @return array Array containing 'video_url', 'thumbnail', 'title', and 'author'
     * @throws \Exception on invalid URL, network errors, or video not found
     */
    public function download(string $url): array
    {
        // Validate TikTok URL format
        if (!$this->isValidTikTokUrl($url)) {
            throw new \Exception('Invalid TikTok URL. Please provide a valid video URL (e.g., https://www.tiktok.com/@user/video/1234567890)');
        }
        
        // Fetch HTML content
        $html = $this->fetchHtml($url);
        
        // Extract video metadata from HTML
        $video = $this->parseVideoFromHtml($html);
        
        if (empty($video)) {
            throw new \Exception('Could not extract video from this post. It may be private, deleted, or TikTok has updated their HTML structure.');
        }
        
        return $video;
    }
    
    /**
     * Validate if the URL is a proper TikTok video URL
     * 
     * @param string $url
     * @return bool
     */
    private function isValidTikTokUrl(string $url): bool
    {
        // Supports both full URLs and short vm.tiktok.com links
        $patterns = [
            '/^https?:\/\/(www\.|m\.)?tiktok\.com\/@[^\/]+\/video\/\d+/i',
            '/^https?:\/\/vm\.tiktok\.com\/[a-zA-Z0-9]+/i',
            '/^https?:\/\/(www\.)?tiktok\.com\/t\/[a-zA-Z0-9]+/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Fetch HTML content from TikTok URL using cURL
     * 
     * @param string $url
     * @return string HTML content
     * @throws \Exception on network errors or HTTP errors
     */
    private function fetchHtml(string $url): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_ENCODING => '', // Handle gzip/deflate automatically
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($html === false) {
            throw new \Exception("Network error: {$error}");
        }
        
        if ($httpCode === 404) {
            throw new \Exception('Video not found. The URL may be incorrect or the video has been deleted.');
        }
        
        if ($httpCode === 403 || $httpCode === 429) {
            throw new \Exception('Access denied or rate limited by TikTok. Please try again later or use a professional API service.');
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP error: {$httpCode}");
        }
        
        return $html;
    }
    
    /**
     * Parse video metadata from HTML using Open Graph meta tags
     * 
     * @param string $html
     * @return array
     */
    private function parseVideoFromHtml(string $html): array
    {
        $video = [];
        
        // Extract og:video (main video URL)
        if (preg_match('/<meta\s+property=["\']og:video["\']\s+content=["\'](.*?)["\']/i', $html, $videoMatch)) {
            $video['video_url'] = html_entity_decode($videoMatch[1]);
        }
        
        // Try alternative video URL pattern
        if (empty($video['video_url']) && preg_match('/<meta\s+property=["\']og:video:url["\']\s+content=["\'](.*?)["\']/i', $html, $videoAltMatch)) {
            $video['video_url'] = html_entity_decode($videoAltMatch[1]);
        }
        
        // Extract og:image (thumbnail)
        if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\'](.*?)["\']/i', $html, $thumbMatch)) {
            $video['thumbnail'] = html_entity_decode($thumbMatch[1]);
        }
        
        // Extract og:title (video title/description)
        if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\'](.*?)["\']/i', $html, $titleMatch)) {
            $video['title'] = html_entity_decode($titleMatch[1]);
        }
        
        // Extract author/username
        if (preg_match('/<meta\s+name=["\']author["\']\s+content=["\'](.*?)["\']/i', $html, $authorMatch)) {
            $video['author'] = html_entity_decode($authorMatch[1]);
        }
        
        return $video;
    }
    
    /**
     * Get video info without downloading (useful for previews)
     * 
     * @param string $url TikTok video URL
     * @return array Video information
     */
    public function getVideoInfo(string $url): array
    {
        return $this->download($url);
    }
}
