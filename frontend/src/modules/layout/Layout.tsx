import { PropsWithChildren } from 'react';
import { Sidebar } from './Sidebar';
import { Topbar } from './Topbar';

export function Layout({ children }: PropsWithChildren) {
  return (
    <div style={{ display: 'grid', gridTemplateColumns: '240px 1fr', height: '100vh' }}>
      <Sidebar />
      <div style={{ display: 'grid', gridTemplateRows: 'auto 1fr' }}>
        <Topbar />
        <main style={{ padding: '1rem', overflow: 'auto' }}>{children}</main>
      </div>
    </div>
  );
}
