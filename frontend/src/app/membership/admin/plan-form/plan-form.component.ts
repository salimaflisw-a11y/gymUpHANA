import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MembershipApiService } from '../../membership-api.service';
import { PlanAccessScope } from '../../shared/plan.model';

@Component({
  selector: 'app-plan-form',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './plan-form.component.html',
  styleUrl: './plan-form.component.scss'
})
export class PlanFormComponent implements OnInit {
  isEditMode = false;
  planId: number | null = null;
  submitting = false;
  error: string | null = null;

  scopes: { value: PlanAccessScope; label: string }[] = [
    { value: 'OPEN_GYM', label: 'Open Gym Access Only' },
    { value: 'CLASSES', label: 'Classes & Studios Only' },
    { value: 'BOTH', label: 'All-Inclusive (Open Gym + Classes)' }
  ];

  formData = {
    name: '',
    price: 49.99,
    durationDays: 30,
    accessScope: 'BOTH' as PlanAccessScope,
    maxMembers: undefined as number | undefined
  };

  constructor(
    private membershipApi: MembershipApiService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.isEditMode = true;
      this.planId = Number(idParam);
      this.membershipApi.getPlan(this.planId).subscribe({
        next: (p) => {
          this.formData.name = p.name;
          this.formData.price = Number(p.price);
          this.formData.durationDays = p.durationDays;
          this.formData.accessScope = p.accessScope;
          this.formData.maxMembers = p.maxMembers ?? undefined;
        },
        error: () => {
          this.error = 'Failed to load membership plan details.';
        }
      });
    }
  }

  onSubmit(): void {
    if (!this.formData.name || this.formData.price <= 0 || this.formData.durationDays <= 0) {
      this.error = 'Please provide valid plan details.';
      return;
    }

    this.submitting = true;
    this.error = null;

    if (this.isEditMode && this.planId) {
      this.membershipApi.updatePlan(this.planId, this.formData).subscribe({
        next: () => {
          this.submitting = false;
          this.router.navigate(['/admin/plans']);
        },
        error: (err) => {
          this.submitting = false;
          this.error = err.error?.error || 'Failed to update plan.';
        }
      });
    } else {
      this.membershipApi.createPlan(this.formData).subscribe({
        next: () => {
          this.submitting = false;
          this.router.navigate(['/admin/plans']);
        },
        error: (err) => {
          this.submitting = false;
          this.error = err.error?.error || 'Failed to create plan.';
        }
      });
    }
  }
}
