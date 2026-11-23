export interface TutorialVideo {
  id: number;
  title: string;
  description: string | null;
  category: string;
  targetMuscle: string | null;
  difficulty: string;
  durationMinutes: number;
  youtubeId: string | null;
  embedUrl: string | null;
  youtubeWatchUrl: string;
  thumbnailUrl: string | null;
  instructorName: string | null;
  viewsCount: number;
  createdAt: string;
}

export interface TutorialVideoListResponse {
  total: number;
  categories: string[];
  difficulties: string[];
  videos: TutorialVideo[];
}
