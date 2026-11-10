export interface User {
  id: number | string;
  email: string;
  fullName: string;
  role: 'ROLE_ADMIN' | 'ROLE_RECEPTIONIST' | 'ROLE_COACH' | 'ROLE_MEMBER';
  status: 'ACTIVE' | 'INACTIVE';
  createdAt?: string | Date;
}

/**
 * Backend response for POST /api/auth/login
 * The backend uses session-based auth (Symfony Security) — no JWT token.
 */
export interface LoginResponse {
  message: string;
  user: User;
}

/**
 * Backend response for POST /api/auth/register
 */
export interface RegisterResponse {
  message: string;
  user: User;
}

export interface RegisterRequest {
  email: string;
  password: string;
  fullName: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface UpdateProfileRequest {
  fullName?: string;
  password?: string;
}
