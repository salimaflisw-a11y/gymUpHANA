import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';

export interface RevenueSummary {
  online: number;
  cash: number;
  total: number;
  paymentCount: number;
  previousMonth: number;
  changePercent: number | null;
}

export interface StreakLeaderboardEntry {
  memberId: number;
  memberName: string;
  streak: number;
  lastSuccessDate: string;
}

export interface DashboardMetrics {
  activeMembers: number;
  activeMembersThirtyDaysAgo: number;
  activeMembersChangePercent: number | null;
  totalMembers: number;
  newMembersThisMonth: number;
  classOccupancyRate: number;
  confirmedBookings: number;
  classCapacity: number;
  classSessionCount: number;
  monthlyRevenue: RevenueSummary;
  expiringMemberships: number;
  expiringSharePercent: number;
  topStreaks: StreakLeaderboardEntry[];
  generatedAt: string;
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
        const revenue = res.revenueThisMonth ?? {};

        return {
          activeMembers: Number(res.activeMembersCount ?? 0),
          activeMembersThirtyDaysAgo: Number(res.activeMembersThirtyDaysAgo ?? 0),
          activeMembersChangePercent:
            res.activeMembersChangePercent === null || res.activeMembersChangePercent === undefined
              ? null
              : Number(res.activeMembersChangePercent),
          totalMembers: Number(res.totalRegisteredMembers ?? 0),
          newMembersThisMonth: Number(res.newMembersThisMonth ?? 0),
          classOccupancyRate: Number(res.overallOccupancyRatePercent ?? 0),
          confirmedBookings: Number(res.totalConfirmedBookings ?? 0),
          classCapacity: Number(res.totalCapacity ?? 0),
          classSessionCount: Number(res.classSessionCount ?? 0),
          monthlyRevenue: {
            online: Number(revenue.onlineRevenue ?? 0),
            cash: Number(revenue.cashRevenue ?? 0),
            total: Number(revenue.totalRevenue ?? 0),
            paymentCount: Number(revenue.totalCount ?? 0),
            previousMonth: Number(revenue.previousMonthRevenue ?? 0),
            changePercent:
              revenue.changePercent === null || revenue.changePercent === undefined
                ? null
                : Number(revenue.changePercent),
          },
          expiringMemberships: Number(res.expiringMembershipsCount ?? 0),
          expiringSharePercent: Number(res.expiringMembershipsSharePercent ?? 0),
          topStreaks: (res.topStreaks ?? []).map((s: any) => ({
            memberId: s.memberId,
            memberName: s.memberName ?? 'Unknown member',
            streak: Number(s.streak ?? 0),
            lastSuccessDate: s.lastSuccessDate ?? '',
          })),
          generatedAt: res.generatedAt ?? new Date().toISOString(),
        };
      })
    );
  }
}
