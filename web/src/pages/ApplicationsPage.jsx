import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { get } from '../api';
import StatusBadge from '../components/StatusBadge';

const STATUSES = ['applied', 'interviewing', 'offer', 'rejected', 'accepted'];

export default function ApplicationsPage() {
  const [applications, setApplications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchParams, setSearchParams] = useSearchParams();
  const status = searchParams.get('status') || '';
  const query = searchParams.get('q') || '';
  const [searchInput, setSearchInput] = useState(query);

  useEffect(() => {
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (query) params.set('q', query);
    const qs = params.toString();

    setLoading(true);
    get('/applications' + (qs ? `?${qs}` : ''))
      .then((data) => {
        setApplications(data.applications);
        setError('');
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [status, query]);

  function applyFilters(nextStatus, nextQuery) {
    const params = new URLSearchParams();
    if (nextStatus) params.set('status', nextStatus);
    if (nextQuery) params.set('q', nextQuery);
    setSearchParams(params);
  }

  function handleSearch(event) {
    event.preventDefault();
    applyFilters(status, searchInput.trim());
  }

  return (
    <div>
      <div className="page-head">
        <h1>Applications</h1>
        <Link className="btn btn-primary" to="/applications/new">
          New application
        </Link>
      </div>

      <form className="filters" onSubmit={handleSearch}>
        <select
          value={status}
          onChange={(event) => applyFilters(event.target.value, query)}
          aria-label="Filter by status"
        >
          <option value="">All statuses</option>
          {STATUSES.map((value) => (
            <option key={value} value={value}>
              {value}
            </option>
          ))}
        </select>

        <input
          type="search"
          placeholder="Search company or role…"
          value={searchInput}
          onChange={(event) => setSearchInput(event.target.value)}
          aria-label="Search applications"
        />

        <button type="submit" className="btn">
          Search
        </button>
      </form>

      {error && <p className="error-banner">{error}</p>}
      {loading && <p className="muted">Loading…</p>}

      {!loading && !error && applications.length === 0 && (
        <div className="empty">
          <p>No applications match.</p>
          <Link className="btn btn-primary" to="/applications/new">
            Add your first one
          </Link>
        </div>
      )}

      {!loading && applications.length > 0 && (
        <table className="table">
          <thead>
            <tr>
              <th>Company</th>
              <th>Role</th>
              <th>Status</th>
              <th>Applied</th>
            </tr>
          </thead>
          <tbody>
            {applications.map((app) => (
              <tr key={app.id}>
                <td>
                  <Link to={`/applications/${app.id}`}>{app.company}</Link>
                </td>
                <td>{app.role}</td>
                <td>
                  <StatusBadge status={app.status} />
                </td>
                <td>{app.date_applied || '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
