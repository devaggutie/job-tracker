import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../AuthContext';

export default function Shell() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  async function handleLogout() {
    await logout();
    navigate('/login');
  }

  return (
    <div>
      <header className="topbar">
        <NavLink to="/" className="brand">
          Job Tracker
        </NavLink>
        <nav>
          <NavLink to="/" end>
            Applications
          </NavLink>
          <NavLink to="/board">Board</NavLink>
        </nav>
        <div className="topbar-right">
          <span className="muted">{user.email}</span>
          <button type="button" className="btn btn-ghost" onClick={handleLogout}>
            Log out
          </button>
        </div>
      </header>
      <main className="container">
        <Outlet />
      </main>
    </div>
  );
}
