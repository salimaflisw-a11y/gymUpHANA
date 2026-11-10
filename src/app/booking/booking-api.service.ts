import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, map, of } from 'rxjs';
import {
  ClassSession,
  CreateClassRequest,
  UpdateClassRequest,
  ClassBooking,
  BookClassResponse
} from './shared/class.model';
import { Booking } from './shared/booking.model';

@Injectable({
  providedIn: 'root'
})
export class BookingApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = '/api';

  // Admin endpoints
  getClasses(): Observable<ClassSession[]> {
    return this.http.get<any[]>(`${this.baseUrl}/admin/classes`).pipe(
      map((classes: any[]) => {
        return (classes || []).map((c: any) => ({
          ...c,
          coachId: c.coach?.id ?? c.coachId,
          coachName: c.coach?.fullName ?? c.coachName,
          bookingCount: c.confirmedCount ?? c.bookingCount ?? 0,
          confirmedCount: c.confirmedCount ?? c.bookingCount ?? 0,
          waitlistCount: c.waitlistCount ?? 0,
        }));
      }),
      catchError((error) => {
        throw error;
        const fallback: ClassSession[] = [
          {
            id: 1,
            name: 'Yoga Flow',
            coachId: 5,
            coachName: 'Sarah Johnson',
            coach: { id: 5, fullName: 'Sarah Johnson' },
            room: 'Room A',
            dayOfWeek: 'MONDAY',
            startTime: '09:00',
            endTime: '10:00',
            capacity: 20,
            bookingCount: 15,
            confirmedCount: 15,
            waitlistCount: 2,
            isFull: false
          },
          {
            id: 2,
            name: 'HIIT Training',
            coachId: 6,
            coachName: 'Mike Smith',
            coach: { id: 6, fullName: 'Mike Smith' },
            room: 'Room B',
            dayOfWeek: 'WEDNESDAY',
            startTime: '18:00',
            endTime: '19:00',
            capacity: 15,
            bookingCount: 15,
            confirmedCount: 15,
            waitlistCount: 5,
            isFull: true
          }
        ];
        return of(fallback);
      })
    );
  }

  createClass(data: CreateClassRequest): Observable<ClassSession> {
    return this.http.post<ClassSession>(`${this.baseUrl}/admin/classes`, data);
  }

  updateClass(id: number, data: UpdateClassRequest): Observable<ClassSession> {
    return this.http.patch<ClassSession>(`${this.baseUrl}/admin/classes/${id}`, data);
  }

  deleteClass(id: number): Observable<any> {
    return this.http.delete(`${this.baseUrl}/admin/classes/${id}`);
  }

  getClassBookings(classId: number): Observable<ClassBooking[]> {
    return this.http.get<any>(`${this.baseUrl}/admin/classes/${classId}/bookings`).pipe(
      map((res: any) => {
        const list = res?.bookings || (Array.isArray(res) ? res : []);
        return list.map((b: any) => ({
          id: b.id,
          classId: res?.classId ?? classId,
          className: res?.className ?? '',
          memberId: b.member?.id ?? b.memberId,
          memberEmail: b.member?.email ?? b.memberEmail,
          memberFullName: b.member?.fullName ?? b.memberFullName,
          member: b.member,
          bookingDate: b.bookedAt ?? b.bookingDate,
          bookedAt: b.bookedAt,
          status: b.status,
        }));
      }),
      catchError((error) => { throw error; })
    );
  }

  getClassWaitlist(classId: number): Observable<ClassBooking[]> {
    return this.http.get<any>(`${this.baseUrl}/admin/classes/${classId}/waitlist`).pipe(
      map((res: any) => {
        const list = res?.waitlist || (Array.isArray(res) ? res : []);
        return list.map((b: any) => ({
          id: b.id,
          classId: res?.classId ?? classId,
          className: res?.className ?? '',
          memberId: b.member?.id ?? b.memberId,
          memberEmail: b.member?.email ?? b.memberEmail,
          memberFullName: b.member?.fullName ?? b.memberFullName,
          member: b.member,
          bookedAt: b.bookedAt,
          bookingDate: b.bookedAt,
          status: 'WAITLIST',
          position: b.position,
        }));
      }),
      catchError((error) => { throw error; })
    );
  }

  // Coach endpoints
  getCoachSchedule(): Observable<ClassSession[]> {
    return this.http.get<any[]>(`${this.baseUrl}/coach/schedule`).pipe(
      map((classes: any[]) => {
        return (classes || []).map((c: any) => ({
          ...c,
          bookingCount: c.enrolledCount ?? 0,
          confirmedCount: c.enrolledCount ?? 0,
        }));
      }),
      catchError((error) => { throw error; })
    );
  }

  getClassMembers(classId: number): Observable<ClassBooking[]> {
    return this.http.get<any>(`${this.baseUrl}/coach/classes/${classId}/members`).pipe(
      map((res: any) => {
        const members = res?.members || (Array.isArray(res) ? res : []);
        return members.map((m: any) => ({
          id: m.bookingId ?? m.id,
          memberId: m.memberId,
          memberFullName: m.fullName,
          memberEmail: m.email,
          bookedAt: m.bookedAt,
          status: 'CONFIRMED',
        }));
      }),
      catchError((error) => { throw error; })
    );
  }

  // Member endpoints
  getMemberClasses(): Observable<ClassSession[]> {
    return this.http.get<any[]>(`${this.baseUrl}/member/classes`).pipe(
      map((classes: any[]) => {
        return (classes || []).map((c: any) => ({
          ...c,
          bookingCount: c.capacity - (c.availableSpots ?? 0),
          confirmedCount: c.capacity - (c.availableSpots ?? 0),
        }));
      }),
      catchError((error) => { throw error; })
    );
  }

  bookClass(classId: number): Observable<BookClassResponse> {
    return this.http.post<BookClassResponse>(`${this.baseUrl}/member/classes/${classId}/book`, {}).pipe(
      map((res: any) => ({
        bookingId: res?.booking?.id,
        classId: res?.booking?.classId ?? classId,
        className: res?.booking?.className ?? '',
        status: res?.booking?.status ?? 'CONFIRMED',
        message: res?.message ?? 'Booking processed',
        booking: res?.booking
      }))
    );
  }

  cancelBooking(bookingId: number): Observable<any> {
    return this.http.post(`${this.baseUrl}/member/bookings/${bookingId}/cancel`, {});
  }

  getMemberBookings(): Observable<Booking[]> {
    return this.http.get<any[]>(`${this.baseUrl}/member/bookings`).pipe(
      map((bookings: any[]) => {
        return (bookings || []).map((b: any) => ({
          id: b.id,
          classId: b.classId,
          className: b.className,
          coachName: b.coachName,
          room: b.room,
          dayOfWeek: b.dayOfWeek,
          time: b.time,
          status: b.status,
          bookedAt: b.bookedAt,
        }));
      }),
      catchError((error) => { throw error; })
    );
  }
}
