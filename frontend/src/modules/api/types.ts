export type UserRole = 'owner' | 'manager' | 'staff';

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  userId: string;
  role: UserRole;
}

export interface CustomerSummary {
  id: string;
  name: string;
  phone?: string;
}

export interface VehicleSummary {
  id: string;
  vin: string;
  make?: string;
  model?: string;
  year?: number;
}

export interface JobSummary {
  id: string;
  title: string;
  scheduledAt: string; // ISO date
  status: 'requested' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled';
  customerId: string;
}
