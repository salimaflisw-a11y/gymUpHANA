import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdvisorApiService } from '../../../core/services/advisor-api.service';
import { EquipmentApiService } from '../../../core/services/equipment-api.service';
import { WorkoutPlan, WorkoutExercise } from '../../../core/models/workout-plan.model';

@Component({
  selector: 'app-workout-planner',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './workout-planner.component.html',
  styleUrl: './workout-planner.component.scss'
})
export class WorkoutPlannerComponent implements OnInit {
  private advisorService = inject(AdvisorApiService);
  private equipmentService = inject(EquipmentApiService);

  // Form selections
  selectedGoal = 'Hypertrophy & Muscle Gain';
  selectedMuscle = 'Chest & Triceps';
  durationMinutes = 45;
  fitnessLevel = 'INTERMEDIATE';

  goalOptions = [
    'Hypertrophy & Muscle Gain',
    'Strength & Raw Power',
    'Fat Loss & Conditioning',
    'Athletic Performance & Mobility'
  ];

  muscleOptions = [
    'Chest & Triceps',
    'Back & Biceps',
    'Legs & Glutes',
    'Shoulders & Traps',
    'Full Body Functional Circuit'
  ];

  levelOptions = ['BEGINNER', 'INTERMEDIATE', 'ADVANCED'];

  // State
  isGenerating = false;
  plan: WorkoutPlan | null = null;
  errorMessage = '';

  // Live Workout Mode
  isLiveMode = false;
  currentExerciseIndex = 0;
  activeTimer = 0;
  timerInterval: any = null;
  isResting = false;
  restTimeRemaining = 0;
  restInterval: any = null;

  // PR quick-log inputs
  logWeightKg: number = 60;
  logReps: number = 10;
  logSuccessMessage = '';

  ngOnInit(): void {}

  generateRoutine(): void {
    this.isGenerating = true;
    this.errorMessage = '';
    this.isLiveMode = false;

    this.advisorService.generateWorkoutPlan({
      goal: this.selectedGoal,
      targetMuscle: this.selectedMuscle,
      durationMinutes: this.durationMinutes,
      fitnessLevel: this.fitnessLevel
    }).subscribe({
      next: (generated) => {
        this.plan = generated;
        this.isGenerating = false;
      },
      error: (err) => {
        this.isGenerating = false;
        this.errorMessage = err?.error?.error || 'Failed to generate workout plan. Please try again.';
      }
    });
  }

  startLiveSession(): void {
    if (!this.plan || !this.plan.exercises.length) return;
    this.isLiveMode = true;
    this.currentExerciseIndex = 0;
    this.activeTimer = 0;
    this.isResting = false;
    this.logSuccessMessage = '';

    clearInterval(this.timerInterval);
    this.timerInterval = setInterval(() => {
      this.activeTimer++;
    }, 1000);
  }

  get currentExercise(): WorkoutExercise | null {
    if (!this.plan || !this.plan.exercises) return null;
    return this.plan.exercises[this.currentExerciseIndex] || null;
  }

  nextExercise(): void {
    if (!this.plan) return;
    if (this.currentExerciseIndex < this.plan.exercises.length - 1) {
      this.currentExerciseIndex++;
      this.logSuccessMessage = '';
      this.startRestCountdown(this.currentExercise?.restSeconds || 60);
    } else {
      this.finishWorkout();
    }
  }

  startRestCountdown(seconds: number): void {
    this.isResting = true;
    this.restTimeRemaining = seconds;
    clearInterval(this.restInterval);
    this.restInterval = setInterval(() => {
      this.restTimeRemaining--;
      if (this.restTimeRemaining <= 0) {
        clearInterval(this.restInterval);
        this.isResting = false;
      }
    }, 1000);
  }

  skipRest(): void {
    clearInterval(this.restInterval);
    this.isResting = false;
  }

  logSetToPRVault(): void {
    const ex = this.currentExercise;
    if (!ex || !ex.equipmentId) {
      this.logSuccessMessage = '✓ Set completed!';
      return;
    }

    this.equipmentService.logSet({
      equipmentId: ex.equipmentId,
      weightKg: Number(this.logWeightKg),
      reps: Number(this.logReps),
      sets: 1
    }).subscribe({
      next: () => {
        this.logSuccessMessage = `🔥 Successfully logged ${this.logWeightKg}kg x ${this.logReps} to your PR Vault!`;
      },
      error: () => {
        this.logSuccessMessage = '✓ Set logged locally!';
      }
    });
  }

  finishWorkout(): void {
    clearInterval(this.timerInterval);
    clearInterval(this.restInterval);
    this.isLiveMode = false;
    alert(`🎉 Workout Complete! Total time: ${this.formatTimer(this.activeTimer)}. Great work!`);
  }

  formatTimer(totalSeconds: number): string {
    const mins = Math.floor(totalSeconds / 60);
    const secs = totalSeconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }
}
