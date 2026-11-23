import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { get, send } from '../api';

const STATUSES = ['applied', 'interviewing', 'offer', 'rejected', 'accepted'];

const EMPTY = {
  company: '',
  role: '',
  status: 'applied',
  url: '',
  salary_min: '',
  salary_max: '',
  date_applied: '',
  follow_up_date: '',
  notes: '',
};

export default function ApplicationFormPage() {
  const { id } = useParams();
  const isNew = !id;
  const navigate = useNavigate();
  const [form, setForm] = useState(EMPTY);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(!isNew);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (isNew) {
      setForm(EMPTY);
      return;
    }

    setLoading(true);
    get(`/applications/${id}`)
      .then((data) => {
        const app = data.application;
        setForm({
          company: app.company,
          role: app.role,
          status: app.status,
          url: app.url || '',
          salary_min: app.salary_min ?? '',
          salary_max: app.salary_max ?? '',
          date_applied: app.date_applied || '',
          follow_up_date: app.follow_up_date || '',
          notes: app.notes || '',
        });
        setError('');
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [id, isNew]);

  function update(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setBusy(true);

    const payload = {
      company: form.company.trim(),
      role: form.role.trim(),
      status: form.status,
      url: form.url.trim() || null,
      salary_min: form.salary_min === '' ? null : Number(form.salary_min),
      salary_max: form.salary_max === '' ? null : Number(form.salary_max),
      date_applied: form.date_applied || null,
      follow_up_date: form.follow_up_date || null,
      notes: form.notes,
    };

    try {
      const data = isNew
        ? await send('POST', '/applications', payload)
        : await send('PUT', `/applications/${id}`, payload);
      navigate(`/applications/${data.application.id}`);
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  if (loading) {
    return <p className="muted">Loading…</p>;
  }

  return (
    <div className="form-page">
      <div className="page-head">
        <h1>{isNew ? 'New application' : 'Edit application'}</h1>
      </div>

      {error && <p className="error-banner">{error}</p>}

      <form className="card form" onSubmit={handleSubmit}>
        <div className="form-row">
          <label>
            Company *
            <input
              type="text"
              value={form.company}
              onChange={(event) => update('company', event.target.value)}
              required
              maxLength={255}
            />
          </label>
          <label>
            Role *
            <input
              type="text"
              value={form.role}
              onChange={(event) => update('role', event.target.value)}
              required
              maxLength={255}
            />
          </label>
        </div>

        <div className="form-row">
          <label>
            Status
            <select
              value={form.status}
              onChange={(event) => update('status', event.target.value)}
            >
              {STATUSES.map((status) => (
                <option key={status} value={status}>
                  {status}
                </option>
              ))}
            </select>
          </label>
          <label>
            Job URL
            <input
              type="url"
              value={form.url}
              onChange={(event) => update('url', event.target.value)}
              placeholder="https://…"
            />
          </label>
        </div>

        <div className="form-row">
          <label>
            Salary min
            <input
              type="number"
              min="0"
              value={form.salary_min}
              onChange={(event) => update('salary_min', event.target.value)}
            />
          </label>
          <label>
            Salary max
            <input
              type="number"
              min="0"
              value={form.salary_max}
              onChange={(event) => update('salary_max', event.target.value)}
            />
          </label>
        </div>

        <div className="form-row">
          <label>
            Date applied
            <input
              type="date"
              value={form.date_applied}
              onChange={(event) => update('date_applied', event.target.value)}
            />
          </label>
          <label>
            Follow-up date
            <input
              type="date"
              value={form.follow_up_date}
              onChange={(event) => update('follow_up_date', event.target.value)}
            />
          </label>
        </div>

        <label>
          Notes
          <textarea
            rows={4}
            value={form.notes}
            onChange={(event) => update('notes', event.target.value)}
          />
        </label>

        <div className="form-actions">
          <button type="submit" className="btn btn-primary" disabled={busy}>
            {busy ? 'Saving…' : isNew ? 'Create application' : 'Save changes'}
          </button>
          <button
            type="button"
            className="btn btn-ghost"
            onClick={() => navigate(isNew ? '/' : `/applications/${id}`)}
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  );
}
