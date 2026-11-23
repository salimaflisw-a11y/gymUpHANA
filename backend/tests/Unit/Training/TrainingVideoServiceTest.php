<?php

namespace App\Tests\Unit\Training;

use App\Training\Entity\TrainingVideo;
use App\Training\Repository\TrainingVideoRepository;
use App\Training\Service\TrainingVideoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TrainingVideoServiceTest extends TestCase
{
    private TrainingVideoService $service;

    protected function setUp(): void
    {
        $this->service = new TrainingVideoService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TrainingVideoRepository::class)
        );
    }

    public function testExtractsIdFromWatchUrl(): void
    {
        $this->assertSame('vcBig73ojpE', $this->service->extractYouTubeId('https://www.youtube.com/watch?v=vcBig73ojpE'));
    }

    public function testExtractsIdFromWatchUrlWithExtraParams(): void
    {
        $this->assertSame('op9kVnSso6Q', $this->service->extractYouTubeId('https://www.youtube.com/watch?feature=share&v=op9kVnSso6Q&t=42s'));
    }

    public function testExtractsIdFromShareUrl(): void
    {
        $this->assertSame('CAwf7n6Luuc', $this->service->extractYouTubeId('https://youtu.be/CAwf7n6Luuc?si=abc123'));
    }

    public function testExtractsIdFromShortsAndEmbedUrls(): void
    {
        $this->assertSame('3VcKaXpzqRo', $this->service->extractYouTubeId('https://www.youtube.com/shorts/3VcKaXpzqRo'));
        $this->assertSame('3VcKaXpzqRo', $this->service->extractYouTubeId('https://www.youtube-nocookie.com/embed/3VcKaXpzqRo'));
    }

    public function testReturnsNullForNonYouTubeUrl(): void
    {
        $this->assertNull($this->service->extractYouTubeId('https://vimeo.com/123456789'));
        $this->assertNull($this->service->extractYouTubeId('not a url'));
    }

    public function testBuildsPrivacyFriendlyEmbedAndThumbnailUrls(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/vcBig73ojpE?rel=0&modestbranding=1',
            $this->service->buildEmbedUrl('vcBig73ojpE')
        );
        $this->assertSame(
            'https://i.ytimg.com/vi/vcBig73ojpE/hqdefault.jpg',
            $this->service->buildThumbnailUrl('vcBig73ojpE')
        );
    }

    public function testSerializeIncludesDerivedYouTubeData(): void
    {
        $video = new TrainingVideo(
            title: 'Bench Press Basics',
            category: 'Technique',
            videoUrl: 'https://www.youtube.com/watch?v=vcBig73ojpE',
            targetMuscle: 'Chest',
            difficulty: 'BEGINNER',
            durationMinutes: 12,
            instructorName: 'Jeff Nippard'
        );
        $video->incrementViews();

        $data = $this->service->serialize($video);

        $this->assertSame('Bench Press Basics', $data['title']);
        $this->assertSame('vcBig73ojpE', $data['youtubeId']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/vcBig73ojpE?rel=0&modestbranding=1', $data['embedUrl']);
        $this->assertSame('https://i.ytimg.com/vi/vcBig73ojpE/hqdefault.jpg', $data['thumbnailUrl']);
        $this->assertSame(1, $data['viewsCount']);
    }

    public function testSerializeFallsBackToStoredThumbnailForNonYouTubeLink(): void
    {
        $video = new TrainingVideo(
            title: 'Internal Clip',
            category: 'Equipment',
            videoUrl: 'https://cdn.example.com/clip.mp4',
            thumbnailUrl: 'https://cdn.example.com/clip.jpg'
        );

        $data = $this->service->serialize($video);

        $this->assertNull($data['youtubeId']);
        $this->assertNull($data['embedUrl']);
        $this->assertSame('https://cdn.example.com/clip.jpg', $data['thumbnailUrl']);
    }
}
