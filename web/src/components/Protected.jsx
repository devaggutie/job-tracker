import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../AuthContext';

export default function Protected() {
  const { user, loading } = useAuth();

  if (loading) {
    return <p className="muted center">Loading…</p>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />;
}
