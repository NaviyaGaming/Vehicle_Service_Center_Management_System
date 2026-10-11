(function () {
    const API = window.CHAT_API || '../admin/api.php';

    const container = document.createElement('div');
    container.innerHTML = `
        <button class="floating-chat-btn" id="floatingChatBtn" title="Chat with Admin">
            <i class="fa-solid fa-comments"></i>
        </button>

        <div class="chat-modal-overlay" id="chatModalOverlay">
            <div class="chat-modal-card">
                <div class="chat-modal-header">
                    <div>
                        <h3>Chat with Admin</h3>
                        <span class="chat-state" id="floatingChatState" style="font-size: 11px; color: var(--text-muted);">Connected</span>
                    </div>
                    <button class="chat-modal-close" id="chatModalClose">&times;</button>
                </div>
                <div class="chat-modal-body">
                    <form id="floatingStartForm">
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 13px; font-weight: 500; display: block; margin-bottom: 4px;">Your Name</label>
                            <input type="text" id="flName" required placeholder="Full Name" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:6px; font-size:14px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 13px; font-weight: 500; display: block; margin-bottom: 4px;">Email</label>
                            <input type="email" id="flEmail" required placeholder="your@email.com" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:6px; font-size:14px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 13px; font-weight: 500; display: block; margin-bottom: 4px;">Initial Message</label>
                            <textarea id="flMsg" required placeholder="How can we help you today?" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:6px; font-size:14px; min-height:80px;"></textarea>
                        </div>
                        <p id="flError" style="color:#EF4444; font-size:12px; margin-bottom:8px;"></p>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Start Chat</button>
                    </form>

                    <div id="floatingChatPanel" hidden>
                        <div class="chat-log" id="flChatLog" aria-live="polite" style="height:280px; overflow-y:auto; padding:12px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; display:flex; flex-direction:column; gap:8px;"></div>
                        <form class="chat-composer" id="flChatForm" style="display:flex; gap:8px; margin-top:12px;">
                            <input id="flChatInput" type="text" maxlength="2000" placeholder="Type a message..." autocomplete="off" style="flex:1; padding:10px; border:1px solid #E2E8F0; border-radius:6px; font-size:14px;">
                            <button type="submit" class="btn btn-primary" style="padding:0 16px;">Send</button>
                        </form>
                        <p class="chat-note" id="flChatClosed" hidden style="font-size:12px; color:#64748B; margin-top:8px;">Conversation closed by admin.</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(container);

    const btn = document.getElementById('floatingChatBtn');
    const overlay = document.getElementById('chatModalOverlay');
    const closeBtn = document.getElementById('chatModalClose');
    
    let token = null;
    try { token = localStorage.getItem('chat_token'); } catch (e) {}
    let lastId = 0;
    let timer = null;

    function openModal() {
        overlay.classList.add('active');
        if (token) {
            document.getElementById('floatingStartForm').hidden = true;
            document.getElementById('floatingChatPanel').hidden = false;
            lastId = 0;
            document.getElementById('flChatLog').innerHTML = '';
            poll();
            clearInterval(timer);
            timer = setInterval(poll, 2000);
        }
    }

    function closeModal() {
        overlay.classList.remove('active');
        clearInterval(timer);
    }

    btn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

    function addMessage(m) {
        const log = document.getElementById('flChatLog');
        const div = document.createElement('div');
        div.className = 'chat-msg ' + m.sender;
        div.textContent = m.message;
        const t = document.createElement('time');
        t.textContent = String(m.created_at).slice(11, 16);
        div.appendChild(t);
        log.appendChild(div);
    }

    async function poll() {
        if (!token) return;
        try {
            const res = await fetch(API + '?action=user_fetch&token=' + encodeURIComponent(token) + '&after=' + lastId);
            if (res.status === 404) {
                localStorage.removeItem('chat_token');
                token = null;
                document.getElementById('floatingChatPanel').hidden = true;
                document.getElementById('floatingStartForm').hidden = false;
                return;
            }
            const data = await res.json();
            const log = document.getElementById('flChatLog');
            const atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 60;
            data.messages.forEach(m => { addMessage(m); lastId = Math.max(lastId, +m.id); });
            if (data.messages.length && atBottom) log.scrollTop = log.scrollHeight;

            const closed = data.status === 'closed';
            document.getElementById('flChatInput').disabled = closed;
            document.getElementById('flChatClosed').hidden = !closed;
        } catch (e) {
            document.getElementById('floatingChatState').textContent = 'Reconnecting...';
        }
    }

    document.getElementById('floatingStartForm').addEventListener('submit', async e => {
        e.preventDefault();
        document.getElementById('flError').textContent = '';
        const formData = new FormData();
        const fullNames = document.getElementById('flName').value.trim().split(' ');
        formData.append('first_name', fullNames[0] || 'User');
        formData.append('last_name', fullNames.slice(1).join(' ') || '');
        formData.append('email', document.getElementById('flEmail').value.trim());
        formData.append('message', document.getElementById('flMsg').value.trim());

        try {
            const res = await fetch(API + '?action=start', { method: 'POST', body: formData });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Failed to start chat.');
            if (data.token) {
                token = data.token;
                localStorage.setItem('chat_token', token);
                document.getElementById('floatingStartForm').hidden = true;
                document.getElementById('floatingChatPanel').hidden = false;
                openModal();
            }
        } catch (err) {
            document.getElementById('flError').textContent = err.message;
        }
    });

    document.getElementById('flChatForm').addEventListener('submit', async e => {
        e.preventDefault();
        const input = document.getElementById('flChatInput');
        const text = input.value.trim();
        if (!text || !token) return;
        input.value = '';
        const body = new FormData();
        body.append('token', token);
        body.append('message', text);
        await fetch(API + '?action=user_send', { method: 'POST', body });
        await poll();
        const log = document.getElementById('flChatLog');
        log.scrollTop = log.scrollHeight;
    });
})();