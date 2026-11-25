import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import QRCode from 'qrcode';
import { AttendanceApiService } from '../../attendance-api.service';

@Component({
  selector: 'app-gym-entry-qr',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './gym-entry-qr.component.html',
  styleUrl: './gym-entry-qr.component.scss',
})
export class GymEntryQrComponent implements OnInit, OnDestroy {
  private attendanceService = inject(AttendanceApiService);

  qrDataUrl: string | null = null;
  errorMessage: string | null = null;
  now = new Date();

  private clockTimer: ReturnType<typeof setInterval> | null = null;

  ngOnInit(): void {
    this.attendanceService.getEntryQr().subscribe({
      next: async (res) => {
        try {
          this.qrDataUrl = await QRCode.toDataURL(res.payload, {
            width: 720,
            margin: 2,
            color: { dark: '#0f172a', light: '#ffffff' },
            errorCorrectionLevel: 'M',
          });
        } catch {
          this.errorMessage = 'Could not render the entrance QR code.';
        }
      },
      error: () => {
        this.errorMessage = 'Could not load the GymUp entrance QR code from the server.';
      },
    });

    this.clockTimer = setInterval(() => (this.now = new Date()), 1000);
  }

  ngOnDestroy(): void {
    if (this.clockTimer !== null) {
      clearInterval(this.clockTimer);
    }
  }
}
