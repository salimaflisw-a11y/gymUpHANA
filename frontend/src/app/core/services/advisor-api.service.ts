import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { RecoveryAssessment } from '../models/advisor.model';
import { GenerateWorkoutRequest, WorkoutPlan } from '../models/workout-plan.model';

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

  generateWorkoutPlan(request: GenerateWorkoutRequest): Observable<WorkoutPlan> {
    return this.http.post<WorkoutPlan>(`${this.apiUrl}/generate-workout`, request, {
      withCredentials: true
    });
  }
}


