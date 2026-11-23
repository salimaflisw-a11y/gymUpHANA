import { Component, HostListener, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { TutorialVideo } from '../../../core/models/tutorial-video.model';
import { TutorialVideoApiService } from '../../../core/services/tutorial-video-api.service';

const DIFFICULTY_LABELS: Record<string, string> = {
  BEGINNER: 'Beginner',
  INTERMEDIATE: 'Intermediate',
  ADVANCED: 'Advanced',
  ALL_LEVELS: 'All Levels'
};

@Component({
  selector: 'app-tutorial-library',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tutorial-library.component.html',
  styleUrl: './tutorial-library.component.scss'
})
export class TutorialLibraryComponent implements OnInit {
  private tutorialApi = inject(TutorialVideoApiService);
  private sanitizer = inject(DomSanitizer);

  videos: TutorialVideo[] = [];
  categories: string[] = [];
  difficulties: string[] = [];

  loading = true;
  errorMessage = '';

  searchText = '';
  activeCategory = 'All';
  activeDifficulty = 'All';

  selectedVideo: TutorialVideo | null = null;
  trustedEmbedUrl: SafeResourceUrl | null = null;

  ngOnInit(): void {
    this.loadVideos();
  }

  loadVideos(): void {
    this.loading = true;
    this.errorMessage = '';

    this.tutorialApi.getVideos().subscribe({
      next: (response) => {
        this.videos = response.videos;
        this.categories = response.categories;
        this.difficulties = response.difficulties;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.errorMessage = 'Unable to load the tutorial library. Refresh the page and try again.';
      }
    });
  }

  get filteredVideos(): TutorialVideo[] {
    const term = this.searchText.trim().toLowerCase();

    return this.videos.filter((video) => {
      const matchesCategory = this.activeCategory === 'All' || video.category === this.activeCategory;
      const matchesDifficulty = this.activeDifficulty === 'All' || video.difficulty === this.activeDifficulty;
      if (!matchesCategory || !matchesDifficulty) {
        return false;
      }
      if (!term) {
        return true;
      }
      const haystack = [
        video.title,
        video.description,
        video.targetMuscle,
        video.instructorName,
        video.category
      ].filter(Boolean).join(' ').toLowerCase();

      return haystack.includes(term);
    });
  }

  get hasActiveFilters(): boolean {
    return this.activeCategory !== 'All' || this.activeDifficulty !== 'All' || this.searchText.trim() !== '';
  }

  setCategory(category: string): void {
    this.activeCategory = category;
  }

  setDifficulty(difficulty: string): void {
    this.activeDifficulty = difficulty;
  }

  resetFilters(): void {
    this.searchText = '';
    this.activeCategory = 'All';
    this.activeDifficulty = 'All';
  }

  openVideo(video: TutorialVideo): void {
    this.selectedVideo = video;
    this.trustedEmbedUrl = this.trustEmbed(video.embedUrl);
    document.body.style.overflow = 'hidden';

    this.tutorialApi.getVideo(video.id).subscribe({
      next: (detail) => {
        if (this.selectedVideo?.id !== detail.id) {
          return;
        }
        this.selectedVideo = detail;
        this.trustedEmbedUrl = this.trustEmbed(detail.embedUrl);
        const listEntry = this.videos.find((v) => v.id === detail.id);
        if (listEntry) {
          listEntry.viewsCount = detail.viewsCount;
        }
      },
      error: () => {}
    });
  }

  closeVideo(): void {
    this.selectedVideo = null;
    this.trustedEmbedUrl = null;
    document.body.style.overflow = '';
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    if (this.selectedVideo) {
      this.closeVideo();
    }
  }

  difficultyLabel(value: string): string {
    return DIFFICULTY_LABELS[value] || value;
  }

  trackByVideoId(_index: number, video: TutorialVideo): number {
    return video.id;
  }

  private trustEmbed(url: string | null): SafeResourceUrl | null {
    const allowedPrefix = 'https://www.youtube-nocookie.com/embed/';
    return url && url.startsWith(allowedPrefix)
      ? this.sanitizer.bypassSecurityTrustResourceUrl(url)
      : null;
  }
}
