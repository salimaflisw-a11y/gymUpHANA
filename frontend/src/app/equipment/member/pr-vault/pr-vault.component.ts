import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { EquipmentApiService } from '../../../core/services/equipment-api.service';
import {
  EquipmentItem,
  PrEntry,
  LogSetPayload
} from '../../../core/models/equipment.model';

@Component({
  selector: 'app-pr-vault',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './pr-vault.component.html',
  styleUrl: './pr-vault.component.scss'
})
export class PrVaultComponent implements OnInit {
  vault: Record<string, PrEntry[]> = {};
  catalog: EquipmentItem[] = [];
  categories: string[] = ['ALL', 'CHEST', 'BACK', 'LEGS', 'SHOULDERS', 'ARMS', 'CORE'];
  selectedCategory: string = 'ALL';
  searchQuery: string = '';

  loading = true;
  showLogModal = false;
  submittingLog = false;
  prSuccessCelebration: string | null = null;
  toastMessage: { text: string; type: 'success' | 'error' } | null = null;

  // New Log Form State
  newLog: LogSetPayload = {
    equipmentId: 0,
    weightKg: 60,
    reps: 8,
    sets: 3,
    seatSetting: '',
    notes: ''
  };

  constructor(private equipmentApi: EquipmentApiService) {}

  ngOnInit(): void {
    this.loadData();
  }

  loadData(): void {
    this.loading = true;
    this.equipmentApi.getCatalog().subscribe({
      next: (items) => {
        this.catalog = items;
        if (items.length > 0 && this.newLog.equipmentId === 0) {
          this.newLog.equipmentId = items[0].id;
        }
      },
      error: () => {}
    });

    this.equipmentApi.getPrVault().subscribe({
      next: (res) => {
        this.vault = res.vault || {};
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  get vaultCategories(): string[] {
    return Object.keys(this.vault);
  }

  get totalPrCount(): number {
    let count = 0;
    for (const cat of Object.keys(this.vault)) {
      count += this.vault[cat].length;
    }
    return count;
  }

  get filteredCatalog(): EquipmentItem[] {
    return this.catalog.filter((item) => {
      const matchCat = this.selectedCategory === 'ALL' || item.category.toUpperCase() === this.selectedCategory;
      const matchQuery = !this.searchQuery || 
        item.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        item.targetMuscles.toLowerCase().includes(this.searchQuery.toLowerCase());
      return matchCat && matchQuery;
    });
  }

  get calculatedEstimated1Rm(): number {
    if (this.newLog.weightKg <= 0 || this.newLog.reps <= 0) return 0;
    // Epley Formula: 1RM = Weight * (1 + Reps / 30)
    return Math.round(this.newLog.weightKg * (1 + this.newLog.reps / 30));
  }

  openLogModal(equipmentId?: number): void {
    if (equipmentId) {
      this.newLog.equipmentId = equipmentId;
    } else if (this.catalog.length > 0) {
      this.newLog.equipmentId = this.catalog[0].id;
    }
    this.showLogModal = true;
  }

  closeLogModal(): void {
    this.showLogModal = false;
    this.prSuccessCelebration = null;
  }

  submitLog(): void {
    if (this.newLog.equipmentId <= 0 || this.newLog.weightKg <= 0 || this.newLog.reps <= 0) {
      this.showToast('Please enter valid machine, weight, and reps.', 'error');
      return;
    }

    this.submittingLog = true;
    this.equipmentApi.logSet(this.newLog).subscribe({
      next: (res) => {
        this.submittingLog = false;
        if (res.log?.isPR) {
          this.prSuccessCelebration = `🎉 NEW ALL-TIME PR! ${res.log.equipment}: ${res.log.weightKg}kg x ${res.log.reps} reps (Estimated 1RM: ${res.log.estimated1Rm}kg)!`;
        } else {
          this.showToast('Workout set logged successfully!', 'success');
          this.closeLogModal();
        }
        // Refresh vault
        this.equipmentApi.getPrVault().subscribe({
          next: (v) => { this.vault = v.vault || {}; }
        });
      },
      error: (err) => {
        this.submittingLog = false;
        this.showToast(err.error?.error || 'Failed to log set.', 'error');
      }
    });
  }

  private showToast(text: string, type: 'success' | 'error'): void {
    this.toastMessage = { text, type };
    setTimeout(() => {
      this.toastMessage = null;
    }, 4500);
  }
}

