import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RetentionApiService } from '../../core/services/retention-api.service';
import { AtRiskMember } from '../../core/models/retention.model';

@Component({
  selector: 'app-retention',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './retention.component.html',
  styleUrl: './retention.component.scss'
})
export class RetentionComponent implements OnInit {
  atRiskMembers: AtRiskMember[] = [];
  loading = true;
  nudgingMemberId: number | null = null;
  toastMessage: { text: string; type: 'success' | 'error' } | null = null;

  constructor(private retentionApi: RetentionApiService) {}

  ngOnInit(): void {
    this.loadAtRisk();
  }

  loadAtRisk(): void {
    this.loading = true;
    this.retentionApi.getAtRiskMembers().subscribe({
      next: (members) => {
        this.atRiskMembers = Array.isArray(members) ? members : [];
        this.loading = false;
      },
      error: () => {
        this.atRiskMembers = [];
        this.loading = false;
      }
    });
  }

  sendNudge(member: AtRiskMember): void {
    this.nudgingMemberId = member.memberId;
    this.retentionApi.sendNudge(member.memberId).subscribe({
      next: (res) => {
        this.nudgingMemberId = null;
        this.showToast(`📩 Re-engagement nudge sent to ${member.name}!`, 'success');
      },
      error: (err) => {
        this.nudgingMemberId = null;
        this.showToast(err.error?.error || 'Failed to send re-engagement nudge.', 'error');
      }
    });
  }

  get criticalCount(): number {
    return Array.isArray(this.atRiskMembers) ? this.atRiskMembers.filter(m => m.riskLevel === 'CRITICAL_CHURN').length : 0;
  }

  get highRiskCount(): number {
    return Array.isArray(this.atRiskMembers) ? this.atRiskMembers.filter(m => m.riskLevel === 'HIGH_RISK').length : 0;
  }

  private showToast(text: string, type: 'success' | 'error'): void {
    this.toastMessage = { text, type };
    setTimeout(() => {
      this.toastMessage = null;
    }, 4500);
  }
}

