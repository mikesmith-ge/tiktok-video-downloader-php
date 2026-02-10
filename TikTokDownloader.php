<?php

namespace Instaboost\Tools;

/**
 * TikTokDownloader
 *
 * Lightweight PHP class to extract video URLs and metadata from public TikTok posts.
 * Uses multi-pattern JSON extraction with Open Graph meta tags as fallback.
 *
 * @author  Instaboost Team
 * @license MIT
 * @version 1.1.0
 */
class TikTokDownloader
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    private const TIMEOUT    = 15;

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Download video metadata from a public TikTok URL.
     *
     * @param  string $url TikTok video URL
     * @return array{video_url: string, thumbnail: string, title: string, author: string, source: string}
     * @throws \InvalidArgumentException on invalid URL
     * @throws \RuntimeException         on network errors or extraction failure
     */
    public function download(string $url): array
    {
        if (!$this->isValidTikTokUrl($url)) {
            throw new \InvalidArgumentException(
                'Invalid TikTok URL. Supported formats: '
                . 'tiktok.com/@user/video/ID, vm.tiktok.com/CODE, tiktok.com/t/CODE'
            );
        }

        $html = $this->fetchHtml($url);

        // Stage 1: JSON blob (multiple patterns)
        $video = $this->parseJson($html);

        // Stage 2: og: meta tags fallback
        if (empty($video)) {
            $video = $this->parseOgMeta($html);
        }

        if (empty($video)) {
            throw new \RuntimeException(
                'Could not extract video from this post. '
                . 'It may be private, deleted, or TikTok has updated their response structure. '
                . 'For reliable production access visit https://instaboost.ge'
            );
        }

        return $video;
    }

    /**
     * Alias for download() — useful for preview workflows.
     */
    public function getVideoInfo(string $url): array
    {
        return $this->download($url);
    }

    // -----------------------------------------------------------------------
    // URL validation
    // -----------------------------------------------------------------------

    private function isValidTikTokUrl(string $url): bool
    {
        $patterns = [
            '/^https?:\/\/(www\.|m\.)?tiktok\.com\/@[^\/]+\/video\/\d+/i',
            '/^https?:\/\/vm\.tiktok\.com\/[a-zA-Z0-9]+/i',
            '/^https?:\/\/(www\.)?tiktok\.com\/t\/[a-zA-Z0-9]+/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    // -----------------------------------------------------------------------
    // HTTP fetching
    // -----------------------------------------------------------------------

    private function fetchHtml(string $url): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $html     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        curl_close($ch);

        if ($html === false) {
            throw new \RuntimeException("Network error: {$error}");
        }

        $this->assertHttpSuccess($httpCode);

        return $html;
    }

    /**
     * Throw descriptive exceptions for non-200 HTTP responses.
     * Resolves #2 — previously these codes caused silent failures.
     */
    private function assertHttpSuccess(int $code): void
    {
        switch ($code) {
            case 200:
                return;

            case 403:
                throw new \RuntimeException(
                    'TikTok blocked the request (HTTP 403). '
                    . 'Your IP may be rate-limited or geo-blocked. '
                    . 'Suggestions: wait a few minutes, use a proxy, or switch to a different IP. '
                    . 'For unlimited access see https://instaboost.ge'
                );

            case 404:
                throw new \RuntimeException(
                    'Video not found (HTTP 404). '
                    . 'The URL may be incorrect or the video has been deleted.'
                );

            case 429:
                throw new \RuntimeException(
                    'Rate limited by TikTok (HTTP 429). '
                    . 'Too many requests from this IP. '
                    . 'Wait before retrying, or use proxy rotation. '
                    . 'For production use without rate limits see https://instaboost.ge'
                );

            default:
                throw new \RuntimeException(
                    "Unexpected HTTP response: {$code}. "
                    . 'TikTok may be temporarily unavailable or blocking this request.'
                );
        }
    }

    // -----------------------------------------------------------------------
    // Stage 1: JSON extraction (multiple patterns)
    // -----------------------------------------------------------------------

    /**
     * Try to extract video data from TikTok's embedded JSON.
     *
     * TikTok embeds video data in several script tag formats.
     * We try multiple patterns to handle different page versions.
     * Resolves #1 — supports both old video.playAddr and new response structures.
     */
    private function parseJson(string $html): ?array
    {
        // Pattern A: __UNIVERSAL_DATA_FOR_REHYDRATION__ (current TikTok format)
        $video = $this->tryUniversalData($html);
        if ($video) {
            return $video;
        }

        // Pattern B: SIGI_STATE (alternative current format)
        $video = $this->trySigiState($html);
        if ($video) {
            return $video;
        }

        // Pattern C: __NEXT_DATA__ (older Next.js format)
        $video = $this->tryNextData($html);
        if ($video) {
            return $video;
        }

        return null;
    }

    /**
     * Pattern A: __UNIVERSAL_DATA_FOR_REHYDRATION__
     * Current primary TikTok data format.
     */
    private function tryUniversalData(string $html): ?array
    {
        if (!preg_match('/<script[^>]*>\s*window\.__UNIVERSAL_DATA_FOR_REHYDRATION__\s*=\s*(\{.*?\})\s*<\/script>/s', $html, $m)) {
            return null;
        }

        $data = json_decode($m[1], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Navigate to video detail
        $itemModule = $data['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct'] ?? null;
        if (!$itemModule) {
            return null;
        }

        return $this->videoFromItemStruct($itemModule, 'json:universal');
    }

    /**
     * Pattern B: SIGI_STATE
     * Alternative TikTok data format used on some regions/versions.
     */
    private function trySigiState(string $html): ?array
    {
        if (!preg_match('/<script[^>]*>\s*window\[\'SIGI_STATE\'\]\s*=\s*(\{.*?\})\s*<\/script>/s', $html, $m)) {
            return null;
        }

        $data = json_decode($m[1], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $items = $data['ItemModule'] ?? [];
        $item  = !empty($items) ? reset($items) : null;

        if (!$item) {
            return null;
        }

        return $this->videoFromItemStruct($item, 'json:sigi');
    }

    /**
     * Pattern C: __NEXT_DATA__
     * Legacy Next.js format, still encountered on some TikTok pages.
     */
    private function tryNextData(string $html): ?array
    {
        if (!preg_match('/<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/s', $html, $m)) {
            return null;
        }

        $data = json_decode($m[1], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $item = $data['props']['pageProps']['itemInfo']['itemStruct'] ?? null;
        if (!$item) {
            return null;
        }

        return $this->videoFromItemStruct($item, 'json:next');
    }

    /**
     * Extract video data from a TikTok itemStruct node.
     *
     * Tries multiple video URL fields to handle both old (playAddr)
     * and new (downloadAddr, bitrateInfo) response structures.
     */
    private function videoFromItemStruct(array $item, string $source): ?array
    {
        $videoData = $item['video'] ?? [];

        // Try multiple video URL fields — TikTok changed from playAddr to other fields
        $videoUrl = $videoData['playAddr']     // legacy field
            ?? $videoData['downloadAddr']       // new primary field
            ?? $videoData['playAddrH264']       // H264 variant
            ?? $videoData['bitrateInfo'][0]['PlayAddr']['UrlList'][0]  // bitrate info
            ?? '';

        if (!$videoUrl) {
            return null;
        }

        $author = $item['author']['uniqueId'] ?? $item['author']['nickname'] ?? '';

        return [
            'video_url' => $videoUrl,
            'thumbnail' => $videoData['cover'] ?? $videoData['dynamicCover'] ?? '',
            'title'     => $item['desc'] ?? '',
            'author'    => $author ? "@{$author}" : '',
            'source'    => $source,
        ];
    }

    // -----------------------------------------------------------------------
    // Stage 2: og: meta tag extraction (fallback)
    // -----------------------------------------------------------------------

    private function parseOgMeta(string $html): ?array
    {
        $video = [];

        // og:video
        if (preg_match('/<meta\s+property=["\']og:video["\']\s+content=["\'](.*?)["\']/i', $html, $m)) {
            $video['video_url'] = html_entity_decode($m[1]);
        }

        // og:video:url fallback
        if (empty($video['video_url']) && preg_match('/<meta\s+property=["\']og:video:url["\']\s+content=["\'](.*?)["\']/i', $html, $m)) {
            $video['video_url'] = html_entity_decode($m[1]);
        }

        if (empty($video['video_url'])) {
            return null;
        }

        // og:image (thumbnail)
        if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\'](.*?)["\']/i', $html, $m)) {
            $video['thumbnail'] = html_entity_decode($m[1]);
        }

        // og:title
        if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\'](.*?)["\']/i', $html, $m)) {
            $video['title'] = html_entity_decode($m[1]);
        }

        // author
        if (preg_match('/<meta\s+name=["\']author["\']\s+content=["\'](.*?)["\']/i', $html, $m)) {
            $video['author'] = html_entity_decode($m[1]);
        }

        $video['source'] = 'og_meta';

        return $video;
    }
}
