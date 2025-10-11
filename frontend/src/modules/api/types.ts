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

export interface InvoiceSummary {
  id: string;
  invoiceNumber: string;
  jobId: string;
  totalAmount: number;
  status: 'draft' | 'sent' | 'paid' | 'void';
  createdAt: string; // ISO date
}

export interface Promotion {
  id: string;
  name: string;
  description?: string;
  discountPercent?: number;
  activeFrom?: string | null;
  activeTo?: string | null;
}

export interface ReviewItem {
  id: string;
  rating: number;
  comment?: string;
  customerId: string;
  createdAt: string;
}

export interface AnalyticsSummary {
  jobsTotal: number;
  jobsCompleted: number;
  revenueTotal: number;
  avgRating: number;
}
