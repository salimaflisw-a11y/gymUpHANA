export interface AtRiskMember {
  memberId: number;
  name: string;
  email: string;
  plan: string;
  daysSinceLastVisit: number;
  lastVisitDate: string;
  riskLevel: 'MODERATE_RISK' | 'HIGH_RISK' | 'CRITICAL_CHURN';
  suggestedAction: string;
}

export interface NudgeResponse {
  success: boolean;
  message: string;
}

