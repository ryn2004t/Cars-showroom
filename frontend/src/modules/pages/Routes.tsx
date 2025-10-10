import { Routes, Route } from 'react-router-dom';
import { Layout } from '../layout/Layout';
import Dashboard from './Dashboard';

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
        <Route path="/invoices" element={<Placeholder title="Invoices" />} />
        <Route path="/promotions" element={<Placeholder title="Promotions & Loyalty" />} />
        <Route path="/reviews" element={<Placeholder title="Review Responses" />} />
        <Route path="/analytics" element={<Placeholder title="Analytics & Reports" />} />
        <Route path="/settings" element={<Placeholder title="Settings" />} />
      </Routes>
    </Layout>
  );
}
