export interface WorkoutExercise {
  name: string;
  equipmentId?: number | null;
  equipmentName?: string;
  targetMuscle?: string;
  sets: number;
  reps: string;
  restSeconds: number;
  coachingTip?: string;
  rpe?: string;
}

export interface WorkoutPhase {
  name: string;
  duration: string;
  notes: string;
}

export interface WorkoutPlan {
  title: string;
  goal: string;
  targetMuscle: string;
  estimatedDurationMinutes: number;
  difficulty: string;
  warmup: WorkoutPhase[];
  exercises: WorkoutExercise[];
  cooldown: WorkoutPhase[];
  coachAdvice?: string;
}

export interface GenerateWorkoutRequest {
  goal: string;
  targetMuscle: string;
  durationMinutes: number;
  fitnessLevel: string;
}
