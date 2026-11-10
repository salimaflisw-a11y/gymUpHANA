import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { GamificationApiService } from '../../../core/services/gamification-api.service';
import {
  GamificationProfile,
  CommunityChallenge,
  LeaderboardUser,
  MemberBadge
} from '../../../core/models/gamification.model';

@Component({
  selector: 'app-challenges-trophies',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './challenges-trophies.component.html',
  styleUrl: './challenges-trophies.component.scss'
})
export class ChallengesTrophiesComponent implements OnInit {
  profile: GamificationProfile | null = null;
  challenges: CommunityChallenge[] = [];
  leaderboard: LeaderboardUser[] = [];
  
  activeTab: 'challenges' | 'badges' | 'leaderboard' = 'challenges';
  loading = true;
  actionMessage: { text: string; type: 'success' | 'error' } | null = null;
  claimingBadgeId: number | null = null;
  joiningChallengeId: number | null = null;

  constructor(private gamificationApi: GamificationApiService) {}

  ngOnInit(): void {
    this.loadData();
  }

  loadData(): void {
    this.loading = true;
    this.gamificationApi.getProfile().subscribe({
      next: (prof) => {
        this.profile = prof;
      },
      error: () => {}
    });

    this.gamificationApi.getChallenges().subscribe({
      next: (challenges) => {
        this.challenges = challenges;
      },
      error: () => {}
    });

    this.gamificationApi.getLeaderboard().subscribe({
      next: (res) => {
        this.leaderboard = res.streakLeaders;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  joinChallenge(challenge: CommunityChallenge): void {
    if (challenge.isJoined) return;
    this.joiningChallengeId = challenge.id;
    this.gamificationApi.joinChallenge(challenge.id).subscribe({
      next: (res) => {
        challenge.isJoined = true;
        challenge.participantCount++;
        this.joiningChallengeId = null;
        this.showToast(res.message || 'Joined challenge successfully!', 'success');
      },
      error: (err) => {
        this.joiningChallengeId = null;
        this.showToast(err.error?.error || 'Failed to join challenge.', 'error');
      }
    });
  }

  claimPerk(badge: MemberBadge): void {
    if (badge.isPerkClaimed || !badge.perkReward) return;
    this.claimingBadgeId = badge.memberBadgeId;
    this.gamificationApi.claimPerk(badge.memberBadgeId).subscribe({
      next: (res) => {
        badge.isPerkClaimed = true;
        this.claimingBadgeId = null;
        this.showToast(`🎉 Perk Claimed! ${res.perk}`, 'success');
      },
      error: (err) => {
        this.claimingBadgeId = null;
        this.showToast(err.error?.error || 'Failed to claim perk.', 'error');
      }
    });
  }

  private showToast(text: string, type: 'success' | 'error'): void {
    this.actionMessage = { text, type };
    setTimeout(() => {
      this.actionMessage = null;
    }, 4500);
  }
}

