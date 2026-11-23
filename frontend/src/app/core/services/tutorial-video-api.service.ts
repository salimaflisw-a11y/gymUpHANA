import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TutorialVideo, TutorialVideoListResponse } from '../models/tutorial-video.model';

@Injectable({
  providedIn: 'root'
})
export class TutorialVideoApiService {
  private readonly apiUrl = '/api/tutorial-videos';

  constructor(private http: HttpClient) {}

  getVideos(filters?: { search?: string; category?: string; difficulty?: string }): Observable<TutorialVideoListResponse> {
    let params = new HttpParams();
    if (filters?.search) {
      params = params.set('search', filters.search);
    }
    if (filters?.category) {
      params = params.set('category', filters.category);
    }
    if (filters?.difficulty) {
      params = params.set('difficulty', filters.difficulty);
    }

    return this.http.get<TutorialVideoListResponse>(this.apiUrl, {
      params,
      withCredentials: true
    });
  }

  getVideo(id: number): Observable<TutorialVideo> {
    return this.http.get<TutorialVideo>(`${this.apiUrl}/${id}`, {
      withCredentials: true
    });
  }
}
