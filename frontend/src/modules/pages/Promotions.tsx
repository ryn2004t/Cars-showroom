import { useEffect, useState } from 'react';
import { createApiClient } from '../api/client';

export default function Promotions() {
  const api = createApiClient();
  const [items, setItems] = useState<any[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.getPromotions().then(setItems).catch(e => setError(e.message));
  }, []);

  if (error) return <div style={{ color: 'crimson' }}>Error: {error}</div>;
  if (!items) return <div>Loading promotions...</div>;

  return (
    <div>
      <h1>Promotions & Loyalty</h1>
      <div style={{ display: 'grid', gap: 8 }}>
        {items.map((p) => (
          <div key={p.id} style={{ border: '1px solid #e5e7eb', padding: 12, borderRadius: 8 }}>
            <div style={{ fontWeight: 600 }}>{p.name}</div>
            <div>{p.description}</div>
            {p.discountPercent != null && <div>Discount: {p.discountPercent}%</div>}
          </div>
        ))}
      </div>
    </div>
  );
}
