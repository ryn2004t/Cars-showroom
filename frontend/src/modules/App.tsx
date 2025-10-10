import { Suspense } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AppRoutes } from './pages/Routes';
import { AuthProvider } from './auth/AuthContext';
import { RequireAuth } from './auth/RequireAuth';
import Login from './pages/Login';

function App() {
  return (
    <AuthProvider>
      <Suspense fallback={<div>Loading...</div>}>
        <Routes>
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="/login" element={<Login />} />
          <Route element={<RequireAuth />}>
            <Route path="/*" element={<AppRoutes />} />
          </Route>
          <Route path="*" element={<div>Not Found</div>} />
        </Routes>
      </Suspense>
    </AuthProvider>
  );
}

export default App;
