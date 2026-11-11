import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { get, send } from '../api';
import StatusBadge from '../components/StatusBadge';

function formatWhen(timestamp) {
  const date = new Date(timestamp.replace(' ', 'T') + 'Z');
  return date.toLocaleString();
}

export default function ApplicationDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [application, setApplication] = useState(null);
  const [history, setHistory] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    get(`/applications/${id}`)
      .then((data) => {
        setApplication(data.application);
        setHistory(data.statusHistory);
        setError('');
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [id]);

  async function handleDelete() {
    if (!window.confirm('Delete this application?')) {
      return;
    }

    try {
      await send('DELETE', `/applications/${id}`);
      navigate('/');
    } catch (err) {
      setError(err.message);
    }
  }

  if (loading) {
    return <p className="muted">Loading…</p>;
  }

  if (!application) {
    return (
      <div>
        <p className="error-banner">{error || 'not found'}</p>
        <Link className="btn" to="/">
          Back to applications
        </Link>
      </div>
    );
  }

  return (
    <div>
      <div className="page-head">
        <div>
          <h1>{application.company}</h1>
          <p className="subtitle">
            {application.role} <StatusBadge status={application.status} />
          </p>
        </div>
        <div className="actions">
          <Link className="btn" to={`/applications/${application.id}/edit`}>
            Edit
          </Link>
          <button type="button" className="btn btn-danger" onClick={handleDelete}>
            Delete
          </button>
        </div>
      </div>

      {error && <p className="error-banner">{error}</p>}

      <div className="detail-grid">
        <section className="card">
          <h2>Details</h2>
          <dl>
            <dt>Job URL</dt>
            <dd>
              {application.url ? (
                <a href={application.url} target="_blank" rel="noreferrer">
                  {application.url}
                </a>
              ) : (
                '—'
              )}
            </dd>
            <dt>Salary range</dt>
            <dd>
              {application.salary_min || application.salary_max
                ? `${application.salary_min ?? '?'} – ${application.salary_max ?? '?'}`
                : '—'}
            </dd>
            <dt>Date applied</dt>
            <dd>{application.date_applied || '—'}</dd>
            <dt>Follow-up</dt>
            <dd>{application.follow_up_date || '—'}</dd>
            <dt>Notes</dt>
            <dd>{application.notes || '—'}</dd>
          </dl>
        </section>

        <section className="card">
          <h2>Status timeline</h2>
          <ol className="timeline">
            {history.map((entry, index) => (
              <li key={index}>
                <span className="timeline-from">
                  {entry.from_status || 'Created'}
                </span>
                {' → '}
                <strong>{entry.to_status}</strong>
                <span className="muted"> · {formatWhen(entry.changed_at)}</span>
              </li>
            ))}
          </ol>
        </section>
      </div>
    </div>
  );
}
