export type PlanAccessScope = 'OPEN_GYM' | 'CLASSES' | 'BOTH';

export interface Plan {
  id: number;
  name: string;
  price: number;
  durationDays: number;
  accessScope: PlanAccessScope;
  maxMembers: number | null;
  isActive: boolean;
  currentMembersCount?: number;
}

export interface SubscriptionMember {
  id: number;
  email: string;
  fullName: string;
}

export interface SubscriptionPlan {
  id: number;
  name: string;
  price: number;
  durationDays?: number;
  accessScope: PlanAccessScope;
}

export interface Subscription {
  id: number;
  planId?: number;
  planName?: string;
  memberId?: number;
  memberEmail?: string;
  memberFullName?: string;
  member?: SubscriptionMember;
  plan?: SubscriptionPlan;
  status: 'PENDING' | 'ACTIVE' | 'EXPIRED' | 'CANCELLED';
  startDate: string;
  endDate: string;
  price?: number;
  durationDays?: number;
  accessScope?: PlanAccessScope;
  isActive?: boolean;
}

export interface ExpiringSubscription {
  subscriptionId: number;
  member: SubscriptionMember;
  planName: string;
  endDate: string;
  daysRemaining: number;
}

export interface SubscriptionStatusResponse {
  hasActiveSubscription: boolean;
  subscriptionId?: number;
  plan?: {
    id: number;
    name: string;
    accessScope: PlanAccessScope;
  };
  status?: string;
  startDate?: string;
  endDate?: string;
  daysRemaining?: number;
  isExpiringSoon?: boolean;
  message?: string;
}

export interface SubscribeResponse {
  message: string;
  subscription: {
    id: number;
    planId: number;
    planName: string;
    price: number;
    status: string;
  };
}

export interface CreatePlanRequest {
  name: string;
  price: number;
  durationDays: number;
  accessScope: PlanAccessScope;
  maxMembers?: number;
}

export interface UpdatePlanRequest {
  name?: string;
  price?: number;
  durationDays?: number;
  accessScope?: PlanAccessScope;
  maxMembers?: number;
  isActive?: boolean;
}
