import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import {
  EquipmentItem,
  PrVaultResponse,
  LogSetPayload,
  LogSetResponse
} from '../models/equipment.model';

@Injectable({
  providedIn: 'root'
})
export class EquipmentApiService {
  private readonly apiUrl = '/api/equipment';

  constructor(private http: HttpClient) {}

  getCatalog(): Observable<EquipmentItem[]> {
    return this.http.get<EquipmentItem[]>(`${this.apiUrl}/catalog`, {
      withCredentials: true
    });
  }

  getPrVault(): Observable<PrVaultResponse> {
    return this.http.get<PrVaultResponse>(`${this.apiUrl}/pr-vault`, {
      withCredentials: true
    });
  }

  logSet(payload: LogSetPayload): Observable<LogSetResponse> {
    return this.http.post<LogSetResponse>(`${this.apiUrl}/log`, payload, {
      withCredentials: true
    });
  }

  scanEquipment(qrCode: string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/scan/${encodeURIComponent(qrCode)}`, {
      withCredentials: true
    });
  }
}

