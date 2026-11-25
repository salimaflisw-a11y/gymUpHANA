import { Component, OnDestroy, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { DashboardApiService, DashboardMetrics } from '../dashboard-api.service';

const REFRESH_INTERVAL_MS = 30000;

@Component({
  selector: 'app-dashboard-home',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard-home.component.html',
  styleUrl: './dashboard-home.component.scss'
})
export class DashboardHomeComponent implements OnInit, OnDestroy {
  metrics: DashboardMetrics | null = null;
  loading = true;
  error = '';

  private refreshTimer: ReturnType<typeof setInterval> | null = null;

  constructor(private dashboardApi: DashboardApiService) { }

  ngOnInit(): void {
    this.loadDashboard();
    this.refreshTimer = setInterval(() => this.loadDashboard(true), REFRESH_INTERVAL_MS);
  }

  ngOnDestroy(): void {
    if (this.refreshTimer !== null) {
      clearInterval(this.refreshTimer);
    }
  }

  loadDashboard(silent = false): void {
    if (!silent) {
      this.loading = true;
    }
    this.dashboardApi.getDashboardMetrics().subscribe({
      next: (data) => {
        this.metrics = data;
        this.error = '';
        this.loading = false;
      },
      error: () => {
        this.error = 'Failed to load dashboard data';
        this.loading = false;
      }
    });
  }

  get growthLabel(): string {
    const metrics = this.metrics;
    if (!metrics) {
      return 'SYNCING';
    }
    const diff = metrics.activeMembers - metrics.activeMembersThirtyDaysAgo;
    const sign = diff > 0 ? '+' : '';
    return `${sign}${diff} / 30D`;
  }

  get growthClass(): string {
    const metrics = this.metrics;
    if (!metrics) {
      return 'neutral';
    }
    const diff = metrics.activeMembers - metrics.activeMembersThirtyDaysAgo;
    if (diff > 0) {
      return 'positive';
    }
    return diff < 0 ? 'negative' : 'neutral';
  }

  get occupancyStatus(): { label: string; cssClass: string } {
    const rate = this.metrics?.classOccupancyRate ?? 0;
    if (rate >= 90) {
      return { label: 'AT PEAK', cssClass: 'status-alert' };
    }
    if (rate >= 75) {
      return { label: 'OPTIMAL', cssClass: 'status-good' };
    }
    if (rate >= 50) {
      return { label: 'BALANCED', cssClass: 'status-good' };
    }
    if (rate >= 20) {
      return { label: 'BUILDING', cssClass: 'neutral' };
    }
    return { label: 'QUIET', cssClass: 'neutral' };
  }

  get expiringStatus(): { label: string; cssClass: string } {
    const count = this.metrics?.expiringMemberships ?? 0;
    if (count === 0) {
      return { label: 'ALL CLEAR', cssClass: 'status-good' };
    }
    if (count <= 3) {
      return { label: 'ATTENTION', cssClass: 'warning' };
    }
    return { label: 'URGENT', cssClass: 'danger' };
  }

  get revenueChangeLabel(): string {
    const revenue = this.metrics?.monthlyRevenue;
    if (!revenue || revenue.changePercent === null || revenue.changePercent === undefined) {
      return 'THIS MONTH';
    }
    const sign = revenue.changePercent > 0 ? '+' : '';
    return `${sign}${Number(revenue.changePercent.toFixed(1))}% MOM`;
  }

  get revenueChangeClass(): string {
    const change = this.metrics?.monthlyRevenue.changePercent;
    if (change === null || change === undefined) {
      return 'revenue-badge';
    }
    return change >= 0 ? 'revenue-badge' : 'negative';
  }

  get lastUpdatedLabel(): string {
    if (!this.metrics?.generatedAt) {
      return 'SYNCING';
    }
    return 'UPDATED ' + new Date(this.metrics.generatedAt).toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  }
}
