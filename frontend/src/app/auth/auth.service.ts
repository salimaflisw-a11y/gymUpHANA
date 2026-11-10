import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { User, LoginResponse } from '../shared/models/user.model';
import { AuthApiService } from './auth-api.service';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(private readonly authApi: AuthApiService) {
    const storedUser = localStorage.getItem('currentUser');
    if (storedUser) {
      try {
        this.currentUserSubject.next(JSON.parse(storedUser));
      } catch {
        localStorage.removeItem('currentUser');
      }
    }
  }

  login(response: LoginResponse | { message: string; user: User }): void {
    if (response && response.user) {
      localStorage.setItem('currentUser', JSON.stringify(response.user));
      this.currentUserSubject.next(response.user);
    }
  }

  logout(): void {
    // Clear the browser state immediately; invalidating the server session is
    // best-effort so logout still works while offline.
    this.authApi.logout().subscribe({ error: () => undefined });
    localStorage.removeItem('currentUser');
    localStorage.removeItem('token');
    this.currentUserSubject.next(null);
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  isLoggedIn(): boolean {
    return !!this.currentUserSubject.value;
  }

  hasRole(role: string): boolean {
    const user = this.getCurrentUser();
    return user?.role === role;
  }

  isAdmin(): boolean {
    return this.hasRole('ROLE_ADMIN');
  }

  isReceptionist(): boolean {
    return this.hasRole('ROLE_RECEPTIONIST');
  }

  isCoach(): boolean {
    return this.hasRole('ROLE_COACH');
  }

  isMember(): boolean {
    return this.hasRole('ROLE_MEMBER');
  }
}
