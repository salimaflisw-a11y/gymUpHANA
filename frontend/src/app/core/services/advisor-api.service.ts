import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { RecoveryAssessment } from '../models/advisor.model';

@Injectable({
  providedIn: 'root'
})
export class AdvisorApiService {
  private readonly apiUrl = '/api/advisor';

  constructor(private http: HttpClient) {}

  getRecoveryAssessment(): Observable<RecoveryAssessment> {
    return this.http.get<RecoveryAssessment>(`${this.apiUrl}/recovery`, {
      withCredentials: true
    });
  }
}

