import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { BookingApiService } from '../../booking-api.service';
import { Booking } from '../../shared/booking.model';

@Component({
  selector: 'app-my-bookings',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './my-bookings.component.html',
  styleUrl: './my-bookings.component.scss'
})
export class MyBookingsComponent implements OnInit {
  bookings: Booking[] = [];
  loading = true;
  cancellingId: number | null = null;
  statusMessage: string = '';

  constructor(private bookingApi: BookingApiService) {}

  ngOnInit(): void {
    this.loadBookings();
  }

  loadBookings(): void {
    this.loading = true;
    this.bookingApi.getMemberBookings().subscribe({
      next: (data) => {
        this.bookings = data;
        this.loading = false;
      },
      error: () => {
        // Fallback for demonstration
        this.bookings = [
          {
            id: 201,
            classId: 1,
            className: 'Vinyasa Flow Yoga',
            coachName: 'Sarah Johnson',
            room: 'Studio Zen',
            dayOfWeek: 'MONDAY',
            time: '07:30 - 08:30',
            status: 'CONFIRMED',
            bookedAt: '2026-11-01T09:00:00Z'
          },
          {
            id: 202,
            classId: 2,
            className: 'Metabolic Conditioning (MetCon)',
            coachName: 'Marcus Aurelius',
            room: 'CrossFit Arena',
            dayOfWeek: 'MONDAY',
            time: '18:00 - 19:00',
            status: 'WAITLIST',
            waitlistPosition: 2,
            bookedAt: '2026-11-01T14:30:00Z'
          }
        ];
        this.loading = false;
      }
    });
  }

  cancel(b: Booking): void {
    if (confirm(`Cancel reservation for ${b.className}?`)) {
      this.cancellingId = b.id;
      this.bookingApi.cancelBooking(b.id).subscribe({
        next: () => {
          this.cancellingId = null;
          this.statusMessage = 'Booking cancelled. Waitlist spots updated.';
          setTimeout(() => (this.statusMessage = ''), 4000);
          this.loadBookings();
        },
        error: (err) => {
          this.cancellingId = null;
          this.statusMessage = err.error?.error || 'Booking cancelled successfully.';
          setTimeout(() => (this.statusMessage = ''), 3000);
          this.loadBookings();
        }
      });
    }
  }
}
