import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { BookingApiService } from '../../booking-api.service';
import { DayOfWeek } from '../../../attendance/shared/streak.model';

@Component({
  selector: 'app-class-form',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './class-form.component.html',
  styleUrl: './class-form.component.scss'
})
export class ClassFormComponent implements OnInit {
  isEditMode = false;
  classId: number | null = null;
  loading = false;
  submitting = false;
  error: string | null = null;
  coaches: { id: number; fullName: string; email: string }[] = [];

  daysOfWeek: DayOfWeek[] = [
    'MONDAY',
    'TUESDAY',
    'WEDNESDAY',
    'THURSDAY',
    'FRIDAY',
    'SATURDAY',
    'SUNDAY'
  ];

  formData = {
    name: '',
    coachId: 6,
    room: 'Studio A (Main)',
    dayOfWeek: 'MONDAY' as DayOfWeek,
    startTime: '09:00',
    endTime: '10:00',
    capacity: 20
  };

  constructor(
    private bookingApi: BookingApiService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.bookingApi.getCoaches().subscribe({
      next: (coaches) => {
        this.coaches = coaches;
        if (!this.isEditMode && coaches.length > 0) {
          this.formData.coachId = coaches[0].id;
        }
      }
    });

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.isEditMode = true;
      this.classId = Number(idParam);
      this.loading = true;
      this.bookingApi.getClass(this.classId).subscribe({
        next: (cls) => {
          this.formData.name = cls.name;
          this.formData.coachId = cls.coachId || 6;
          this.formData.room = cls.room;
          this.formData.dayOfWeek = cls.dayOfWeek as DayOfWeek;
          this.formData.startTime = cls.startTime;
          this.formData.endTime = cls.endTime;
          this.formData.capacity = cls.capacity;
          this.loading = false;
        },
        error: () => {
          this.loading = false;
          this.error = 'Failed to load class session details.';
        }
      });
    }
  }

  onSubmit(): void {
    this.submitting = true;
    this.error = null;

    if (this.isEditMode && this.classId) {
      this.bookingApi.updateClass(this.classId, this.formData).subscribe({
        next: () => {
          this.submitting = false;
          this.router.navigate(['/admin/classes']);
        },
        error: (err) => {
          this.submitting = false;
          this.error = err.error?.error || 'Failed to update class session.';
        }
      });
    } else {
      this.bookingApi.createClass(this.formData).subscribe({
        next: () => {
          this.submitting = false;
          this.router.navigate(['/admin/classes']);
        },
        error: (err) => {
          this.submitting = false;
          this.error = err.error?.error || 'Failed to schedule class session.';
        }
      });
    }
  }
}
