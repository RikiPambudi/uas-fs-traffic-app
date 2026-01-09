// Minimal UI layer: modern, modular, small helpers

const $ = selector => document.querySelector(selector);
const $$ = selector => Array.from(document.querySelectorAll(selector));

function alertMessage(msg, type = 'info'){
  const el = $('#alert-placeholder');
  el.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
}

function hideAll(){ $$('#page-login, #page-dashboard, #page-violations, #page-observations, #page-masterdata').forEach(n=>n.classList.add('d-none')); }
function show(id){
  hideAll();
  const el = $(id) || $('#content-area');
  if (el) el.classList.remove('d-none');
}

async function loadPage(name, params){
  try {
    const res = await fetch(`pages/${name}.html`);
    if (!res.ok) throw new Error('page-not-found');
    const html = await res.text();
    $('#content-area').innerHTML = html;
    // Hide header for login page to provide a minimal fullscreen login
    const hdr = document.querySelector('header');
    if (hdr) hdr.classList.toggle('d-none', name === 'login');
    // call page-specific initializer if exists
    if (pageInits[name]) await pageInits[name](params);
  } catch (e){
    $('#content-area').innerHTML = `<div class="alert alert-danger">Failed to load page: ${name}</div>`;
    console.error(e);
  }
}

const pageInits = {
  login: async ()=>{
    $('#form-login').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(e.target);
      const identity = fd.get('identity');
      const password = fd.get('password');
      const res = await api.login(identity, password);
      if (res && res.status === 'success'){
        alertMessage('Welcome back','success');
        updateAuth();
        await loadPage('dashboard');
      } else alertMessage(res?.message || 'Login failed','danger');
    });
  },
  dashboard: async ()=>{
    $('#refreshDashboard')?.addEventListener('click', ()=> loadDashboard());
    await loadDashboard();
  },
  violations: async ()=>{
    // populate selects
    await loadMasterData();
    $('#btn-new-violation').addEventListener('click', ()=> new bootstrap.Modal($('#violation-form-modal')).show());
    $('#form-violation').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target));
      const id = data.id || null;
      if (data.violation_datetime) {
        data.violation_datetime = data.violation_datetime.replace('T',' ');
        // ensure seconds are present (backend expects Y-m-d H:i:s)
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(data.violation_datetime)) {
          data.violation_datetime = data.violation_datetime + ':00';
        }
      }
      try {
        let resp;
        if (id) resp = await api.put(`/violations/${id}`, data); else resp = await api.post('/violations', data);
        const res = resp.data;
        if (res.status === 'success'){
          alertMessage('Violation saved','success');
          bootstrap.Modal.getInstance($('#violation-form-modal')).hide();
          $('#form-violation').reset();
          loadViolations();
        } else {
          alertMessage(res.message || 'Save failed','danger');
        }
      } catch (err) {
        console.warn('Save violation error', err);
        const server = err?.response?.data;
        if (server && server.data) {
          // validation errors object
          if (typeof server.data === 'object') {
            const msgs = Object.values(server.data).flat().join('<br>');
            alertMessage(msgs, 'danger');
          } else {
            alertMessage(String(server.data), 'danger');
          }
        } else if (server && server.message) {
          alertMessage(server.message, 'danger');
        } else {
          alertMessage('Save failed (network/server error)','danger');
        }
      }
    });
    await loadViolations();
  },
  observations: async ()=>{
    await loadMasterData();
    $('#btn-new-observation').addEventListener('click', ()=> new bootstrap.Modal($('#observation-form-modal')).show());
    $('#form-observation').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target));
      const id = data.id || null;
      if (data.observation_datetime) data.observation_datetime = data.observation_datetime.replace('T',' ');
      let res;
      if (id) res = (await api.put(`/observations/${id}`, data)).data; else res = (await api.post('/observations', data)).data;
      if (res.status === 'success'){
        alertMessage('Observation saved','success');
        bootstrap.Modal.getInstance($('#observation-form-modal')).hide();
        $('#form-observation').reset();
        loadObservations();
      } else alertMessage(res.message || 'Save failed','danger');
    });
    await loadObservations();
  },
  masterdata: async ()=>{
    await loadMasterData();
    // render lists
    const vt = (await api.get('/master/violation-types')).data;
    const vlist = vt.status==='success' ? vt.data : [];
    $('#violationTypesList').innerHTML = `<ul class="list-group">${vlist.map(v=>`<li class="list-group-item d-flex justify-content-between"><span>${v.name}</span></li>`).join('')}</ul>`;
    const vt2 = (await api.get('/master/vehicle-types')).data;
    const v2list = vt2.status==='success' ? vt2.data : [];
    $('#vehicleTypesList').innerHTML = `<ul class="list-group">${v2list.map(v=>`<li class="list-group-item d-flex justify-content-between"><span>${v.name}</span></li>`).join('')}</ul>`;
  }
};

