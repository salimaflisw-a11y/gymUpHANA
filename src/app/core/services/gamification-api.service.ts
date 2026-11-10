import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import {
  GamificationProfile,
  CommunityChallenge,
  LeaderboardResponse,
  ClaimPerkResponse
} from '../models/gamification.model';

@Injectable({
  providedIn: 'root'
})
export class GamificationApiService {
  private readonly apiUrl = '/api/gamification';

  constructor(private http: HttpClient) {}

  getProfile(): Observable<GamificationProfile> {
    return this.http.get<GamificationProfile>(`${this.apiUrl}/profile`, {
      withCredentials: true
    });
  }

  getChallenges(): Observable<CommunityChallenge[]> {
    return this.http.get<CommunityChallenge[]>(`${this.apiUrl}/challenges`, {
      withCredentials: true
    });
  }

  joinChallenge(id: number): Observable<{ success: boolean; message: string; challenge: string }> {
    return this.http.post<{ success: boolean; message: string; challenge: string }>(
      `${this.apiUrl}/challenges/${id}/join`,
      {},
      { withCredentials: true }
    );
  }

  claimPerk(memberBadgeId: number): Observable<ClaimPerkResponse> {
    return this.http.post<ClaimPerkResponse>(
      `${this.apiUrl}/badges/claim/${memberBadgeId}`,
      {},
      { withCredentials: true }
    );
  }

  getLeaderboard(): Observable<LeaderboardResponse> {
    return this.http.get<LeaderboardResponse>(`${this.apiUrl}/leaderboard`, {
      withCredentials: true
    });
  }
}

