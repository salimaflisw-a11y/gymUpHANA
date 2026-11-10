import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { BookingApiService } from '../../booking-api.service';
import { ClassSession, ClassBooking } from '../../shared/class.model';

@Component({
  selector: 'app-my-classes',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './my-classes.component.html',
  styleUrl: './my-classes.component.scss'
})
export class MyClassesComponent implements OnInit {
  classes: ClassSession[] = [];
  selectedClass: ClassSession | null = null;
  enrolledMembers: ClassBooking[] = [];
  loading = true;
  loadingMembers = false;
  error = '';

  constructor(private bookingApi: BookingApiService) {}

  ngOnInit(): void {
    this.loadCoachSchedule();
  }

  loadCoachSchedule(): void {
    this.loading = true;
    this.bookingApi.getCoachSchedule().subscribe({
      next: (data) => {
        this.classes = data;
        this.loading = false;
        if (data.length > 0) {
          this.selectClass(data[0]);
        }
      },
      error: () => {
        // Fallback for offline demo
        this.classes = [
          {
            id: 1,
            name: 'High Intensity Core & Conditioning',
            room: 'Studio Alpha',
            dayOfWeek: 'MONDAY',
            startTime: '08:00',
            endTime: '09:00',
            capacity: 20,
            bookingCount: 18,
            confirmedCount: 18,
          },
          {
            id: 2,
            name: 'Olympic Powerlifting Masterclass',
            room: 'Heavy Iron Zone',
            dayOfWeek: 'WEDNESDAY',
            startTime: '17:30',
            endTime: '18:45',
            capacity: 12,
            bookingCount: 12,
            confirmedCount: 12,
          }
        ];
        this.loading = false;
        this.selectClass(this.classes[0]);
      }
    });
  }

  selectClass(session: ClassSession): void {
    this.selectedClass = session;
    this.loadingMembers = true;
    this.bookingApi.getClassMembers(session.id).subscribe({
      next: (members) => {
        this.enrolledMembers = members;
        this.loadingMembers = false;
      },
      error: () => {
        this.enrolledMembers = [
          {
            id: 1,
            memberId: 101,
            memberFullName: 'Alexander Volkov',
            memberEmail: 'alex.v@example.com',
            status: 'CONFIRMED',
            bookedAt: '2026-11-01T10:00:00Z'
          },
          {
            id: 2,
            memberId: 102,
            memberFullName: 'Jessica Chen',
            memberEmail: 'jess.chen@example.com',
            status: 'CONFIRMED',
            bookedAt: '2026-11-01T11:20:00Z'
          }
        ];
        this.loadingMembers = false;
      }
    });
  }
}
