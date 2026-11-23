import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import * as QRCode from 'qrcode';
import { AuthService } from '../../../auth/auth.service';
import { FacilityApiService } from '../../services/facility-api.service';
import {
  FacilityOccupancy,
  CrowdForecastResponse,
  DynamicAccessPass
} from '../../models/facility.model';

@Component({
  selector: 'app-member-layout',
  templateUrl: './member-layout.component.html',
  styleUrl: './member-layout.component.scss'
})
export class MemberLayoutComponent implements OnInit {
  occupancy: FacilityOccupancy | null = null;
  forecast: CrowdForecastResponse | null = null;
  accessPass: DynamicAccessPass | null = null;
  qrCodeDataUrl: string = '';

  showOccupancyModal = false;
  showPassModal = false;
  loadingPass = false;
  passError = '';

  constructor(
    private authService: AuthService,
    private router: Router,
    private facilityApi: FacilityApiService
  ) {}

  ngOnInit(): void {
    this.loadOccupancy();
  }

  loadOccupancy(): void {
    this.facilityApi.getLiveOccupancy().subscribe({
      next: (occ) => {
        this.occupancy = occ;
      },
      error: () => {}
    });
  }

  openOccupancyModal(): void {
    this.showOccupancyModal = true;
    if (!this.forecast) {
      this.facilityApi.getCrowdForecast().subscribe({
        next: (fc) => {
          this.forecast = fc;
        },
        error: () => {}
      });
    }
  }

  closeOccupancyModal(): void {
    this.showOccupancyModal = false;
  }

  openPassModal(): void {
    this.showPassModal = true;
    this.loadingPass = true;
    this.passError = '';
    this.qrCodeDataUrl = '';
    this.facilityApi.getDynamicAccessPass().subscribe({
      next: (pass) => {
        this.accessPass = pass;
        this.loadingPass = false;
        const payload = pass.qrData || (pass as any).qrRawPayload || `GYMUP://PASS/${(pass as any).passToken || pass.memberId}`;
        QRCode.toDataURL(payload, {
          width: 190,
          margin: 1,
          color: {
            dark: '#00f5ff',
            light: '#070b14'
          }
        }).then(url => {
          this.qrCodeDataUrl = url;
        }).catch(() => {
          this.qrCodeDataUrl = '';
        });
      },
      error: (err) => {
        this.passError = err.error?.error || 'Unable to generate dynamic pass. Ensure subscription is active.';
        this.loadingPass = false;
      }
    });
  }

  closePassModal(): void {
    this.showPassModal = false;
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  get userName(): string {
    return this.authService.getCurrentUser()?.fullName || 'Member';
  }

  get passSecurityHashSnippet(): string {
    return (this.accessPass?.securityHash || 'GYMUP-SEC-PASS').substring(0, 24);
  }
}
