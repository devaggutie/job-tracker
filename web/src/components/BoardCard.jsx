import { useNavigate } from 'react-router-dom';

export default function BoardCard({ app, canMoveLeft, canMoveRight, onMove }) {
  const navigate = useNavigate();

  return (
    <div
      className="board-card"
      role="button"
      tabIndex={0}
      onClick={() => navigate(`/applications/${app.id}`)}
      onKeyDown={(event) => {
        if (event.key === 'Enter') navigate(`/applications/${app.id}`);
      }}
    >
      <div className="board-card-main">
        <strong>{app.company}</strong>
        <span className="muted">{app.role}</span>
      </div>
      <div
        className="board-card-actions"
        onClick={(event) => event.stopPropagation()}
      >
        <button
          type="button"
          className="btn btn-ghost"
          disabled={!canMoveLeft}
          aria-label={`Move ${app.company} left`}
          onClick={() => onMove(-1)}
        >
          ‹
        </button>
        <button
          type="button"
          className="btn btn-ghost"
          disabled={!canMoveRight}
          aria-label={`Move ${app.company} right`}
          onClick={() => onMove(1)}
        >
          ›
        </button>
      </div>
    </div>
  );
}
