import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { BookingApiService } from '../../booking-api.service';
import { ClassSession } from '../../shared/class.model';

@Component({
  selector: 'app-class-schedule',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './class-schedule.component.html',
  styleUrl: './class-schedule.component.scss'
})
export class ClassScheduleComponent implements OnInit {
  classes: ClassSession[] = [];
  filteredClasses: ClassSession[] = [];
  selectedDay: string = 'ALL';
  daysOfWeek: string[] = ['ALL', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];
  loading = true;
  bookingInProgressId: number | null = null;
  bookingToast: { message: string; isWaitlist?: boolean } | null = null;

  constructor(private bookingApi: BookingApiService) {}

  ngOnInit(): void {
    this.loadClasses();
  }

  loadClasses(): void {
    this.loading = true;
    this.bookingApi.getMemberClasses().subscribe({
      next: (data) => {
        this.classes = data;
        this.filterByDay(this.selectedDay);
        this.loading = false;
      },
      error: () => {
        // Fallback for demonstration
        this.classes = [
          {
            id: 1,
            name: 'Vinyasa Flow Yoga',
            coachName: 'Sarah Johnson',
            room: 'Studio Zen',
            dayOfWeek: 'MONDAY',
            startTime: '07:30',
            endTime: '08:30',
            capacity: 20,
            availableSpots: 6,
            isFull: false
          },
          {
            id: 2,
            name: 'Metabolic Conditioning (MetCon)',
            coachName: 'Marcus Aurelius',
            room: 'CrossFit Arena',
            dayOfWeek: 'MONDAY',
            startTime: '18:00',
            endTime: '19:00',
            capacity: 15,
            availableSpots: 0,
            isFull: true
          },
          {
            id: 3,
            name: 'High Intensity Spin & Cycle',
            coachName: 'Chloe Bennett',
            room: 'RPM Arena',
            dayOfWeek: 'WEDNESDAY',
            startTime: '08:00',
            endTime: '09:00',
            capacity: 25,
            availableSpots: 14,
            isFull: false
          },
          {
            id: 4,
            name: 'Core Dynamics & Mobility',
            coachName: 'David Beckham',
            room: 'Functional Turf',
            dayOfWeek: 'FRIDAY',
            startTime: '17:00',
            endTime: '18:00',
            capacity: 18,
            availableSpots: 8,
            isFull: false
          }
        ];
        this.filterByDay(this.selectedDay);
        this.loading = false;
      }
    });
  }

  filterByDay(day: string): void {
    this.selectedDay = day;
    if (day === 'ALL') {
      this.filteredClasses = [...this.classes];
    } else {
      this.filteredClasses = this.classes.filter(c => c.dayOfWeek === day);
    }
  }

  book(c: ClassSession): void {
    this.bookingInProgressId = c.id;
    this.bookingApi.bookClass(c.id).subscribe({
      next: (res) => {
        this.bookingInProgressId = null;
        this.bookingToast = {
          message: res.message || 'Session reserved successfully! See you in class.',
          isWaitlist: res.status === 'WAITLIST'
        };
        setTimeout(() => (this.bookingToast = null), 5000);
        this.loadClasses();
      },
      error: (err) => {
        this.bookingInProgressId = null;
        this.bookingToast = {
          message: err.error?.error || 'Booking could not be completed. Please try again.',
          isWaitlist: false
        };
        setTimeout(() => (this.bookingToast = null), 4000);
      }
    });
  }
}
