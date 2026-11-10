export interface RecoveryAssessment {
  readinessScore: number;
  recoveryState: 'PRIME_PERFORMANCE' | 'OPTIMAL' | 'FATIGUED_RECOVERY';
  recentVisitsPast3Days: number;
  muscleReadiness: {
    LEGS: 'READY' | 'RECOVERING';
    CHEST: 'READY' | 'RECOVERING';
    BACK: 'READY' | 'RECOVERING';
    SHOULDERS: 'READY' | 'RECOVERING';
    ARMS: 'READY' | 'RECOVERING';
    CORE: 'READY' | 'RECOVERING';
    [key: string]: 'READY' | 'RECOVERING';
  };
  recommendedWorkoutFocus: string;
  aiAdvice: string;
  warnings?: string[];
}

