import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, map, of } from 'rxjs';

export interface DashboardMetrics {
  activeMembers: number;
  totalMembers: number;
  classOccupancyRate: number;
  monthlyRevenue: {
    online: number;
    cash: number;
    total: number;
  };
  expiringMemberships: number;
  topStreaks: Array<{
    memberId: number;
    memberName: string;
    streak: number;
    lastSuccessDate: string;
  }>;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = '/api';

  getDashboardMetrics(): Observable<DashboardMetrics> {
    return this.http.get<any>(`${this.baseUrl}/admin/dashboard`).pipe(
      map((res: any) => {
        return {
          activeMembers: res.activeMembersCount ?? res.activeMembers ?? 0,
          totalMembers: res.totalRegisteredMembers ?? res.totalMembers ?? 0,
          classOccupancyRate: res.overallOccupancyRatePercent ?? res.classOccupancyRate ?? 0,
          monthlyRevenue: {
            online: res.revenueThisMonth?.onlineRevenue ?? res.monthlyRevenue?.online ?? 0,
            cash: res.revenueThisMonth?.cashRevenue ?? res.monthlyRevenue?.cash ?? 0,
            total: res.revenueThisMonth?.totalRevenue ?? res.monthlyRevenue?.total ?? 0,
          },
          expiringMemberships: res.expiringMembershipsCount ?? res.expiringMemberships ?? 0,
          topStreaks: (res.topStreaks || []).map((s: any) => ({
            memberId: s.memberId,
            memberName: s.memberName,
            streak: s.streak,
            lastSuccessDate: s.lastSuccessDate || '',
          })),
        };
      }),
      catchError((error) => {
        throw error;
        const fallback: DashboardMetrics = {
          activeMembers: 156,
          totalMembers: 203,
          classOccupancyRate: 78.5,
          monthlyRevenue: {
            online: 8420.0,
            cash: 3150.0,
            total: 11570.0
          },
          expiringMemberships: 12,
          topStreaks: [
            { memberId: 101, memberName: 'Alexander Volkov', streak: 34, lastSuccessDate: '2026-10-21' },
            { memberId: 102, memberName: 'Jessica Chen', streak: 28, lastSuccessDate: '2026-10-21' },
            { memberId: 103, memberName: 'Marcus Aurelius', streak: 21, lastSuccessDate: '2026-10-20' },
            { memberId: 104, memberName: 'Sophia Taylor', streak: 16, lastSuccessDate: '2026-10-21' },
            { memberId: 105, memberName: 'David Beckham', streak: 12, lastSuccessDate: '2026-10-20' }
          ]
        };
        return of(fallback);
      })
    );
  }
}
