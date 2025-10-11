import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

export function Topbar() {
  const navigate = useNavigate();
  const { logout } = useAuth();
  const [theme, setTheme] = useState<'light' | 'dark'>(() => {
    const saved = localStorage.getItem('theme');
    return (saved === 'dark' ? 'dark' : 'light');
  });

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
  }, [theme]);

  return (
    <header style={{
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: '0.75rem 1rem',
      borderBottom: '1px solid #e5e7eb',
      background: 'var(--surface)'
    }}>
      <div>Service Provider Dashboard</div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
        <button onClick={() => setTheme(t => t === 'light' ? 'dark' : 'light')}>
          Toggle {theme === 'light' ? 'Dark' : 'Light'}
        </button>
        <button onClick={() => { logout(); navigate('/login', { replace: true }); }}>
          Logout
        </button>
      </div>
    </header>
  );
}
