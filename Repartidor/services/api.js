const config = require('../config');

let authToken = null;

function setToken(token) {
  authToken = token;
}

function getToken() {
  return authToken;
}

function clearToken() {
  authToken = null;
}

async function request(method, path, body = null) {
  const url = `${config.angelowUrl}${path}`;
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (authToken) {
    options.headers['Authorization'] = `Bearer ${authToken}`;
  }
  if (body) {
    options.body = JSON.stringify(body);
  }
  const res = await fetch(url, options);
  const data = await res.json();
  if (!res.ok) {
    throw new Error(data.error || data.message || `Error ${res.status}`);
  }
  return data;
}

async function uploadFile(path, formData) {
  const url = `${config.angelowUrl}${path}`;
  const options = {
    method: 'POST',
    headers: {},
  };
  if (authToken) {
    options.headers['Authorization'] = `Bearer ${authToken}`;
  }
  options.body = formData;
  const res = await fetch(url, options);
  const data = await res.json();
  if (!res.ok) {
    throw new Error(data.error || data.message || `Error ${res.status}`);
  }
  return data;
}

module.exports = {
  setToken,
  getToken,
  clearToken,
  get: (path) => request('GET', path),
  post: (path, body) => request('POST', path, body),
  put: (path, body) => request('PUT', path, body),
  del: (path) => request('DELETE', path),
  upload: (path, formData) => uploadFile(path, formData),
};
