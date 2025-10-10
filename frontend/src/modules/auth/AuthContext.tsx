import { createContext, PropsWithChildren, useContext, useEffect, useMemo, useState } from 'react';

interface AuthState {
  token: string | null;
  role: 'owner' | 'manager' | 'staff' | null;
}

interface AuthContextValue extends AuthState {
  login: (token: string, role: AuthState['role']) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: PropsWithChildren) {
  const [token, setToken] = useState<string | null>(() => localStorage.getItem('token'));
  const [role, setRole] = useState<AuthState['role']>(() => (localStorage.getItem('role') as any) ?? null);

  useEffect(() => {
    if (token) localStorage.setItem('token', token); else localStorage.removeItem('token');
  }, [token]);
  useEffect(() => {
    if (role) localStorage.setItem('role', role); else localStorage.removeItem('role');
  }, [role]);

  const value = useMemo<AuthContextValue>(() => ({
    token,
    role,
    login: (t, r) => { setToken(t); setRole(r); },
    logout: () => { setToken(null); setRole(null); },
  }), [token, role]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
