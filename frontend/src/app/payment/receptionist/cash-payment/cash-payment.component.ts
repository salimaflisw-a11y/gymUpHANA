import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PaymentApiService } from '../../payment-api.service';
import { CashPaymentResponse } from '../../shared/payment.model';

interface MemberLookup {
  id: number;
  fullName: string;
  email: string;
  phone: string;
  pendingSubscription: {
    id: number;
    planName: string;
    expectedPrice: number;
  } | null;
}

@Component({
  selector: 'app-cash-payment',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './cash-payment.component.html',
  styleUrl: './cash-payment.component.scss',
})
export class CashPaymentComponent implements OnInit {
  private paymentService = inject(PaymentApiService);

  // Search member input
  searchQuery = '';
  pendingList: any[] = [];
  selectedMember: MemberLookup | null = null;

  // Cash payment fields
  cashReceived: number = 0;
  receptionistNotes: string = '';
  isProcessing = false;
  errorMessage: string | null = null;
  paymentSuccess: CashPaymentResponse | null = null;

  // History of receipts recorded today in this session
  todayReceipts: Array<{
    id: number;
    member: string;
    plan: string;
    amount: number;
    time: string;
  }> = [];

  ngOnInit(): void {
    this.loadPending();
  }

  loadPending(): void {
    this.paymentService.getPendingSubscriptions().subscribe({
      next: (list) => {
        this.pendingList = list || [];
        if (this.pendingList.length > 0 && !this.selectedMember) {
          this.selectPending(this.pendingList[0]);
        }
      }
    });
  }

  selectPending(item: any): void {
    this.selectedMember = {
      id: item.member.id,
      fullName: item.member.fullName,
      email: item.member.email,
      phone: '',
      pendingSubscription: {
        id: item.id,
        planName: item.plan.name,
        expectedPrice: item.plan.price,
      },
    };
    this.cashReceived = item.plan.price;
    this.errorMessage = null;
    this.paymentSuccess = null;
  }

  get filteredPending(): any[] {
    if (!this.searchQuery) return this.pendingList;
    const q = this.searchQuery.toLowerCase();
    return this.pendingList.filter(
      item =>
        item.member.fullName.toLowerCase().includes(q) ||
        item.member.email.toLowerCase().includes(q) ||
        String(item.id).includes(q)
    );
  }

  get expectedAmount(): number {
    return this.selectedMember?.pendingSubscription?.expectedPrice ?? 0;
  }

  get changeDue(): number {
    if (!this.cashReceived || this.cashReceived < this.expectedAmount) return 0;
    return +(this.cashReceived - this.expectedAmount).toFixed(2);
  }

  get isUnderpaid(): boolean {
    return this.cashReceived < this.expectedAmount;
  }

  recordCash(): void {
    if (!this.selectedMember || !this.selectedMember.pendingSubscription) {
      this.errorMessage = 'Please select a member with a pending subscription.';
      return;
    }

    if (this.isUnderpaid) {
      this.errorMessage = `Cash received ($${this.cashReceived}) is lower than the plan price ($${this.expectedAmount}).`;
      return;
    }

    this.isProcessing = true;
    this.errorMessage = null;

    this.paymentService
      .recordCashPayment({
        memberId: this.selectedMember.id,
        subscriptionId: this.selectedMember.pendingSubscription.id,
        amount: this.cashReceived,
      })
      .subscribe({
        next: (res) => {
          this.isProcessing = false;
          this.paymentSuccess = res;

          this.todayReceipts.unshift({
            id: res.payment.id,
            member: this.selectedMember!.fullName,
            plan: res.subscription.plan,
            amount: res.payment.amount,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
          });
          this.loadPending();
        },
        error: (err) => {
          this.isProcessing = false;
          this.errorMessage =
            err?.error?.error || 'Failed to record cash payment. Please retry.';
        },
      });
  }

  resetForm(): void {
    this.paymentSuccess = null;
    this.cashReceived = 0;
    this.selectedMember = null;
    this.loadPending();
  }
}
