import { useEffect, useState } from 'react';
import { createApiClient } from '../api/client';

interface JobItem {
  id: string;
  title: string;
  scheduledAt: string;
  status: string;
  customerId: string;
}

export default function Dashboard() {
  const api = createApiClient();
  const [jobs, setJobs] = useState<JobItem[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.getJobs()
      .then(setJobs)
      .catch((e) => setError(e.message));
  }, []);

  if (error) return <div style={{ color: 'crimson' }}>Error: {error}</div>;
  if (!jobs) return <div>Loading jobs...</div>;

  return (
    <div>
      <h1>Upcoming Jobs</h1>
      <div style={{ display: 'grid', gap: 12 }}>
        {jobs.map(job => (
          <div key={job.id} style={{ padding: 12, border: '1px solid #e5e7eb', borderRadius: 8 }}>
            <div style={{ fontWeight: 600 }}>{job.title}</div>
            <div>{new Date(job.scheduledAt).toLocaleString()}</div>
            <div>Status: {job.status}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
