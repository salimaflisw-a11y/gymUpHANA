export interface FacilityZone {
  zoneName: string;
  density: number;
  status: 'AVAILABLE' | 'BUSY' | 'OPEN';
}

export interface FacilityOccupancy {
  activeMembersCount: number;
  maxCapacity: number;
  occupancyPercentage: number;
  status: 'QUIET' | 'OPTIMAL' | 'BUSY' | 'PEAK';
  statusMessage: string;
  zones: FacilityZone[];
  asOf: string;
}

export interface HourlyForecast {
  hour: number;
  label: string;
  predictedOccupancy: number;
  status: 'QUIET' | 'OPTIMAL' | 'BUSY' | 'PEAK';
}

export interface DayForecast {
  day: string;
  hourly: HourlyForecast[];
}

export interface CrowdForecastResponse {
  forecast: DayForecast[];
  peakHoursSummary: string;
  recommendedBestTime: string;
}

export interface DynamicAccessPass {
  passId: string;
  memberId: number;
  memberName: string;
  planName: string;
  planStatus: string;
  validUntil: string;
  qrData: string;
  tokenSeed: string;
  refreshedAt: string;
  securityHash: string;
}

