import { useEffect, useState } from 'react';
import { get, send } from '../api';
import BoardCard from '../components/BoardCard';

const STATUSES = ['applied', 'interviewing', 'offer', 'rejected', 'accepted'];

const TITLES = {
  applied: 'Applied',
  interviewing: 'Interviewing',
  offer: 'Offer',
  rejected: 'Rejected',
  accepted: 'Accepted',
};

export default function BoardPage() {
  const [applications, setApplications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    getApplications();
  }, []);

  async function getApplications() {
    setLoading(true);
    try {
      const data = await get('/applications');
      setApplications(data.applications);
      setError('');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function move(app, direction) {
    const index = STATUSES.indexOf(app.status) + direction;
    const nextStatus = STATUSES[index];

    const previous = applications;
    setApplications(
      applications.map((item) =>
        item.id === app.id ? { ...item, status: nextStatus } : item
      )
    );

    try {
      await send('PUT', `/applications/${app.id}`, { status: nextStatus });
    } catch (err) {
      setApplications(previous);
      setError(err.message);
    }
  }

  if (loading) {
    return <p className="muted">Loading…</p>;
  }

  return (
    <div>
      <div className="page-head">
        <h1>Board</h1>
      </div>

      {error && <p className="error-banner">{error}</p>}

      <div className="board">
        {STATUSES.map((status, statusIndex) => {
          const items = applications.filter((app) => app.status === status);

          return (
            <section className="board-column" key={status}>
              <h2>
                {TITLES[status]} <span className="count">{items.length}</span>
              </h2>
              {items.length === 0 && <p className="muted">Nothing here</p>}
              {items.map((app) => (
                <BoardCard
                  key={app.id}
                  app={app}
                  canMoveLeft={statusIndex > 0}
                  canMoveRight={statusIndex < STATUSES.length - 1}
                  onMove={(direction) => move(app, direction)}
                />
              ))}
            </section>
          );
        })}
      </div>
    </div>
  );
}
