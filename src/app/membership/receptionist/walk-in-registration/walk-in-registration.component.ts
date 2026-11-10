import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MembershipApiService } from '../../membership-api.service';
import { Plan } from '../../shared/plan.model';

@Component({
  selector: 'app-walk-in-registration',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './walk-in-registration.component.html',
  styleUrl: './walk-in-registration.component.scss'
})
export class WalkInRegistrationComponent implements OnInit {
  plans: Plan[] = [];
  formData = {
    fullName: '',
    email: '',
    planId: null as number | null
  };
  loadingPlans = true;
  isSubmitting = false;
  error: string | null = null;
  registrationSuccess: {
    member: { id: number; email: string; fullName: string };
    subscription: { id: number; planName: string; price: number };
    temporaryPassword?: string;
  } | null = null;

  constructor(
    private membershipApi: MembershipApiService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.membershipApi.getReceptionistPlans().subscribe({
      next: (plans) => {
        this.plans = plans;
        if (plans.length > 0) {
          this.formData.planId = plans[0].id;
        }
        this.loadingPlans = false;
      },
      error: () => {
        this.plans = [
          { id: 1, name: 'Open Gym Standard', price: 29.99, durationDays: 30, accessScope: 'OPEN_GYM', maxMembers: null, isActive: true },
          { id: 2, name: 'Pro All-Access Unlimited', price: 59.99, durationDays: 30, accessScope: 'BOTH', maxMembers: 100, isActive: true }
        ];
        this.formData.planId = 1;
        this.loadingPlans = false;
      }
    });
  }

  onSubmit(): void {
    if (!this.formData.fullName || !this.formData.email || !this.formData.planId) {
      this.error = 'Full Name, Email, and a Plan selection are required.';
      return;
    }

    this.isSubmitting = true;
    this.error = null;

    this.membershipApi.registerWalkInMember({
      fullName: this.formData.fullName,
      email: this.formData.email,
      planId: this.formData.planId
    }).subscribe({
      next: (res) => {
        this.isSubmitting = false;
        this.registrationSuccess = res;
      },
      error: (err) => {
        this.isSubmitting = false;
        this.error = err.error?.error || 'Failed to register walk-in member. Please verify email format.';
      }
    });
  }

  proceedToCashPayment(): void {
    this.router.navigate(['/receptionist/cash-payment']);
  }

  resetForm(): void {
    this.registrationSuccess = null;
    this.formData.fullName = '';
    this.formData.email = '';
  }
}
