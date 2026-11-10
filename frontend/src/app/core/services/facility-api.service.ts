import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import {
  FacilityOccupancy,
  CrowdForecastResponse,
  DynamicAccessPass
} from '../models/facility.model';

@Injectable({
  providedIn: 'root'
})
export class FacilityApiService {
  private readonly apiUrl = '/api/facility';

  constructor(private http: HttpClient) {}

  getLiveOccupancy(): Observable<FacilityOccupancy> {
    return this.http.get<FacilityOccupancy>(`${this.apiUrl}/occupancy`, {
      withCredentials: true
    });
  }

  getCrowdForecast(): Observable<CrowdForecastResponse> {
    return this.http.get<CrowdForecastResponse>(`${this.apiUrl}/forecast`, {
      withCredentials: true
    });
  }

  getDynamicAccessPass(): Observable<DynamicAccessPass> {
    return this.http.get<DynamicAccessPass>(`${this.apiUrl}/access-pass`, {
      withCredentials: true
    });
  }
}

