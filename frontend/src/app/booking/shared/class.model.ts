import { DayOfWeek } from '../../attendance/shared/streak.model';

export interface ClassSessionCoach {
  id: number;
  fullName: string;
}

export interface ClassSession {
  id: number;
  name: string;
  coachId?: number;
  coachName?: string;
  coach?: ClassSessionCoach;
  room: string;
  dayOfWeek: DayOfWeek;
  startTime: string;
  endTime: string;
  capacity: number;
  bookingCount?: number;
  confirmedCount?: number;
  enrolledCount?: number;
  waitlistCount?: number;
  availableSpots?: number;
  isFull?: boolean;
}

export interface CreateClassRequest {
  name: string;
  coachId: number;
  room: string;
  dayOfWeek: DayOfWeek;
  startTime: string;
  endTime: string;
  capacity: number;
}

export interface UpdateClassRequest {
  name?: string;
  coachId?: number;
  room?: string;
  dayOfWeek?: DayOfWeek;
  startTime?: string;
  endTime?: string;
  capacity?: number;
}

export interface ClassBookingMember {
  id: number;
  email: string;
  fullName: string;
}

export interface ClassBooking {
  id: number;
  classId?: number;
  className?: string;
  memberId?: number;
  memberEmail?: string;
  memberFullName?: string;
  member?: ClassBookingMember;
  bookingDate?: string;
  bookedAt?: string;
  status: 'CONFIRMED' | 'WAITLIST' | 'CANCELLED';
  position?: number;
}

export interface BookClassResponse {
  bookingId?: number;
  classId?: number;
  className?: string;
  status?: 'CONFIRMED' | 'WAITLIST';
  message: string;
  booking?: {
    id: number;
    classId: number;
    className: string;
    status: 'CONFIRMED' | 'WAITLIST' | string;
    dayOfWeek?: string;
    time?: string;
    bookedAt: string;
  };
}
