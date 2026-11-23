import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { AtRiskMember, NudgeResponse } from '../models/retention.model';

@Injectable({
  providedIn: 'root'
})
export class RetentionApiService {
  private readonly apiUrl = '/api/admin/retention';

  constructor(private http: HttpClient) {}

  getAtRiskMembers(): Observable<AtRiskMember[]> {
    return this.http.get<any>(`${this.apiUrl}/at-risk`, {
      withCredentials: true
    }).pipe(
      map((res: any) => {
        if (Array.isArray(res)) return res;
        if (res && Array.isArray(res.members)) return res.members;
        return [];
      })
    );
  }

  sendNudge(memberId: number): Observable<NudgeResponse> {
    return this.http.post<NudgeResponse>(
      `${this.apiUrl}/nudge/${memberId}`,
      {},
      { withCredentials: true }
    );
  }
}

