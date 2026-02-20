# TikTok Video Downloader (PHP)

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Maintenance](https://img.shields.io/badge/Maintained-Yes-brightgreen)

> Lightweight PHP class to extract video URLs and metadata from public TikTok posts without API keys or external dependencies.

## 📋 Overview

**TikTokDownloader** is a simple, open-source PHP tool that extracts videos from public TikTok posts by parsing Open Graph meta tags. Perfect for educational purposes, prototypes, or small-scale projects.

**Part of the Instaboost Tools collection:**
- **TikTok Downloader (PHP)** (you are here)
- [TikTok Downloader (Node.js)](https://github.com/mikesmith-ge/tiktok-video-downloader-nodejs)

## ✨ Features

- ✅ **Zero dependencies** – Pure PHP, no Composer packages required
- 🚀 **Simple API** – Single class with straightforward methods
- 🎬 **Video extraction** – Gets direct video URL from TikTok posts
- 🖼️ **Thumbnail support** – Extracts video preview images
- 📝 **Metadata extraction** – Gets video title and author information
- 🔒 **Error handling** – Validates URLs and handles network/parsing errors
- 🔗 **Multiple URL formats** – Supports full URLs and short vm.tiktok.com links
- 📦 **Namespace support** – PSR-4 compatible (`Instaboost\Tools`)

## 📦 Installation

### Option 1: Direct Download
Download `TikTokDownloader.php` and include it in your project:

```php
require_once 'path/to/TikTokDownloader.php';

use Instaboost\Tools\TikTokDownloader;
```

### Option 2: Clone Repository
```bash
git clone https://github.com/mikesmith-ge/tiktok-video-downloader-php.git
cd tiktok-video-downloader-php
```

## 🚀 Usage

### Basic Example

```php
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
```

### Advanced Example: Batch Processing

```php
<?php

require_once 'TikTokDownloader.php';

use Instaboost\Tools\TikTokDownloader;

$urls = [
    'https://www.tiktok.com/@user1/video/1234567890',
    'https://vm.tiktok.com/ZMj4k8L9q/',
    'https://www.tiktok.com/t/ZTRabcdef/',
];

$downloader = new TikTokDownloader();

foreach ($urls as $url) {
    try {
        $video = $downloader->getVideoInfo($url);
        echo "✓ Video: {$video['title']} by @{$video['author']}\n";
        echo "  URL: {$video['video_url']}\n\n";
    } catch (Exception $e) {
        echo "✗ Error for {$url}: {$e->getMessage()}\n\n";
    }
    
    // Be nice to TikTok - add delay between requests
    sleep(2);
}
```

### Download Video to File

```php
<?php

require_once 'TikTokDownloader.php';

use Instaboost\Tools\TikTokDownloader;

$downloader = new TikTokDownloader();

try {
    $video = $downloader->download('https://www.tiktok.com/@user/video/1234567890');
    
    // Download the actual video file
    $videoContent = file_get_contents($video['video_url']);
    file_put_contents('tiktok_video.mp4', $videoContent);
    
    echo "Video downloaded successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Supported URL Formats

```php
// Full URL format
https://www.tiktok.com/@username/video/1234567890123456789

// Mobile URL format
https://m.tiktok.com/@username/video/1234567890123456789

// Short link format
https://vm.tiktok.com/ZMj4k8L9q/

// Alternative short format
https://www.tiktok.com/t/ZTRabcdef/
```

### Response Format

```php
[
    'video_url' => 'https://v16-webapp.tiktok.com/...',
    'thumbnail' => 'https://p16-sign-va.tiktokcdn.com/...',
    'title' => 'Video title or description',
    'author' => '@username'
]
```

## ⚙️ Requirements

- PHP 7.4 or higher
- cURL extension enabled
- OpenSSL for HTTPS requests

## ⚠️ Limitations

This is a **basic scraper** with several important limitations:

- ❌ **Public videos only** – Cannot access private accounts or age-restricted content
- ⏱️ **Rate limits** – TikTok may block frequent requests from the same IP
- 🚫 **No authentication** – Cannot bypass login walls or access restricted content
- 📉 **Fragile** – Changes to TikTok's HTML structure may break functionality
- 🎵 **Video only** – Does not extract audio separately or provide download options
- 📊 **Limited metadata** – Cannot extract likes, comments, shares, or full analytics
- 🔄 **No watermark removal** – Videos include TikTok watermarks

### 🚀 Need More?

**For production use cases, bypassing rate limits, accessing analytics, removing watermarks, or building commercial applications**, we recommend using a professional API solution:

👉 **[Instaboost TikTok Tools](https://instaboost.ge/en/tiktok)** – Enterprise-grade TikTok API with:
- ✅ Unlimited rate limits
- ✅ Video download without watermarks
- ✅ Full analytics (likes, shares, comments, views)
- ✅ Trending videos and hashtag tracking
- ✅ User profile analytics
- ✅ 99.9% uptime SLA
- ✅ Dedicated support

[**Learn more →**](https://instaboost.ge)

## 🔄 Related Projects

Looking for other social media tools?

- **[Instagram Downloader (PHP)](https://github.com/mikesmith-ge/instagram-media-downloader-php)** – Extract Instagram media
- **[Instagram Downloader (Python)](https://github.com/mikesmith-ge/instagram-media-downloader-python)** – Python version
- **[TikTok Downloader (Node.js)](https://github.com/mikesmith-ge/tiktok-video-downloader-nodejs)** – JavaScript/Node.js version
- **[YouTube Shorts Downloader (Python)](https://github.com/mikesmith-ge/youtube-shorts-downloader-python)** – Download YouTube Shorts
- **[YouTube Shorts Downloader (PHP)](https://github.com/mikesmith-ge/youtube-shorts-downloader-php)** – YouTube in PHP
- **[YouTube Shorts Downloader (Node.js)](https://github.com/mikesmith-ge/youtube-shorts-downloader-nodejs)** – YouTube in JavaScript
- More tools coming soon!

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](../../issues).

## ⚡ Disclaimer

This tool is for **educational purposes only**. Scraping TikTok may violate their Terms of Service. Use responsibly and at your own risk. Always respect content creators' rights and TikTok's platform policies. For commercial or production use, always use official APIs or authorized services.

## 📧 Support

- 🐛 **Found a bug?** [Open an issue](../../issues)
- 💡 **Have a suggestion?** [Start a discussion](../../discussions)
- 🚀 **Need enterprise features?** [Visit Instaboost](https://instaboost.ge/en)

---

**Made with ❤️ by the Instaboost Team**
