import { useEffect, useState } from 'react';
import { createApiClient } from '../api/client';

export default function Invoices() {
  const api = createApiClient();
  const [items, setItems] = useState<any[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.getInvoices().then(setItems).catch(e => setError(e.message));
  }, []);

  if (error) return <div style={{ color: 'crimson' }}>Error: {error}</div>;
  if (!items) return <div>Loading invoices...</div>;

  return (
    <div>
      <h1>Invoices</h1>
      <div style={{ display: 'grid', gap: 8 }}>
        {items.map((i) => (
          <div key={i.id} style={{ border: '1px solid #e5e7eb', padding: 12, borderRadius: 8 }}>
            <div>#{i.invoiceNumber} • {i.status}</div>
            <div>Total: ${Number(i.totalAmount).toFixed(2)}</div>
            <div>Created: {new Date(i.createdAt).toLocaleString()}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
