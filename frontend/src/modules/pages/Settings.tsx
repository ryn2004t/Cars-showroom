import { useEffect, useState } from 'react';
import { createApiClient } from '../api/client';

export default function Settings() {
  const api = createApiClient();
  const [settings, setSettings] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<boolean | null>(null);

  useEffect(() => {
    api.getSettings().then(setSettings).catch(e => setError(e.message));
  }, []);

  async function save() {
    setError(null); setOk(null); setSaving(true);
    try {
      const res = await api.updateSettings(settings);
      setOk(res.ok);
    } catch (e:any) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  }

  const entries = Object.entries(settings);

  return (
    <div>
      <h1>Settings</h1>
      {error && <div style={{ color: 'crimson' }}>{error}</div>}
      {ok && <div style={{ color: 'green' }}>Saved</div>}
      <div style={{ display: 'grid', gap: 8, maxWidth: 600 }}>
        {entries.length === 0 && <div>No settings yet.</div>}
        {entries.map(([k, v]) => (
          <label key={k} style={{ display: 'grid', gap: 4 }}>
            <div>{k}</div>
            <input value={v} onChange={e => setSettings({ ...settings, [k]: e.target.value })} />
          </label>
        ))}
        <div>
          <button onClick={() => setSettings({ ...settings, business_name: settings.business_name ?? '' })}>Add business_name</button>
        </div>
        <div>
          <button onClick={save} disabled={saving}>{saving ? 'Saving…' : 'Save Settings'}</button>
        </div>
      </div>
    </div>
  );
}
