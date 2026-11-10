export interface EquipmentItem {
  id: number;
  qrCode: string;
  name: string;
  category: string;
  targetMuscles: string;
  modelNumber: string;
  imageUrl?: string;
  instructions?: string;
  videoTutorial?: string;
}

export interface PrEntry {
  equipmentName: string;
  category: string;
  weightKg: number;
  reps: number;
  estimated1Rm: number;
  seatSetting: string | null;
  achievedAt: string;
}

export interface PrVaultResponse {
  memberId: number;
  vault: Record<string, PrEntry[]>;
}

export interface LogSetPayload {
  equipmentId: number;
  weightKg: number;
  reps: number;
  sets: number;
  seatSetting?: string;
  notes?: string;
}

export interface LogSetResponse {
  success: boolean;
  message: string;
  log: {
    id: number;
    equipment: string;
    weightKg: number;
    reps: number;
    sets: number;
    estimated1Rm: number;
    isPR: boolean;
    seatSetting?: string;
    loggedAt: string;
  };
}

