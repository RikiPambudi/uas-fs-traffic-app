# Frontend (Traffic Dashboard)

Lightweight Bootstrap + vanilla JS frontend for the Traffic Dashboard API.

How to use
1. Serve the `frontend` folder via static server or place behind your webserver. Example (PHP built-in):

```markdown
# Frontend (Traffic Dashboard)

This is a lightweight Bootstrap + vanilla JS frontend that integrates with the existing CodeIgniter API under `/api`.

Quick start

1. Ensure the backend is running and API is reachable at the same host (the client uses `/api` base path).
2. Serve this folder via your web server (e.g., place under DocumentRoot or use a simple HTTP server):

```bash
cd frontend
python3 -m http.server 8080
```

3. Visit http://localhost:8080 and login using an existing user in the database.

Notes
- The client stores tokens in `localStorage` and will attempt to refresh tokens using `/api/refresh` when needed.
- Master data (violation types, vehicle types) is fetched from `/api/master/*` endpoints and used to populate form selects.
- If you run the frontend under a different origin than the API, configure CORS on the backend and update `basePath` in `assets/js/api.js`.

Next steps (optional)
- Add role-based UI restrictions and more granular error handling.
- Improve token storage to use secure cookies for production.
```
