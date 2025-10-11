import { Routes, Route } from 'react-router-dom';
import { Layout } from '../layout/Layout';
import Dashboard from './Dashboard';
import Invoices from './Invoices';
import Promotions from './Promotions';
import Reviews from './Reviews';
import Analytics from './Analytics';
import Settings from './Settings';

function Placeholder({ title }: { title: string }) {
  return (
    <div>
      <h1 style={{ marginTop: 0 }}>{title}</h1>
      <p>Coming soon.</p>
    </div>
  );
}

export function AppRoutes() {
  return (
    <Layout>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/jobs" element={<Placeholder title="Jobs" />} />
        <Route path="/customers" element={<Placeholder title="Customers" />} />
        <Route path="/media" element={<Placeholder title="Media" />} />
        <Route path="/invoices" element={<Invoices />} />
        <Route path="/promotions" element={<Promotions />} />
        <Route path="/reviews" element={<Reviews />} />
        <Route path="/analytics" element={<Analytics />} />
        <Route path="/settings" element={<Settings />} />
      </Routes>
    </Layout>
  );
}
