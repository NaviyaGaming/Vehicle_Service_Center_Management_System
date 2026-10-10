// Live chat for the contact section of home.php
(function () {
  const API = window.CHAT_API || '../live-chat/api.php';
  const $ = id => document.getElementById(id);

  const form = $('contactForm');
  if (!form) return;

  let token = null;
  try { token = localStorage.getItem('chat_token'); } catch (e) {}
  let lastId = 0;
  let timer = null;

  function showChat() {
    form.style.display = 'none';
    $('chatPanel').hidden = false;
    lastId = 0;
    $('chatLog').innerHTML = '';
    poll();
    clearInterval(timer);
    timer = setInterval(poll, 2000);
  }

  function showForm() {
    clearInterval(timer);
    try { localStorage.removeItem('chat_token'); } catch (e) {}
    token = null;
    $('chatPanel').hidden = true;
    form.style.display = '';
    $('msg').value = '';
  }

  function addMessage(m) {
    const div = document.createElement('div');
    div.className = 'chat-msg ' + m.sender;
    div.textContent = m.message;                 // textContent keeps HTML from being injected
    const t = document.createElement('time');
    t.textContent = String(m.created_at).slice(11, 16);
    div.appendChild(t);
    $('chatLog').appendChild(div);
  }

  async function poll() {
    if (!token) return;
    try {
      const res = await fetch(API + '?action=user_fetch&token=' + encodeURIComponent(token) + '&after=' + lastId);
      if (res.status === 404) return showForm();  // conversation no longer exists
      const data = await res.json();
      const log = $('chatLog');
      const atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 60;
      data.messages.forEach(m => { addMessage(m); lastId = Math.max(lastId, +m.id); });
      if (data.messages.length && atBottom) log.scrollTop = log.scrollHeight;

      const closed = data.status === 'closed';
      $('chatInput').disabled = closed;
      $('chatSend').disabled = closed;
      $('chatClosed').hidden = !closed;
      $('chatState').textContent = 'Connected';
    } catch (e) {
      $('chatState').textContent = 'Reconnecting...';
    }
  }

  // Contact form submit: creates the conversation and opens the chat
  form.addEventListener('submit', async e => {
    e.preventDefault();
    $('formError').textContent = '';
    $('sendBtn').disabled = true;
    try {
      const res = await fetch(API + '?action=start', { method: 'POST', body: new FormData(form) });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Something went wrong. Please try again.');
      if (data.token) {
        token = data.token;
        try { localStorage.setItem('chat_token', token); } catch (e) {}
        showChat();
      }
    } catch (err) {
      $('formError').textContent = err.message;
    }
    $('sendBtn').disabled = false;
  });

  // Visitor sends a chat message
  $('chatForm').addEventListener('submit', async e => {
    e.preventDefault();
    const text = $('chatInput').value.trim();
    if (!text || !token) return;
    $('chatInput').value = '';
    const body = new FormData();
    body.append('token', token);
    body.append('message', text);
    await fetch(API + '?action=user_send', { method: 'POST', body });
    await poll();
    $('chatLog').scrollTop = $('chatLog').scrollHeight;
  });

  $('chatNew').addEventListener('click', showForm);

  if (token) showChat();                          // resume after a page refresh
})();
