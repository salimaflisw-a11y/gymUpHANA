import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { MembershipApiService } from '../../membership-api.service';
import { Plan } from '../../shared/plan.model';

@Component({
  selector: 'app-plan-selector',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './plan-selector.component.html',
  styleUrl: './plan-selector.component.scss'
})
export class PlanSelectorComponent implements OnInit {
  plans: Plan[] = [];
  loading = true;
  error = '';

  constructor(
    private membershipApi: MembershipApiService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadPlans();
  }

  loadPlans(): void {
    this.loading = true;
    this.membershipApi.getMemberPlans().subscribe({
      next: (data) => {
        this.plans = (data || []).map(p => ({
          ...p,
          price: Number(p.price) || 0
        }));
        this.loading = false;
      },
      error: () => {
        this.plans = [
          {
            id: 1,
            name: 'Open Gym Standard',
            price: 29.99,
            durationDays: 30,
            accessScope: 'OPEN_GYM',
            maxMembers: null,
            isActive: true
          },
          {
            id: 2,
            name: 'Pro All-Access Cyber',
            price: 59.99,
            durationDays: 30,
            accessScope: 'BOTH',
            maxMembers: 100,
            isActive: true
          },
          {
            id: 3,
            name: 'Elite Studio Classes Only',
            price: 49.99,
            durationDays: 30,
            accessScope: 'CLASSES',
            maxMembers: 50,
            isActive: true
          }
        ];
        this.loading = false;
      }
    });
  }

  selectPlan(plan: Plan): void {
    this.error = '';
    this.membershipApi.subscribeToPlan(plan.id).subscribe({
      next: (response) => this.router.navigate(['/member/checkout'], {
        queryParams: {
          subscriptionId: response.subscription.id,
          planName: response.subscription.planName,
          price: response.subscription.price,
        }
      }),
      error: (error) => {
        this.error = error?.error?.error || 'Unable to create the pending subscription.';
      }
    });
  }

  getFeatures(scope: string): string[] {
    if (scope === 'BOTH') {
      return [
        'Full 24/7 Functional Gym Floor Access',
        'Unlimited Studio Classes & Masterclasses',
        'Biometric Streak & Check-in Tracking',
        'Locker Room, Sauna & Recovery Lounge',
        'Guest Pass: 2 per billing cycle'
      ];
    } else if (scope === 'CLASSES') {
      return [
        'All Instructor-Led Group Classes',
        'Studio & Cycling Access during class',
        'Priority Waitlist Reservation',
        'Trainer Direct Check-ins',
        'Locker Room Access'
      ];
    } else {
      return [
        'Full 24/7 Functional Gym Floor Access',
        'Free Weights & Machine Park Access',
        'Cardio & Functional Turf Access',
        'Standard Biometric Streak Tracking',
        'Locker Room Access'
      ];
    }
  }
}
