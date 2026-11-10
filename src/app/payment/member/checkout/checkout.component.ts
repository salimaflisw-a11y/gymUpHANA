import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { PaymentApiService } from '../../payment-api.service';
import { CheckoutResponse, Payment } from '../../shared/payment.model';

@Component({
  selector: 'app-checkout',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './checkout.component.html',
  styleUrl: './checkout.component.scss',
})
export class CheckoutComponent implements OnInit {
  private paymentService = inject(PaymentApiService);
  private route = inject(ActivatedRoute);

  // Selected plan to checkout
  selectedPlan = {
    subscriptionId: 0,
    name: '',
    durationDays: 30,
    price: 0,
    taxRate: 0.08,
    features: [
      'Unlimited gym floor & locker room access',
      'All group fitness & HIIT classes included',
      'Free body composition scan every 30 days',
      'Guest pass included (2 visits / month)',
    ],
  };

  // Payment form state
  cardHolder = '';
  cardNumber = '';
  expiry = '';
  cvv = '';
  billingZip = '';
  selectedMethod: 'card' | 'apple-pay' | 'google-pay' = 'card';

  isLoading = false;
  errorMessage: string | null = null;
  checkoutSuccess: CheckoutResponse | null = null;
  history: Payment[] = [];

  get subtotal(): number {
    return this.selectedPlan.price;
  }

  get tax(): number {
    return +(this.subtotal * this.selectedPlan.taxRate).toFixed(2);
  }

  get total(): number {
    return +(this.subtotal + this.tax).toFixed(2);
  }

  ngOnInit(): void {
    const subscriptionId = Number(this.route.snapshot.queryParamMap.get('subscriptionId'));
    const price = Number(this.route.snapshot.queryParamMap.get('price'));
    const planName = this.route.snapshot.queryParamMap.get('planName');
    if (!Number.isInteger(subscriptionId) || subscriptionId <= 0 || !Number.isFinite(price) || price < 0 || !planName) {
      this.errorMessage = 'Select a membership plan before opening checkout.';
      return;
    }
    this.selectedPlan.subscriptionId = subscriptionId;
    this.selectedPlan.price = price;
    this.selectedPlan.name = planName;
    this.loadHistory();
  }

  loadHistory(): void {
    this.paymentService.getMemberHistory().subscribe((data) => {
      this.history = data;
    });
  }

  submitPayment(): void {
    if (!this.selectedPlan.subscriptionId) {
      this.errorMessage = 'No pending subscription was selected.';
      return;
    }
    this.isLoading = true;
    this.errorMessage = null;

    this.paymentService
      .processCheckout({
        subscriptionId: this.selectedPlan.subscriptionId,
        cardNumber: this.cardNumber,
        cardHolder: this.cardHolder,
        expiry: this.expiry,
        cvv: this.cvv,
      })
      .subscribe({
        next: (res) => {
          this.isLoading = false;
          this.checkoutSuccess = res;
          this.loadHistory();
        },
        error: (err) => {
          this.isLoading = false;
          this.errorMessage =
            err?.error?.error || 'Payment failed. Please check your card details and try again.';
        },
      });
  }

  closeSuccessModal(): void {
    this.checkoutSuccess = null;
  }
}
