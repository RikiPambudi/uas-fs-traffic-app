/* Axios-based ApiClient
   - Uses axios for requests and automatic JSON handling
   - Supports request retry after refresh with a refresh lock
   - Configurable baseURL via window.API_BASE (e.g. 'http://localhost:8081')
*/

const _getBase = () => {
  if (window.API_BASE) return window.API_BASE.replace(/\/$/, '') + '/api';
  // default: same origin /api
  return '/api';
};

const axiosInstance = axios.create({ baseURL: _getBase(), timeout: 15000 });
let accessToken = localStorage.getItem('access_token') || null;
let refreshToken = localStorage.getItem('refresh_token') || null;
let refreshLock = null;

function saveTokens(access, refresh){
  accessToken = access || null; refreshToken = refresh || null;
  if (access) localStorage.setItem('access_token', access); else localStorage.removeItem('access_token');
  if (refresh) localStorage.setItem('refresh_token', refresh); else localStorage.removeItem('refresh_token');
}

// Request interceptor to add Authorization header
axiosInstance.interceptors.request.use(cfg => {
  cfg.headers = cfg.headers || {};
  cfg.headers['Accept'] = 'application/json';
  if (!(cfg.data instanceof FormData) && !cfg.headers['Content-Type']) cfg.headers['Content-Type'] = 'application/json';
  if (accessToken) cfg.headers['Authorization'] = `Bearer ${accessToken}`;
  return cfg;
});

// Response interceptor to handle 401 -> try refresh -> retry
axiosInstance.interceptors.response.use(resp => resp, async err => {
  const original = err.config;
  if (err.response && err.response.status === 401 && refreshToken && !original._retry) {
    original._retry = true;
    const ok = await tryRefresh();
    if (ok) {
      original.headers['Authorization'] = `Bearer ${accessToken}`;
      return axiosInstance(original);
    }
  }
  return Promise.reject(err);
});

async function tryRefresh(){
  if (!refreshToken) return false;
  if (refreshLock) return refreshLock;
  refreshLock = (async ()=>{
    try {
      // Use a bare fetch to avoid interceptor loop (or axios without interceptors)
      const r = await fetch(_getBase() + '/refresh', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: refreshToken })
      });
      if (!r.ok) throw new Error('refresh-failed');
      const j = await r.json();
      const data = j.data || {};
      saveTokens(data.access_token, data.refresh_token);
      return true;
    } catch (e) {
      saveTokens(null, null);
      return false;
    } finally { refreshLock = null; }
  })();
  return refreshLock;
}

const apiClient = {
  get accessToken(){ return accessToken; },
  get refreshToken(){ return refreshToken; },
  async get(path){ return axiosInstance.get(path); },
  async post(path, data){ return axiosInstance.post(path, data); },
  async put(path, data){ return axiosInstance.put(path, data); },
  async delete(path){ return axiosInstance.delete(path); },
  async login(identity, password){
    try {
      const res = await axiosInstance.post('/login', { identity, password });
      const j = res.data || {};
      if (j.data) saveTokens(j.data.access_token, j.data.refresh_token);
      return j;
    } catch (e) {
      // if server returned HTML for error, include status/text
      if (e.response && typeof e.response.data === 'string') return { status: 'error', message: e.response.data, code: e.response.status };
      return { status: 'error', message: e.message };
    }
  },
  async logout(){
    try { await axiosInstance.post('/logout', {}); } catch(_){}
    saveTokens(null, null);
  },
  _tryRefresh: tryRefresh,
  saveTokens
};

window.api = apiClient;