function updateAuth(){
  const logged = !!api.accessToken;
  const navLogin = $('#nav-login'); const navLogout = $('#nav-logout');
  if (navLogin) navLogin.classList.toggle('d-none', logged);
  if (navLogout) navLogout.classList.toggle('d-none', !logged);
  // mark active menu item (for sidebar and top nav)
  $$('[id^="nav-"]').forEach(it=> it.classList.remove('active'));
  $$('.pc-link').forEach(it=> it.classList.remove('active'));
  const activeId = 'nav-' + (location.hash ? location.hash.replace('#','') : (document.body.dataset.page || 'dashboard'));
  const activeEl = document.getElementById(activeId);
  if (activeEl) activeEl.classList.add('active');
  // also mark parent .pc-item if this is a sidebar link
  if (activeEl && activeEl.closest('.pc-item')) {
    activeEl.closest('.pc-item').classList.add('active');
  }
}

async function loadMasterData(){
  try {
    const vt = (await api.get('/master/vehicle-types')).data;
    const vtList = vt.status==='success' ? vt.data : [];
    const selectV = document.querySelectorAll('#vehicleType, select[name="vehicle_type"]');
    selectV.forEach(s=>{
      // provide normalized values expected by backend mapping
      s.innerHTML = vtList.map(v=>{
        const code = (v.code||'').toUpperCase();
        const valMap = { 'TRUCK':'truk', 'CAR':'mobil', 'MOTORCYCLE':'motor' };
        const val = valMap[code] || (v.code||'').toLowerCase() || v.id;
        return `<option value="${val}" data-id="${v.id}">${v.name}</option>`;
      }).join('');
    });

    const vt2 = (await api.get('/master/violation-types')).data;
    const vtypes = vt2.status==='success' ? vt2.data : [];
    const selectT = document.querySelectorAll('#violationType, select[name="type"]');
    selectT.forEach(s=>{
      // map DB codes to the allowed keys the backend expects
      s.innerHTML = vtypes.map(v=>{
        const code = (v.code||'').toUpperCase();
        const map = { 'CONTRAFLOW':'contraflow', 'OVERSPEED':'overspeed', 'TRAFFIC_BLOCK':'traffic_jam' };
        const val = map[code] || (v.code||'').toLowerCase() || v.id;
        return `<option value="${val}" data-id="${v.id}">${v.name}</option>`;
      }).join('');
    });
  } catch (e){ console.warn('master data load failed', e); }
}

async function loadDashboard(){
  show('#page-dashboard');
  const res = (await api.get('/dashboard/summary')).data;
  if (!res || res.status!=='success') return alertMessage(res?.message||'Failed to load','danger');
  const counts = res.data.counts || {};
  const sl = $('#summary-list');
  if (sl) {
    sl.innerHTML = Object.keys(counts).map(k=>`<li class="list-group-item d-flex justify-content-between"><span>${k.replace(/_/g,' ')}</span><strong>${counts[k]}</strong></li>`).join('');
  } else console.warn('Missing #summary-list element in dashboard page');

  const rv = res.data.recent_violations || [];
  const rvEl = $('#recent-violations');
  if (rvEl) {
    rvEl.innerHTML = `<ul class="list-group">${rv.map(r=>`<li class="list-group-item"><strong>${r.violation_number||r.id}</strong> ${r.location_address || ''}<br><small>${r.violation_datetime || ''}</small></li>`).join('')}</ul>`;
  } else console.warn('Missing #recent-violations element in dashboard page');
}

