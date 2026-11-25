import {
  Component,
  ElementRef,
  OnDestroy,
  ViewChild,
  inject,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import jsQR from 'jsqr';
import { AttendanceApiService } from '../../attendance-api.service';
import { CheckInResult } from '../../shared/streak.model';

type ScanState = 'idle' | 'starting' | 'scanning' | 'verifying';

@Component({
  selector: 'app-qr-checkin',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './qr-checkin.component.html',
  styleUrl: './qr-checkin.component.scss',
})
export class QrCheckinComponent implements OnDestroy {
  private attendanceService = inject(AttendanceApiService);

  @ViewChild('videoElement') private videoRef?: ElementRef<HTMLVideoElement>;
  @ViewChild('canvasElement') private canvasRef?: ElementRef<HTMLCanvasElement>;

  state: ScanState = 'idle';
  scanResult: CheckInResult | null = null;
  errorMessage: string | null = null;
  scannedCode: string | null = null;

  private stream: MediaStream | null = null;
  private animationFrame: number | null = null;

  get isCameraActive(): boolean {
    return this.state === 'scanning' || this.state === 'verifying';
  }

  get isBusy(): boolean {
    return this.state === 'starting' || this.state === 'verifying';
  }

  async startCamera(): Promise<void> {
    if (this.state !== 'idle') {
      return;
    }

    this.errorMessage = null;
    this.scannedCode = null;
    this.state = 'starting';

    if (!navigator.mediaDevices?.getUserMedia) {
      this.state = 'idle';
      this.errorMessage =
        'Camera access is not available in this browser. Use a modern browser over HTTPS or localhost.';
      return;
    }

    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false,
      });
    } catch (err: any) {
      this.state = 'idle';
      this.errorMessage = this.describeCameraError(err);
      return;
    }

    const video = this.videoRef?.nativeElement;
    if (!video) {
      this.stopCamera();
      this.errorMessage = 'Camera preview could not be initialised. Please retry.';
      return;
    }

    video.srcObject = this.stream;
    try {
      await video.play();
    } catch {
      this.stopCamera();
      this.errorMessage = 'Camera preview failed to start. Please retry.';
      return;
    }

    this.state = 'scanning';
    this.scanLoop();
  }

  stopCamera(): void {
    if (this.animationFrame !== null) {
      cancelAnimationFrame(this.animationFrame);
      this.animationFrame = null;
    }
    if (this.stream) {
      this.stream.getTracks().forEach((track) => track.stop());
      this.stream = null;
    }
    const video = this.videoRef?.nativeElement;
    if (video) {
      video.pause();
      video.srcObject = null;
    }
    if (this.state === 'scanning') {
      this.state = 'idle';
    }
  }

  reset(): void {
    this.stopCamera();
    this.state = 'idle';
    this.errorMessage = null;
    this.scannedCode = null;
    this.scanResult = null;
  }

  dismissResult(): void {
    this.scanResult = null;
  }

  ngOnDestroy(): void {
    this.stopCamera();
  }

  private scanLoop(): void {
    const video = this.videoRef?.nativeElement;
    const canvas = this.canvasRef?.nativeElement;

    if (this.state !== 'scanning' || !video || !canvas) {
      return;
    }

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      const width = video.videoWidth;
      const height = video.videoHeight;

      if (width > 0 && height > 0) {
        const scale = Math.min(1, 640 / Math.max(width, height));
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);

        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (ctx) {
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
          const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'attemptBoth',
          });

          if (code && code.data) {
            this.handleDetected(code.data);
            return;
          }
        }
      }
    }

    this.animationFrame = requestAnimationFrame(() => this.scanLoop());
  }

  private handleDetected(rawCode: string): void {
    const code = rawCode.trim();
    this.stopCamera();
    this.state = 'verifying';
    this.scannedCode = code;
    this.errorMessage = null;

    this.attendanceService.processCheckIn(code).subscribe({
      next: (res) => {
        this.state = 'idle';
        this.scanResult = res;
      },
      error: (err) => {
        this.state = 'idle';
        this.errorMessage =
          err?.error?.error ||
          'Check-in failed: the scanned code was rejected. Point the camera at the GymUp entrance QR code and try again.';
      },
    });
  }

  private describeCameraError(err: any): string {
    const name = err?.name ?? '';
    if (name === 'NotAllowedError' || name === 'SecurityError') {
      return 'Camera permission was denied. Allow camera access in your browser, then try again.';
    }
    if (name === 'NotFoundError' || name === 'OverconstrainedError') {
      return 'No usable camera was found on this device.';
    }
    if (name === 'NotReadableError') {
      return 'The camera is already in use by another application.';
    }
    return 'Unable to open the camera. Please retry or check your device settings.';
  }
}
