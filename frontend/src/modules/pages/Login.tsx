import { FormEvent, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { createApiClient } from '../api/client';
import { useAuth } from '../auth/AuthContext';

export default function Login() {
  const [email, setEmail] = useState('demo@example.com');
  const [password, setPassword] = useState('DemoPass123!');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const location = useLocation() as any;
  const { login } = useAuth();
  const api = createApiClient();

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const res = await api.login({ email, password });
      login(res.token, res.role as any);
      const to = location.state?.from?.pathname ?? '/dashboard';
      navigate(to, { replace: true });
    } catch (err: any) {
      setError(err.message ?? 'Login failed');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div style={{ display: 'grid', placeItems: 'center', height: '100vh' }}>
      <form onSubmit={handleSubmit} style={{ display: 'grid', gap: 12, width: 320, padding: 24, border: '1px solid #e5e7eb', borderRadius: 12, background: 'var(--surface)' }}>
        <h1 style={{ margin: 0 }}>Provider Login</h1>
        {error && <div style={{ color: 'crimson' }}>{error}</div>}
        <label>
          <div>Email</div>
          <input value={email} onChange={e => setEmail(e.target.value)} type="email" required style={{ width: '100%', padding: 8 }} />
        </label>
        <label>
          <div>Password</div>
          <input value={password} onChange={e => setPassword(e.target.value)} type="password" required minLength={8} style={{ width: '100%', padding: 8 }} />
        </label>
        <button type="submit" disabled={loading}>{loading ? 'Signing in…' : 'Sign In'}</button>
      </form>
    </div>
  );
}
