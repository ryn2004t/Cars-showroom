const DEFAULT_TIMEOUT_MS = 15000;

export class ApiError extends Error {
  constructor(public status: number, message: string) {
    super(message);
    this.name = 'ApiError';
  }
}

function withTimeout<T>(promise: Promise<T>, ms = DEFAULT_TIMEOUT_MS): Promise<T> {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error('Request timed out')), ms);
    promise.then(
      (value) => {
        clearTimeout(timer);
        resolve(value);
      },
      (err) => {
        clearTimeout(timer);
        reject(err);
      }
    );
  });
}

export interface ApiClientOptions {
  baseUrl?: string;
  getToken?: () => string | null;
}

export function createApiClient(options: ApiClientOptions = {}) {
  const baseUrl = options.baseUrl ?? (import.meta.env.VITE_API_BASE_URL as string | undefined) ?? '/api';
  const getToken = options.getToken ?? (() => localStorage.getItem('token'));

  async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
    const token = getToken();
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init.headers,
    };

    const response = await withTimeout(fetch(`${baseUrl}${path}`, { ...init, headers }));
    const isJson = response.headers.get('content-type')?.includes('application/json');
    const payload = isJson ? await response.json() : undefined;

    if (!response.ok) {
      const message = (payload && (payload.message || payload.error)) || `HTTP ${response.status}`;
      throw new ApiError(response.status, message);
    }

    return payload as T;
  }

  return {
    login: (data: { email: string; password: string }) =>
      request<{ token: string; userId: string; role: string }>('/auth/login', {
        method: 'POST',
        body: JSON.stringify(data),
      }),
    getJobs: () => request<any[]>('/jobs', { method: 'GET' }),
    getCustomers: () => request<any[]>('/customers', { method: 'GET' }),
  };
}
