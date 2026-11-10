export type BookingStatus = 'CONFIRMED' | 'WAITLIST' | 'CANCELLED';

export interface BookingMember {
  id: number;
  email: string;
  fullName: string;
}

export interface Booking {
  id: number;
  classId: number;
  className: string;
  coachName?: string;
  room?: string;
  dayOfWeek?: string;
  time?: string;
  memberId?: number;
  memberName?: string;
  memberEmail?: string;
  member?: BookingMember;
  status: BookingStatus;
  bookedAt: string;
  waitlistPosition?: number;
}

export interface ClassBooking {
  bookingId?: number;
  id?: number;
  memberId?: number;
  memberName?: string;
  memberEmail?: string;
  member?: BookingMember;
  status: BookingStatus;
  bookedAt: string;
  waitlistPosition?: number;
  position?: number;
}

export interface BookClassResponse {
  message: string;
  booking: {
    id: number;
    classId: number;
    className: string;
    status: BookingStatus;
    dayOfWeek?: string;
    time?: string;
    bookedAt: string;
    waitlistPosition?: number;
  };
}
