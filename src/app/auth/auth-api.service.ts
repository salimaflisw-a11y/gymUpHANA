import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { User, LoginResponse, LoginRequest, RegisterRequest, RegisterResponse, UpdateProfileRequest } from '../shared/models/user.model';

@Injectable({
  providedIn: 'root'
})
export class AuthApiService {
  private readonly apiUrl = '/api/auth';

  constructor(private http: HttpClient) {}

  /**
   * POST /api/auth/register
   * Returns { message, user } — backend response wraps the user object.
   */
  register(data: RegisterRequest): Observable<RegisterResponse> {
    return this.http.post<RegisterResponse>(`${this.apiUrl}/register`, data);
  }

  /**
   * POST /api/auth/login
   * Returns { message, user } — backend is session-based (no token).
   */
  login(data: LoginRequest): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/login`, data, {
      withCredentials: true
    });
  }

  /**
   * GET /api/auth/me
   * Returns the current authenticated user.
   */
  getCurrentUser(): Observable<User> {
    return this.http.get<User>(`${this.apiUrl}/me`, { withCredentials: true });
  }

  /**
   * PUT /api/auth/profile
   * Returns { message, user }.
   */
  updateProfile(data: UpdateProfileRequest): Observable<{ message: string; user: User }> {
    return this.http.put<{ message: string; user: User }>(`${this.apiUrl}/profile`, data, {
      withCredentials: true
    });
  }

  logout(): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/logout`, {}, { withCredentials: true });
  }
}
