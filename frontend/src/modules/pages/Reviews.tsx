import { useEffect, useState } from 'react';
import { createApiClient } from '../api/client';

export default function Reviews() {
  const api = createApiClient();
  const [items, setItems] = useState<any[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.getReviews().then(setItems).catch(e => setError(e.message));
  }, []);

  if (error) return <div style={{ color: 'crimson' }}>Error: {error}</div>;
  if (!items) return <div>Loading reviews...</div>;

  return (
    <div>
      <h1>Reviews</h1>
      <div style={{ display: 'grid', gap: 8 }}>
        {items.map((r) => (
          <div key={r.id} style={{ border: '1px solid #e5e7eb', padding: 12, borderRadius: 8 }}>
            <div>Rating: {r.rating}/5</div>
            <div>{r.comment}</div>
            <div>On {new Date(r.createdAt).toLocaleString()}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
