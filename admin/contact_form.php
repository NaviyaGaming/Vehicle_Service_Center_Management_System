<?php /* PUBLIC page: contact form + live chat for the visitor */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contact us</title>
<style>
  :root { --blue:#0b4fc2; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --field:#f8fafc; }
  * { box-sizing: border-box; }
  body { margin:0; background:#f8fafc; font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:var(--ink); }
  .card { max-width:700px; margin:40px auto; background:#fff; border:1px solid var(--line); border-radius:10px; padding:40px; box-shadow:0 2px 6px rgba(15,23,42,.06); }
  label { display:block; font-weight:500; margin-bottom:8px; }
  input, textarea { width:100%; padding:14px 18px; border:1px solid var(--line); background:var(--field); border-radius:10px; font:inherit; color:inherit; }
  input:focus, textarea:focus { outline:2px solid var(--blue); outline-offset:1px; }
  textarea { min-height:120px; resize:vertical; }
  .row { display:flex; gap:20px; } .row > div { flex:1; }
  .field { margin-bottom:20px; }
  .hp { position:absolute; left:-9999px; }          /* honeypot */
  button { width:100%; padding:16px; border:0; border-radius:8px; background:var(--blue); color:#fff; font-weight:600; font-size:17px; font-family:inherit; cursor:pointer; box-shadow:0 6px 16px rgba(11,79,194,.25); }
  button:disabled { opacity:.6; cursor:default; }
  .error { color:#b91c1c; margin:0 0 14px; min-height:1.2em; }

  /* chat view */
  #chatView { display:none; }
  .chat-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
  .chat-head h2 { margin:0; font-size:20px; }
  .chat-head small { color:var(--muted); }
  #log { height:380px; overflow-y:auto; border:1px solid var(--line); border-radius:10px; background:var(--field); padding:16px; display:flex; flex-direction:column; gap:10px; }
  .msg { max-width:78%; padding:10px 14px; border-radius:14px; line-height:1.4; white-space:pre-wrap; word-wrap:break-word; }
  .msg.user  { align-self:flex-end; background:var(--blue); color:#fff; border-bottom-right-radius:4px; }
  .msg.admin { align-self:flex-start; background:#fff; border:1px solid var(--line); border-bottom-left-radius:4px; }
  .msg time { display:block; font-size:11px; opacity:.65; margin-top:4px; }
  .composer { display:flex; gap:10px; margin-top:12px; }
  .composer input { flex:1; }
  .composer button { width:auto; padding:0 24px; box-shadow:none; }
  .note { color:var(--muted); font-size:14px; margin-top:10px; }
  @media (max-width:560px){ .row{flex-direction:column; gap:0;} .card{margin:0; border-radius:0; padding:24px;} }
</style>
</head>
<body>
<div class="card">

  <!-- 1) Contact form -->
  <form id="contactForm" novalidate>
    <div class="row">
      <div class="field"><label for="first_name">First name</label><input id="first_name" name="first_name" placeholder="First name" maxlength="60" required></div>
      <div class="field"><label for="last_name">Last name</label><input id="last_name" name="last_name" placeholder="Last name" maxlength="60" required></div>
    </div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" placeholder="Enter your email" maxlength="150" required></div>
    <div class="field"><label for="message">Message</label><textarea id="message" name="message" maxlength="2000" placeholder="Tell us about your vehicle or the service you need..." required></textarea></div>
    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <p class="error" id="formError" role="alert"></p>
    <button type="submit" id="sendBtn">Send message</button>
  </form>

  <!-- 2) Live chat (shown after the form is sent) -->
  <div id="chatView">
    <div class="chat-head"><h2>Your conversation</h2><small id="state">Connected</small></div>
    <div id="log" aria-live="polite"></div>
    <form class="composer" id="chatForm">
      <input id="chatInput" placeholder="Type a message..." maxlength="2000" autocomplete="off">
      <button type="submit" id="chatSend">Send</button>
    </form>
    <p class="note" id="closedNote" style="display:none">This conversation has been closed. Send a new message from the contact form if you need more help.</p>
  </div>

</div>

<script>
const API = 'api.php';
const $ = id => document.getElementById(id);
let token = localStorage.getItem('chat_token');
let lastId = 0, timer = null;

function showChat() {
  $('contactForm').style.display = 'none';
  $('chatView').style.display = 'block';
  lastId = 0; $('log').innerHTML = '';
  poll(); timer = setInterval(poll, 2000);
}
function showForm() {
  clearInterval(timer);
  localStorage.removeItem('chat_token'); token = null;
  $('chatView').style.display = 'none';
  $('contactForm').style.display = 'block';
}

function addMessage(m) {
  const div = document.createElement('div');
  div.className = 'msg ' + m.sender;
  div.textContent = m.message;                       // textContent = safe against HTML injection
  const t = document.createElement('time');
  t.textContent = m.created_at.slice(11, 16);
  div.appendChild(t);
  $('log').appendChild(div);
}

async function poll() {
  try {
    const res = await fetch(`${API}?action=user_fetch&token=${encodeURIComponent(token)}&after=${lastId}`);
    if (res.status === 404) return showForm();       // token no longer valid
    const data = await res.json();
    const log = $('log');
    const atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 60;
    data.messages.forEach(m => { addMessage(m); lastId = Math.max(lastId, +m.id); });
    if (data.messages.length && atBottom) log.scrollTop = log.scrollHeight;
    const closed = data.status === 'closed';
    $('chatInput').disabled = $('chatSend').disabled = closed;
    $('closedNote').style.display = closed ? 'block' : 'none';
    $('state').textContent = 'Connected';
  } catch (e) { $('state').textContent = 'Reconnecting...'; }
}

$('contactForm').addEventListener('submit', async e => {
  e.preventDefault();
  $('formError').textContent = '';
  $('sendBtn').disabled = true;
  try {
    const res = await fetch(`${API}?action=start`, { method:'POST', body:new FormData(e.target) });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Something went wrong.');
    if (data.token) { token = data.token; localStorage.setItem('chat_token', token); showChat(); }
  } catch (err) { $('formError').textContent = err.message; }
  $('sendBtn').disabled = false;
});

$('chatForm').addEventListener('submit', async e => {
  e.preventDefault();
  const text = $('chatInput').value.trim();
  if (!text) return;
  $('chatInput').value = '';
  const body = new FormData(); body.append('token', token); body.append('message', text);
  await fetch(`${API}?action=user_send`, { method:'POST', body });
  poll();
  $('log').scrollTop = $('log').scrollHeight;
});

if (token) showChat();                                // resume an existing conversation
</script>
</body>
</html>
