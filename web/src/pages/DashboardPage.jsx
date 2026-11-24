import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { get } from '../api';

const STATUSES = ['applied', 'interviewing', 'offer', 'rejected', 'accepted'];

const MAX_BAR_PX = 130;

export default function DashboardPage() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    get('/dashboard')
      .then((dashboard) => {
        setData(dashboard);
        setError('');
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <p className="muted">Loading…</p>;
  }

  if (!data) {
    return <p className="error-banner">{error || 'failed to load dashboard'}</p>;
  }

  const max = Math.max(...data.applicationsPerWeek.map((week) => week.count), 1);

  return (
    <div>
      <div className="page-head">
        <h1>Dashboard</h1>
      </div>

      {error && <p className="error-banner">{error}</p>}

      <section className="dashboard-section">
        <h2>Pipeline</h2>
        <div className="stat-cards">
          {STATUSES.map((status) => (
            <Link
              key={status}
              className={`stat-card stat-${status}`}
              to={`/applications?status=${status}`}
            >
              <span className="stat-number">{data.countsByStatus[status]}</span>
              <span className="stat-label">{status}</span>
            </Link>
          ))}
        </div>
      </section>

      <section className="dashboard-section">
        <h2>Applications per week</h2>
        <div className="chart card">
          {data.applicationsPerWeek.map((week) => (
            <div className="chart-col" key={week.weekStart}>
              <span className="chart-value">{week.count}</span>
              <div
                className="chart-bar"
                style={{
                  height: week.count === 0 ? 0 : Math.max((week.count / max) * MAX_BAR_PX, 8),
                }}
                title={`${week.count} application(s) week of ${week.weekStart}`}
              />
              <span className="chart-label">{week.weekStart.slice(5)}</span>
            </div>
          ))}
        </div>
      </section>

      <section className="dashboard-section">
        <h2>Needs follow-up</h2>
        {data.followUps.length === 0 ? (
          <div className="empty">
            <p>Nothing due — you're all caught up.</p>
          </div>
        ) : (
          <ul className="followup-list card">
            {data.followUps.map((app) => {
              const overdue = app.follow_up_date < data.today;

              return (
                <li key={app.id}>
                  <div className="followup-main">
                    <Link to={`/applications/${app.id}`}>{app.company}</Link>
                    <span className="muted">{app.role}</span>
                  </div>
                  <span className={`badge ${overdue ? 'badge-overdue' : 'badge-due'}`}>
                    {overdue ? 'Overdue' : 'Due'} {app.follow_up_date}
                  </span>
                </li>
              );
            })}
          </ul>
        )}
      </section>
    </div>
  );
}
