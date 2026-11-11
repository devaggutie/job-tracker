export class ApiError extends Error {
  constructor(status, message) {
    super(message);
    this.status = status;
  }
}

async function parse(res) {
  const body = await res.json().catch(() => null);

  if (!res.ok) {
    throw new ApiError(res.status, (body && body.error) || 'request failed');
  }

  return body ? body.data : null;
}

export async function get(path) {
  const res = await fetch(path, { credentials: 'include' });
  return parse(res);
}

async function csrfToken() {
  const res = await fetch('/auth/csrf', { credentials: 'include' });
  const body = await res.json();
  return body.data.csrfToken;
}

export async function send(method, path, data) {
  const token = await csrfToken();

  const res = await fetch(path, {
    method,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: data === undefined ? undefined : JSON.stringify(data),
  });

  return parse(res);
}