function renderTable(container, items, columns){
  if (!container) return console.warn('renderTable: missing container');
  if (!items || items.length === 0) { container.innerHTML = '<div class="no-data">No records found</div>'; return; }
  container.innerHTML = `<div class="table-responsive"><table class="table table-striped table-hover"><thead><tr>${columns.map(c=>`<th>${c.label}</th>`).join('')}</tr></thead><tbody>${items.map(row=>`<tr>${columns.map(c=>`<td>${(typeof c.render==='function'? c.render(row) : (row[c.key]??''))}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
}

async function renderPagination(pagerEl, meta, onChange){
  if (!meta) { pagerEl.innerHTML = ''; return; }
  // Accept either paginator shapes: { current_page, last_page } (Laravel style)
  // or { currentPage, totalPages } (our backend)
  const page = meta.current_page || meta.currentPage || 1;
  const last = meta.last_page || meta.totalPages || 1;
  let html = '';
  for (let p=1;p<=last;p++) html += `<li class="page-item ${p===page?'active':''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
  pagerEl.innerHTML = html;
  pagerEl.querySelectorAll('a[data-page]').forEach(a=> a.addEventListener('click', (e)=>{ e.preventDefault(); onChange(Number(a.dataset.page)); }));
}

async function loadViolations(page=1){
  show('#page-violations');
  const res = (await api.get(`/violations?page=${page}`)).data;
  if (!res || res.status!=='success') return alertMessage(res?.message||'Failed','danger');
  const items = res.data.items || [];
  renderTable($('#violations-list'), items, [
    { label:'#', key:'id' },
    { label:'Number', key:'violation_number' },
    { label:'Type', key:'violation_type_id' },
    { label:'Location', key:'location_address' },
    { label:'Date', key:'violation_datetime' },
    { label:'Actions', key:'actions', render: (row)=>{
      return `<div class="btn-group" role="group"><button class="btn btn-sm btn-outline-primary btn-edit" data-id="${row.id}">Edit</button><button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}">Delete</button></div>`;
    }}
  ]);
  await renderPagination($('#violationsPagination'), res.data.pagination || res.data.meta, p=> loadViolations(p));
  // wire actions
  $$('#violations-list .btn-edit').forEach(b=> b.addEventListener('click', async ()=>{
    const id = b.dataset.id;
    const r = (await api.get(`/violations/${id}`)).data;
    if (r.status==='success'){
      const v = r.data;
      $('#violationId').value = v.id;
      // map numeric violation_type_id to the select option value (using data-id)
      const typeSelect = $('#form-violation').querySelector('[name="type"]');
      if (typeSelect) {
        const opt = Array.from(typeSelect.options).find(o=> o.dataset && String(o.dataset.id) === String(v.violation_type_id));
        if (opt) typeSelect.value = opt.value; else typeSelect.value = v.violation_type_id || '';
      }
      $('#form-violation').querySelector('[name="location_address"]').value = v.location_address || '';
      if (v.violation_datetime) $('#form-violation').querySelector('[name="violation_datetime"]').value = v.violation_datetime.replace(' ','T');
      $('#form-violation').querySelector('[name="description"]').value = v.description || '';
      new bootstrap.Modal($('#violation-form-modal')).show();
    } else alertMessage(r.message||'Failed to load','danger');
  }));
  $$('#violations-list .btn-delete').forEach(b=> b.addEventListener('click', async ()=>{
    const id = b.dataset.id;
    if (!confirm('Delete this violation?')) return;
    const r = (await api.delete(`/violations/${id}`)).data;
    if (r.status==='success') { alertMessage('Deleted','success'); loadViolations(); } else alertMessage(r.message||'Delete failed','danger');
  }));
}

async function loadObservations(page=1){
  show('#page-observations');
  const res = (await api.get(`/observations?page=${page}`)).data;
  if (!res || res.status!=='success') return alertMessage(res?.message||'Failed','danger');
  const items = res.data.items || [];
  renderTable($('#observations-list'), items, [
    { label:'#', key:'id' },
    { label:'Plate', key:'license_plate' },
    { label:'Vehicle', key:'vehicle_type_id' },
    { label:'Location', key:'location_address' },
    { label:'Date', key:'observation_datetime' },
    { label:'Actions', key:'actions', render: (row)=>{
      return `<div class="btn-group" role="group"><button class="btn btn-sm btn-outline-primary btn-edit" data-id="${row.id}">Edit</button><button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}">Delete</button></div>`;
    }}
  ]);
  await renderPagination($('#observationsPagination'), res.data.pagination || res.data.meta, p=> loadObservations(p));
  $$('#observations-list .btn-edit').forEach(b=> b.addEventListener('click', async ()=>{
    const id = b.dataset.id;
    const r = (await api.get(`/observations/${id}`)).data;
    if (r.status==='success'){
      const v = r.data;
      $('#observationId').value = v.id;
      // map numeric vehicle_type_id to select option value (using data-id)
      const vehSelect = $('#form-observation').querySelector('[name="vehicle_type"]');
      if (vehSelect) {
        const opt = Array.from(vehSelect.options).find(o=> o.dataset && String(o.dataset.id) === String(v.vehicle_type_id));
        if (opt) vehSelect.value = opt.value; else vehSelect.value = v.vehicle_type_id || '';
      }
      $('#form-observation').querySelector('[name="license_plate"]').value = v.license_plate || '';
      if (v.observation_datetime) $('#form-observation').querySelector('[name="observation_datetime"]').value = v.observation_datetime.replace(' ','T');
      $('#form-observation').querySelector('[name="location_address"]').value = v.location_address || '';
      $('#form-observation').querySelector('[name="speed_kmh"]').value = v.speed_kmh || '';
      new bootstrap.Modal($('#observation-form-modal')).show();
    } else alertMessage(r.message||'Failed to load','danger');
  }));
  $$('#observations-list .btn-delete').forEach(b=> b.addEventListener('click', async ()=>{
    const id = b.dataset.id;
    if (!confirm('Delete this observation?')) return;
    const r = (await api.delete(`/observations/${id}`)).data;
    if (r.status==='success') { alertMessage('Deleted','success'); loadObservations(); } else alertMessage(r.message||'Delete failed','danger');
  }));
}

document.addEventListener('DOMContentLoaded', async ()=>{
  // If opened via file:// the browser will block fetch requests for fragments.
  if (location.protocol === 'file:') {
    const area = $('#content-area');
    area.innerHTML = `
      <div class="alert alert-warning">
        Static file access is blocked by the browser. Serve the frontend directory over HTTP and open via http://localhost.
        <div class="mt-2"><small>Quick start (run inside frontend folder):</small>
          <ul>
            <li><code>python3 -m http.server 8080</code></li>
            <li><code>php -S localhost:9000</code></li>
          </ul>
        </div>
      </div>`;
    return;
  }
  // wire top-level nav to load pages (links with id nav-)
  document.querySelectorAll('a[id^="nav-"]').forEach(a=>{
    a.addEventListener('click', async (e)=>{
      e.preventDefault();
      const id = a.id.replace('nav-','');
      if (id === 'logout') { await api.logout(); updateAuth(); return loadPage('login'); }
      return loadPage(id);
    });
  });
  // remove any leftover sidebar toggle wiring (no-op if element missing)
  $('#btn-toggle-sidebar')?.remove();

  updateAuth();
  // If we have a refresh token but no access token, try silent refresh first
  try {
    if (api.refreshToken && !api.accessToken) {
      const ok = await api._tryRefresh();
      if (ok) { updateAuth(); await loadPage('dashboard'); return; }
    }
  } catch (e) { console.warn('silent refresh failed', e); }

  // If we have an access token, go to dashboard; otherwise show login
  if (api.accessToken) await loadPage('dashboard'); else await loadPage('login');
});
