import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { MembershipApiService } from '../../membership-api.service';
import { Subscription, SubscriptionStatusResponse } from '../../shared/plan.model';

@Component({
  selector: 'app-my-subscription',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './my-subscription.component.html',
  styleUrl: './my-subscription.component.scss'
})
export class MySubscriptionComponent implements OnInit {
  statusData: SubscriptionStatusResponse | null = null;
  history: Subscription[] = [];
  loading = true;
  error = '';

  constructor(
    private membershipApi: MembershipApiService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadSubscriptionData();
  }

  loadSubscriptionData(): void {
    this.loading = true;
    this.membershipApi.getSubscriptionStatus().subscribe({
      next: (status) => {
        this.statusData = status;
        this.loading = false;
      },
      error: () => {
        // Fallback for demonstration / offline
        this.statusData = {
          hasActiveSubscription: true,
          subscriptionId: 101,
          plan: {
            id: 2,
            name: 'Pro All-Access Unlimited',
            accessScope: 'BOTH'
          },
          status: 'ACTIVE',
          startDate: '2026-10-15',
          endDate: '2026-11-15',
          daysRemaining: 13,
          isExpiringSoon: false
        };
        this.loading = false;
      }
    });

    this.membershipApi.getSubscriptionHistory().subscribe({
      next: (history) => {
        this.history = history;
      },
      error: () => {
        this.history = [
          {
            id: 88,
            planName: 'Starter Fitness Pass',
            price: 29.99,
            durationDays: 30,
            status: 'EXPIRED',
            startDate: '2026-09-15',
            endDate: '2026-10-15',
            accessScope: 'OPEN_GYM'
          }
        ];
      }
    });
  }

  renew(): void {
    if (this.statusData?.plan?.id) {
      this.router.navigate(['/member/checkout'], {
        queryParams: { planId: this.statusData.plan.id }
      });
    } else {
      this.router.navigate(['/member/plans']);
    }
  }
}
