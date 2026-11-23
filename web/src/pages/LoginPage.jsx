import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { send } from '../api';
import { useAuth } from '../AuthContext';

export default function LoginPage() {
  const [mode, setMode] = useState('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setBusy(true);

    try {
      const data = await send(
        'POST',
        mode === 'login' ? '/auth/login' : '/auth/register',
        { email, password }
      );
      login(data);
      navigate('/');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  function toggleMode() {
    setMode(mode === 'login' ? 'register' : 'login');
    setError('');
  }

  return (
    <div className="auth-page">
      <form className="auth-card" onSubmit={handleSubmit}>
        <h1 className="brand">Job Tracker</h1>
        <h2>{mode === 'login' ? 'Log in' : 'Create account'}</h2>

        {error && <p className="error-banner">{error}</p>}

        <label>
          Email
          <input
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
            autoComplete="email"
          />
        </label>

        <label>
          Password
          <input
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
            minLength={8}
            autoComplete={mode === 'login' ? 'current-password' : 'new-password'}
          />
        </label>

        <button type="submit" className="btn btn-primary" disabled={busy}>
          {busy ? 'Working…' : mode === 'login' ? 'Log in' : 'Sign up'}
        </button>

        <button type="button" className="btn btn-ghost" onClick={toggleMode}>
          {mode === 'login' ? 'Need an account? Sign up' : 'Have an account? Log in'}
        </button>
      </form>
    </div>
  );
}
