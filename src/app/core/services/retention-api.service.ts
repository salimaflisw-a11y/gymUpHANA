import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AtRiskMember, NudgeResponse } from '../models/retention.model';

@Injectable({
  providedIn: 'root'
})
export class RetentionApiService {
  private readonly apiUrl = '/api/admin/retention';

  constructor(private http: HttpClient) {}

  getAtRiskMembers(): Observable<AtRiskMember[]> {
    return this.http.get<AtRiskMember[]>(`${this.apiUrl}/at-risk`, {
      withCredentials: true
    });
  }

  sendNudge(memberId: number): Observable<NudgeResponse> {
    return this.http.post<NudgeResponse>(
      `${this.apiUrl}/nudge/${memberId}`,
      {},
      { withCredentials: true }
    );
  }
}

