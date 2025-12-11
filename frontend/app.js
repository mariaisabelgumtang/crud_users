const apiBase = 'http://localhost:8000'; // assume same host serving backend or use full URL like http://localhost:8000

function qs(sel, root = document) { return root.querySelector(sel); }
function qsa(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

async function fetchJSON(path, opts = {}){
  const res = await fetch(path, Object.assign({headers:{'Content-Type':'application/json'}}, opts));
  const text = await res.text();
  let data = null;
  try { data = text ? JSON.parse(text) : null; } catch(e){ data = text; }
  if (!res.ok) throw {status: res.status, body: data};
  return data;
}

async function loadUsers(){
  const ul = qs('#users'); ul.innerHTML = 'Loading...';
  try{
    const users = await fetchJSON(apiBase + '/users');
    ul.innerHTML = '';
    users.forEach(u => {
      const li = document.createElement('li');
      li.innerHTML = `<div><strong>${u.first_name} ${u.last_name}</strong> <span class="muted">${u.email}</span></div>`;
      const actions = document.createElement('div');
      const view = document.createElement('button'); view.textContent = 'View';
      view.onclick = () => showUser(u.id);
      const del = document.createElement('button'); del.textContent = 'Delete'; del.style.background='#c0392b';
      del.onclick = async ()=>{
        if (!confirm('Delete user?')) return;
        try{ await fetchJSON(apiBase + `/users/${u.id}`, {method:'DELETE'}); loadUsers(); } catch(e){ alert('Delete failed'); }
      };
      actions.appendChild(view); actions.appendChild(del);
      li.appendChild(actions);
      ul.appendChild(li);
    });
  }catch(e){ ul.innerHTML = '<li>Error loading users</li>'; }
}

function collectForm(){
  const f = qs('#userForm');
  const fm = new FormData(f);
  const data = {
    email: fm.get('email'),
    first_name: fm.get('first_name'),
    last_name: fm.get('last_name'),
    mobile_number: fm.get('mobile_number'),
    birth_date: fm.get('birth_date'),
    addresses: []
  };
  qsa('#addresses .addr').forEach(a=>{
    const street = a.querySelector('[name=street]').value;
    const barangay = a.querySelector('[name=barangay]').value;
    const city = a.querySelector('[name=city]').value;
    data.addresses.push({street, barangay, city});
  });
  return data;
}

qs('#userForm').addEventListener('submit', async (ev)=>{
  ev.preventDefault();
  qs('#formError').textContent = '';
  const data = collectForm();
  try{
    const created = await fetchJSON(apiBase + '/users', {method:'POST', body: JSON.stringify(data)});
    qs('#userForm').reset();
    // remove extra addresses
    qsa('#addresses .addr').forEach((el,i)=>{ if(i>0) el.remove(); });
    loadUsers();
    showUser(created.id);
  }catch(err){
    if (err.body && err.body.errors) qs('#formError').textContent = JSON.stringify(err.body.errors);
    else qs('#formError').textContent = 'Create failed';
  }
});

qs('#addAddress').addEventListener('click', ()=>{
  const container = qs('#addresses');
  const idx = qsa('#addresses .addr').length;
  const div = document.createElement('div'); div.className='addr'; div.dataset.index = idx;
  div.innerHTML = `
    <div class="row"><label>Street</label><input name="street"></div>
    <div class="row"><label>Barangay</label><input name="barangay" required></div>
    <div class="row"><label>City</label><input name="city" required></div>
    <div class="row"><button type="button" class="removeAddr">Remove</button></div>
  `;
  container.appendChild(div);
  div.querySelector('.removeAddr').onclick = ()=>div.remove();
});

async function showUser(id){
  try{
    const u = await fetchJSON(apiBase + `/users/${id}`);
    const el = qs('#detailContent'); el.innerHTML = '';
    el.appendChild(document.createElement('div')).textContent = `${u.first_name} ${u.last_name} — ${u.email}`;
    el.appendChild(document.createElement('div')).textContent = `Mobile: ${u.mobile_number}`;
    el.appendChild(document.createElement('div')).textContent = `Birth date: ${u.birth_date}`;
    const h = document.createElement('h3'); h.textContent = 'Addresses'; el.appendChild(h);
    const ul = document.createElement('ul'); ul.style.paddingLeft='16px';
    (u.addresses||[]).forEach(a=>{
      const li = document.createElement('li');
      li.textContent = `${a.street||''} — ${a.barangay}, ${a.city}`;
      ul.appendChild(li);
    });
    el.appendChild(ul);
    qs('#detail').classList.remove('hidden');
  }catch(e){ alert('Failed to load user'); }
}

qs('#closeDetail').addEventListener('click', ()=>qs('#detail').classList.add('hidden'));
qs('#refresh').addEventListener('click', loadUsers);

// initial load
loadUsers();
