import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, catchError, map, of } from 'rxjs';
import {
  Plan,
  Subscription,
  ExpiringSubscription,
  SubscriptionStatusResponse,
  SubscribeResponse,
  CreatePlanRequest,
  UpdatePlanRequest
} from './shared/plan.model';

@Injectable({
  providedIn: 'root'
})
export class MembershipApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = '/api';

  // Admin endpoints
  getPlans(): Observable<Plan[]> {
    return this.http.get<Plan[]>(`${this.baseUrl}/admin/plans`).pipe(
      catchError((error) => {
        throw error;
        const fallback: Plan[] = [
          {
            id: 1,
            name: 'Basic',
            price: 29.99,
            durationDays: 30,
            accessScope: 'OPEN_GYM',
            maxMembers: null,
            isActive: true,
            currentMembersCount: 45
          },
          {
            id: 2,
            name: 'Premium',
            price: 59.99,
            durationDays: 30,
            accessScope: 'BOTH',
            maxMembers: 100,
            isActive: true,
            currentMembersCount: 78
          },
          {
            id: 3,
            name: 'Classes Only',
            price: 49.99,
            durationDays: 30,
            accessScope: 'CLASSES',
            maxMembers: 50,
            isActive: true,
            currentMembersCount: 32
          }
        ];
        return of(fallback);
      })
    );
  }

  getPlan(id: number): Observable<Plan> {
    return this.http.get<Plan>(`${this.baseUrl}/admin/plans/${id}`);
  }

  createPlan(data: CreatePlanRequest): Observable<Plan> {
    return this.http.post<Plan>(`${this.baseUrl}/admin/plans`, data);
  }

  updatePlan(id: number, data: UpdatePlanRequest): Observable<Plan> {
    return this.http.patch<Plan>(`${this.baseUrl}/admin/plans/${id}`, data);
  }

  activatePlan(id: number): Observable<any> {
    return this.http.patch(`${this.baseUrl}/admin/plans/${id}/activate`, {});
  }

  deactivatePlan(id: number): Observable<any> {
    return this.http.patch(`${this.baseUrl}/admin/plans/${id}/deactivate`, {});
  }

  getSubscriptions(status?: string, planId?: number, limit?: number): Observable<Subscription[]> {
    let params = new HttpParams();
    if (status) params = params.set('status', status);
    if (planId) params = params.set('planId', planId);
    if (limit) params = params.set('limit', limit);

    return this.http.get<any[]>(`${this.baseUrl}/admin/subscriptions`, { params }).pipe(
      map((subs: any[]) => {
        return (subs || []).map((s: any) => ({
          ...s,
          planId: s.plan?.id ?? s.planId,
          planName: s.plan?.name ?? s.planName,
          memberId: s.member?.id ?? s.memberId,
          memberEmail: s.member?.email ?? s.memberEmail,
          memberFullName: s.member?.fullName ?? s.memberFullName,
          price: s.plan?.price ?? s.price,
          accessScope: s.plan?.accessScope ?? s.accessScope,
        }));
      }),
      catchError((error) => { throw error; })
    );
  }

  getExpiringSubscriptions(days?: number): Observable<ExpiringSubscription[]> {
    let params = new HttpParams();
    if (days) params = params.set('days', days);

    return this.http.get<ExpiringSubscription[]>(`${this.baseUrl}/admin/plans/expiring`, { params }).pipe(
      catchError((error) => { throw error; })
    );
  }

  // Receptionist endpoints
  getReceptionistPlans(): Observable<Plan[]> {
    return this.http.get<Plan[]>(`${this.baseUrl}/receptionist/plans`).pipe(
      catchError((error) => { throw error; })
    );
  }

  registerWalkInMember(data: { email: string; fullName: string; planId: number }): Observable<any> {
    return this.http.post(`${this.baseUrl}/receptionist/members/register`, data);
  }

  // Member endpoints
  getMemberPlans(): Observable<Plan[]> {
    return this.http.get<Plan[]>(`${this.baseUrl}/member/membership/plans`).pipe(
      catchError((error) => { throw error; })
    );
  }

  subscribeToPlan(planId: number): Observable<SubscribeResponse> {
    return this.http.post<SubscribeResponse>(`${this.baseUrl}/member/membership/subscribe`, { planId });
  }

  renewSubscription(planId: number): Observable<SubscribeResponse> {
    return this.http.post<SubscribeResponse>(`${this.baseUrl}/member/membership/renew`, { planId });
  }

  getSubscriptionStatus(): Observable<SubscriptionStatusResponse> {
    return this.http.get<SubscriptionStatusResponse>(`${this.baseUrl}/member/membership/status`);
  }

  getSubscriptionHistory(): Observable<Subscription[]> {
    return this.http.get<any[]>(`${this.baseUrl}/member/membership/history`).pipe(
      map((subs: any[]) => {
        return (subs || []).map((s: any) => ({
          ...s,
          planId: s.plan?.id ?? s.planId,
          planName: s.plan?.name ?? s.planName,
          price: Number(s.plan?.price ?? s.price) || 0,
          accessScope: s.plan?.accessScope ?? s.accessScope,
        }));
      }),
      catchError((error) => { throw error; })
    );
  }
}
