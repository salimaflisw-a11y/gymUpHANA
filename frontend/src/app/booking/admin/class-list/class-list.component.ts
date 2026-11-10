import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { BookingApiService } from '../../booking-api.service';
import { ClassSession } from '../../shared/class.model';

@Component({
  selector: 'app-class-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './class-list.component.html',
  styleUrl: './class-list.component.scss'
})
export class ClassListComponent implements OnInit {
  classes: ClassSession[] = [];
  loading = true;
  error = '';
  successMessage = '';

  constructor(
    private bookingApi: BookingApiService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadClasses();
  }

  loadClasses(): void {
    this.loading = true;
    this.error = '';
    this.bookingApi.getClasses().subscribe({
      next: (data) => {
        this.classes = data;
        this.loading = false;
      },
      error: (err) => {
        this.error = 'Failed to load studio classes';
        this.loading = false;
      }
    });
  }

  createClass(): void {
    this.router.navigate(['/admin/classes/new']);
  }

  deleteClass(id: number): void {
    if (confirm('Are you sure you want to delete this class session?')) {
      this.bookingApi.deleteClass(id).subscribe({
        next: () => {
          this.successMessage = 'Class session removed successfully.';
          setTimeout(() => (this.successMessage = ''), 3000);
          this.loadClasses();
        },
        error: (err) => {
          this.error = err.error?.error || 'Failed to delete class session.';
        }
      });
    }
  }
}
