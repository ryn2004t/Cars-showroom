import { NavLink } from 'react-router-dom';

const navItems = [
  { to: '/dashboard', label: 'Dashboard' },
  { to: '/jobs', label: 'Jobs' },
  { to: '/customers', label: 'Customers' },
  { to: '/media', label: 'Media' },
  { to: '/invoices', label: 'Invoices' },
  { to: '/promotions', label: 'Promotions' },
  { to: '/reviews', label: 'Reviews' },
  { to: '/analytics', label: 'Analytics' },
  { to: '/settings', label: 'Settings' },
];

export function Sidebar() {
  return (
    <aside style={{
      width: 240,
      padding: '1rem',
      borderRight: '1px solid #e5e7eb',
      position: 'sticky',
      top: 0,
      height: '100vh',
      background: 'var(--surface)'
    }}>
      <div style={{ fontWeight: 700, marginBottom: '1rem' }}>Provider Panel</div>
      <nav style={{ display: 'grid', gap: 8 }}>
        {navItems.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            style={({ isActive }) => ({
              padding: '0.5rem 0.75rem',
              borderRadius: 8,
              background: isActive ? 'var(--primary)' : 'transparent',
              color: isActive ? '#000' : 'inherit',
              textDecoration: 'none'
            })}
          >
            {item.label}
          </NavLink>
        ))}
      </nav>
    </aside>
  );
}
