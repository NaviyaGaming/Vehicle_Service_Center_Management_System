<?php
/* ADMIN page: read and reply to visitors in real time.
   Put this file inside your admin site (or include it from a page of your admin layout). */
require __DIR__ . '/config.php';

if (!is_admin()) {
    http_response_code(403);
    exit('Access denied. Please log in as admin.');   // or: header('Location: /admin/login.php'); exit;
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customer messages</title>
<style>
  :root { --blue:#0b4fc2; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --bg:#f1f5f9; --green:#16a34a; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:var(--ink); background:var(--bg); }
  .wrap { display:grid; grid-template-columns:340px 1fr; height:calc(100vh - 32px); margin:16px; background:#fff; border:1px solid var(--line); border-radius:10px; overflow:hidden; }

  /* conversation list */
  .list { border-right:1px solid var(--line); overflow-y:auto; }
  .list h1 { font-size:18px; margin:0; padding:18px 20px; border-bottom:1px solid var(--line); }
  .item { padding:14px 20px; border-bottom:1px solid var(--line); cursor:pointer; }
  .item:hover { background:#f8fafc; }
  .item.active { background:#eaf1fd; box-shadow:inset 3px 0 0 var(--blue); }
  .item .top { display:flex; justify-content:space-between; gap:8px; align-items:center; }
  .item .name { font-weight:600; display:flex; align-items:center; gap:8px; }
  .dot { width:9px; height:9px; border-radius:50%; background:#cbd5e1; flex:none; }
  .dot.on { background:var(--green); }
  .badge { background:var(--blue); color:#fff; border-radius:999px; font-size:12px; padding:2px 8px; font-weight:600; }
  .item .last { color:var(--muted); font-size:14px; margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .item .closed { color:var(--muted); font-size:12px; }
  .empty { padding:30px 20px; color:var(--muted); }

  /* chat panel */
  .panel { display:flex; flex-direction:column; min-width:0; }
  .panel-head { padding:16px 22px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; gap:12px; }
  .panel-head .who { font-weight:600; font-size:17px; }
  .panel-head .mail { color:var(--muted); font-size:14px; }
  .panel-head button { border:1px solid var(--line); background:#fff; border-radius:8px; padding:8px 14px; cursor:pointer; font:inherit; }
  #log { flex:1; overflow-y:auto; padding:20px 22px; background:#f8fafc; display:flex; flex-direction:column; gap:10px; }
  .msg { max-width:70%; padding:10px 14px; border-radius:14px; line-height:1.4; white-space:pre-wrap; word-wrap:break-word; }
  .msg.user  { align-self:flex-start; background:#fff; border:1px solid var(--line); border-bottom-left-radius:4px; }
  .msg.admin { align-self:flex-end; background:var(--blue); color:#fff; border-bottom-right-radius:4px; }
  .msg time { display:block; font-size:11px; opacity:.65; margin-top:4px; }
  .composer { display:flex; gap:10px; padding:14px 22px; border-top:1px solid var(--line); }
  .composer textarea { flex:1; resize:none; height:46px; padding:12px 14px; border:1px solid var(--line); border-radius:10px; font:inherit; }
  .composer textarea:focus { outline:2px solid var(--blue); outline-offset:1px; }
  .composer button { border:0; background:var(--blue); color:#fff; border-radius:8px; padding:0 24px; font-weight:600; font-size:15px; font-family:inherit; cursor:pointer; }
  .composer button:disabled, .composer textarea:disabled { opacity:.55; }
  .placeholder { margin:auto; color:var(--muted); }
  @media (max-width:760px){ .wrap{ grid-template-columns:1fr; } .list{ max-height:35vh; border-right:0; border-bottom:1px solid var(--line);} }
</style>
</head>
<body>
<div class="wrap">
  <aside class="list">
    <h1>Customer messages</h1>
    <div id="sessions"><div class="empty">Loading...</div></div>
  </aside>

  <section class="panel">
    <div class="panel-head" id="head" style="display:none">
      <div><div class="who" id="who"></div><div class="mail" id="mail"></div></div>
      <button id="toggleBtn" type="button">Close chat</button>
    </div>
    <div id="log"><div class="placeholder">Select a conversation to start replying.</div></div>
    <form class="composer" id="composer" style="display:none">
      <textarea id="reply" placeholder="Write a reply... (Enter to send, Shift+Enter for a new line)" maxlength="2000"></textarea>
      <button type="submit" id="sendBtn">Send</button>
    </form>
  </section>
</div>

<script>
const API  = 'api.php';
const CSRF = <?= json_encode($_SESSION['csrf']) ?>;
const $ = id => document.getElementById(id);

let currentId = null, lastId = 0, currentStatus = 'open', lastUnreadTotal = 0;

async function get(params) {
  const res = await fetch(`${API}?${new URLSearchParams(params)}`);
  if (res.status === 403) { location.reload(); return null; }   // session expired
  return res.json();
}
async function post(action, fields) {
  const body = new FormData();
  for (const k in fields) body.append(k, fields[k]);
  const res = await fetch(`${API}?action=${action}`, { method:'POST', body, headers:{ 'X-CSRF-Token': CSRF } });
  return res.json();
}

/* ---------- conversation list ---------- */
async function loadSessions() {
  const data = await get({ action:'admin_sessions' });
  if (!data) return;
  const box = $('sessions');
  box.innerHTML = '';
  if (!data.sessions.length) { box.innerHTML = '<div class="empty">No messages yet.</div>'; return; }

  let unreadTotal = 0;
  data.sessions.forEach(s => {
    unreadTotal += +s.unread;
    const item = document.createElement('div');
    item.className = 'item' + (+s.id === currentId ? ' active' : '');

    const top = document.createElement('div'); top.className = 'top';
    const name = document.createElement('div'); name.className = 'name';
    const dot = document.createElement('span'); dot.className = 'dot' + (+s.online ? ' on' : '');
    dot.title = +s.online ? 'Online now' : 'Offline';
    name.append(dot, document.createTextNode(`${s.first_name} ${s.last_name}`));
    top.appendChild(name);
    if (+s.unread > 0 && +s.id !== currentId) {
      const b = document.createElement('span'); b.className = 'badge'; b.textContent = s.unread; top.appendChild(b);
    }

    const last = document.createElement('div'); last.className = 'last'; last.textContent = s.last_message || '';
    item.append(top, last);
    if (s.status === 'closed') {
      const c = document.createElement('div'); c.className = 'closed'; c.textContent = 'Closed'; item.appendChild(c);
    }
    item.addEventListener('click', () => openSession(s));
    box.appendChild(item);
  });

  document.title = unreadTotal ? `(${unreadTotal}) Customer messages` : 'Customer messages';
  lastUnreadTotal = unreadTotal;
}

/* ---------- open a conversation ---------- */
function openSession(s) {
  currentId = +s.id; lastId = 0;
  $('log').innerHTML = '';
  $('who').textContent = `${s.first_name} ${s.last_name}`;
  $('mail').textContent = s.email;
  $('head').style.display = 'flex';
  $('composer').style.display = 'flex';
  setStatus(s.status);
  loadMessages(true);
  loadSessions();
  $('reply').focus();
}

function setStatus(status) {
  currentStatus = status;
  $('toggleBtn').textContent = status === 'open' ? 'Close chat' : 'Reopen chat';
  $('reply').disabled = $('sendBtn').disabled = (status === 'closed');
  $('reply').placeholder = status === 'closed'
    ? 'This chat is closed. Reopen it to reply.'
    : 'Write a reply... (Enter to send, Shift+Enter for a new line)';
}

function addMessage(m) {
  const div = document.createElement('div');
  div.className = 'msg ' + m.sender;
  div.textContent = m.message;                        // safe: no HTML injection from visitors
  const t = document.createElement('time');
  t.textContent = m.created_at.slice(0, 16).replace('T', ' ');
  div.appendChild(t);
  $('log').appendChild(div);
}

async function loadMessages(forceScroll = false) {
  if (!currentId) return;
  const id = currentId;
  const data = await get({ action:'admin_fetch', session_id:id, after:lastId });
  if (!data || id !== currentId) return;              // user switched conversation meanwhile
  const log = $('log');
  const atBottom = forceScroll || log.scrollHeight - log.scrollTop - log.clientHeight < 80;
  data.messages.forEach(m => { addMessage(m); lastId = Math.max(lastId, +m.id); });
  if (data.messages.length && atBottom) log.scrollTop = log.scrollHeight;
  if (data.status !== currentStatus) setStatus(data.status);
}

/* ---------- actions ---------- */
$('composer').addEventListener('submit', async e => {
  e.preventDefault();
  const text = $('reply').value.trim();
  if (!text || !currentId) return;
  $('reply').value = '';
  const r = await post('admin_send', { session_id: currentId, message: text });
  if (r.error) alert(r.error);
  await loadMessages(true);
  loadSessions();
});
$('reply').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('composer').requestSubmit(); }
});
$('toggleBtn').addEventListener('click', async () => {
  if (!currentId) return;
  const next = currentStatus === 'open' ? 'closed' : 'open';
  await post('admin_status', { session_id: currentId, status: next });
  setStatus(next);
  loadSessions();
});

/* ---------- live updates (polling) ---------- */
loadSessions();
setInterval(loadSessions, 3000);
setInterval(() => loadMessages(false), 2000);
</script>
</body>
</html>
