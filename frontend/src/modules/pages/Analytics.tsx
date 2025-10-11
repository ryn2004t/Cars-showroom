import { useEffect, useState } from 'react';
import { createApiClient } from '../api/client';

export default function Analytics() {
  const api = createApiClient();
  const [data, setData] = useState<any | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.getAnalyticsSummary().then(setData).catch(e => setError(e.message));
  }, []);

  if (error) return <div style={{ color: 'crimson' }}>Error: {error}</div>;
  if (!data) return <div>Loading analytics...</div>;

  return (
    <div>
      <h1>Analytics</h1>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, minmax(0, 1fr))', gap: 12 }}>
        <Metric title="Total Jobs" value={data.jobsTotal} />
        <Metric title="Completed Jobs" value={data.jobsCompleted} />
        <Metric title="Revenue" value={`$${Number(data.revenueTotal).toFixed(2)}`} />
        <Metric title="Avg. Rating" value={data.avgRating} />
      </div>
    </div>
  );
}

function Metric({ title, value }: { title: string; value: any }) {
  return (
    <div style={{ border: '1px solid #e5e7eb', padding: 16, borderRadius: 8 }}>
      <div style={{ color: '#6b7280' }}>{title}</div>
      <div style={{ fontSize: 24, fontWeight: 700 }}>{String(value)}</div>
    </div>
  );
}
