<?php

namespace App\Training\Service;

use App\Training\Entity\TrainingVideo;
use App\Training\Repository\TrainingVideoRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class TrainingVideoService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TrainingVideoRepository $videoRepository
    ) {
    }

    public function listVideos(?string $search = null, ?string $category = null, ?string $difficulty = null): array
    {
        $hasFilters = ($search !== null && trim($search) !== '')
            || ($category !== null && $category !== '')
            || ($difficulty !== null && $difficulty !== '');

        $videos = $hasFilters
            ? $this->videoRepository->search($search, $category, $difficulty)
            : $this->videoRepository->findAllOrdered();

        $all = $hasFilters ? $this->videoRepository->findAllOrdered() : $videos;

        $difficultyOrder = ['BEGINNER' => 0, 'INTERMEDIATE' => 1, 'ADVANCED' => 2, 'ALL_LEVELS' => 3];
        $difficulties = $this->distinctValues($all, fn (TrainingVideo $v) => $v->getDifficulty());
        usort($difficulties, fn (string $a, string $b) => ($difficultyOrder[$a] ?? 99) <=> ($difficultyOrder[$b] ?? 99));

        return [
            'total' => count($videos),
            'categories' => $this->distinctValues($all, fn (TrainingVideo $v) => $v->getCategory()),
            'difficulties' => $difficulties,
            'videos' => array_map(fn (TrainingVideo $v) => $this->serialize($v), $videos),
        ];
    }

    public function getVideo(int $id): array
    {
        $video = $this->videoRepository->find($id);
        if (!$video) {
            throw new LogicException('Tutorial video not found.');
        }

        $video->incrementViews();
        $this->entityManager->flush();

        return $this->serialize($video);
    }

    public function serialize(TrainingVideo $video): array
    {
        $youtubeId = $this->extractYouTubeId($video->getVideoUrl());

        return [
            'id' => $video->getId(),
            'title' => $video->getTitle(),
            'description' => $video->getDescription(),
            'category' => $video->getCategory(),
            'targetMuscle' => $video->getTargetMuscle(),
            'difficulty' => $video->getDifficulty(),
            'durationMinutes' => $video->getDurationMinutes(),
            'youtubeId' => $youtubeId,
            'embedUrl' => $youtubeId !== null ? $this->buildEmbedUrl($youtubeId) : null,
            'youtubeWatchUrl' => $video->getVideoUrl(),
            'thumbnailUrl' => $youtubeId !== null
                ? $this->buildThumbnailUrl($youtubeId)
                : $video->getThumbnailUrl(),
            'instructorName' => $video->getInstructorName(),
            'viewsCount' => $video->getViewsCount(),
            'createdAt' => $video->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Extracts the 11-character video ID from watch, share, shorts or embed URLs.
     */
    public function extractYouTubeId(string $url): ?string
    {
        $pattern = '~^(?:https?://)?(?:www\.|m\.)?'
            . '(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)'
            . '([A-Za-z0-9_-]{11})(?:[?&#/]|$)~i';

        return preg_match($pattern, trim($url), $matches) === 1 ? $matches[1] : null;
    }

    public function buildEmbedUrl(string $youtubeId): string
    {
        return sprintf('https://www.youtube-nocookie.com/embed/%s?rel=0&modestbranding=1', $youtubeId);
    }

    public function buildThumbnailUrl(string $youtubeId): string
    {
        return sprintf('https://i.ytimg.com/vi/%s/hqdefault.jpg', $youtubeId);
    }

    /**
     * @param TrainingVideo[] $videos
     * @param callable(TrainingVideo): string $accessor
     * @return string[]
     */
    private function distinctValues(array $videos, callable $accessor): array
    {
        $values = array_map($accessor, $videos);

        return array_values(array_unique($values));
    }
}
