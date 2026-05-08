<?php
define('FBCAST_PAGE_CONTEXT', true);
require_once __DIR__ . '/config/load-env.php';

if (empty($_SESSION['fb_user_id'])) {
    header('Location: /');
    exit;
}
$csrfToken = getCsrfToken();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inbox — FBCast Pro</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0c10;
  --surface:#12151c;
  --surface2:#1a1d26;
  --surface3:#22263300;
  --border:rgba(255,255,255,.07);
  --border2:rgba(255,255,255,.12);
  --primary:#0866FF;
  --primary-dark:#0550c8;
  --text:#e4e6eb;
  --text2:#94a3b8;
  --text3:#64748b;
  --green:#22c55e;
  --red:#ef4444;
  --unread:#0866FF;
  --bubble-in:#1e2230;
  --bubble-out:#0866FF;
  --radius:12px;
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);height:100vh;overflow:hidden;display:flex;flex-direction:column}

/* ── TOP BAR ── */
.ib-topbar{
  display:flex;align-items:center;gap:12px;
  padding:0 20px;height:56px;
  background:var(--surface);border-bottom:1px solid var(--border);
  flex-shrink:0;
}
.ib-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text)}
.ib-brand img{width:28px;height:28px;border-radius:8px;object-fit:cover}
.ib-brand span{font-weight:700;font-size:15px}
.ib-title{font-weight:600;font-size:15px;color:var(--text2);margin-left:4px}
.ib-topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px}
.ib-btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 14px;border-radius:8px;border:1px solid var(--border2);
  background:var(--surface2);color:var(--text);font-size:13px;font-weight:500;
  cursor:pointer;transition:all .15s;text-decoration:none
}
.ib-btn:hover{background:var(--primary);border-color:var(--primary);color:#fff}
.ib-btn-primary{background:var(--primary);border-color:var(--primary);color:#fff}
.ib-btn-primary:hover{background:var(--primary-dark)}
#syncStatus{font-size:12px;color:var(--text3)}

/* ── BODY LAYOUT ── */
.ib-body{display:flex;flex:1;overflow:hidden}

/* ── LEFT PANEL — Conversations ── */
.ib-left{
  width:300px;flex-shrink:0;
  display:flex;flex-direction:column;
  border-right:1px solid var(--border);
  background:var(--surface);
}
.ib-left-header{
  padding:14px 16px 10px;border-bottom:1px solid var(--border);
}
.ib-left-header h2{font-size:16px;font-weight:700;margin-bottom:10px}
.ib-search{
  display:flex;align-items:center;gap:8px;
  background:var(--surface2);border:1px solid var(--border);
  border-radius:8px;padding:8px 12px;
}
.ib-search i{color:var(--text3);font-size:13px}
.ib-search input{
  background:none;border:none;outline:none;color:var(--text);
  font-size:13px;width:100%;font-family:inherit
}
.ib-search input::placeholder{color:var(--text3)}
.ib-conv-list{flex:1;overflow-y:auto}
.ib-conv-list::-webkit-scrollbar{width:4px}
.ib-conv-list::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}

.conv-item{
  display:flex;align-items:center;gap:10px;
  padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border);
  transition:background .1s;position:relative
}
.conv-item:hover{background:var(--surface2)}
.conv-item.active{background:rgba(8,102,255,.12);border-right:3px solid var(--primary)}
.conv-avatar{
  width:42px;height:42px;border-radius:50%;
  background:linear-gradient(135deg,var(--primary),#7c3aed);
  display:flex;align-items:center;justify-content:center;
  font-size:16px;font-weight:700;color:#fff;flex-shrink:0;
  overflow:hidden;
}
.conv-avatar img{width:100%;height:100%;object-fit:cover}
.conv-info{flex:1;min-width:0}
.conv-name{
  font-size:13.5px;font-weight:600;color:var(--text);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  margin-bottom:3px
}
.conv-preview{
  font-size:12px;color:var(--text3);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis
}
.conv-preview.unread{color:var(--text);font-weight:500}
.conv-meta{display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0}
.conv-time{font-size:11px;color:var(--text3)}
.conv-badge{
  background:var(--primary);color:#fff;
  font-size:10px;font-weight:700;
  min-width:18px;height:18px;border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  padding:0 4px
}
.conv-empty{
  padding:40px 20px;text-align:center;color:var(--text3);font-size:13px
}

/* ── MIDDLE PANEL — Chat ── */
.ib-chat{
  flex:1;display:flex;flex-direction:column;overflow:hidden;
  background:var(--bg)
}
.chat-empty-state{
  flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;
  color:var(--text3);gap:16px
}
.chat-empty-state i{font-size:48px;opacity:.3}
.chat-empty-state p{font-size:14px}

.chat-header{
  display:flex;align-items:center;gap:12px;
  padding:14px 20px;border-bottom:1px solid var(--border);
  background:var(--surface);flex-shrink:0
}
.chat-header-avatar{
  width:36px;height:36px;border-radius:50%;
  background:linear-gradient(135deg,var(--primary),#7c3aed);
  display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:700;color:#fff;overflow:hidden;flex-shrink:0
}
.chat-header-avatar img{width:100%;height:100%;object-fit:cover}
.chat-header-info{flex:1}
.chat-header-name{font-size:14px;font-weight:700}
.chat-header-sub{font-size:12px;color:var(--text3)}

.chat-messages{
  flex:1;overflow-y:auto;padding:20px 20px 8px;
  display:flex;flex-direction:column;gap:6px
}
.chat-messages::-webkit-scrollbar{width:4px}
.chat-messages::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}

/* Date divider */
.msg-date-divider{
  text-align:center;margin:12px 0;
}
.msg-date-divider span{
  font-size:11px;color:var(--text3);background:var(--surface2);
  padding:3px 12px;border-radius:20px
}

/* Message bubbles */
.msg-row{display:flex;align-items:flex-end;gap:8px;max-width:75%}
.msg-row.out{align-self:flex-end;flex-direction:row-reverse}
.msg-row.in{align-self:flex-start}
.msg-bubble-avatar{
  width:28px;height:28px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--primary),#7c3aed);
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:700;color:#fff;overflow:hidden
}
.msg-bubble-avatar img{width:100%;height:100%;object-fit:cover}
.msg-bubble{
  padding:10px 14px;border-radius:18px;
  font-size:13.5px;line-height:1.5;word-break:break-word;
  max-width:100%
}
.msg-row.in  .msg-bubble{background:var(--bubble-in);border-bottom-left-radius:4px}
.msg-row.out .msg-bubble{background:var(--bubble-out);color:#fff;border-bottom-right-radius:4px}
.msg-time{font-size:10.5px;color:var(--text3);margin-top:2px;padding:0 4px}
.msg-row.out .msg-time{text-align:right}
.msg-attachment a{color:inherit;text-decoration:underline;opacity:.85}

/* Reply box */
.chat-reply{
  padding:14px 20px;border-top:1px solid var(--border);
  background:var(--surface);flex-shrink:0
}
.reply-inner{
  display:flex;align-items:flex-end;gap:10px;
  background:var(--surface2);border:1px solid var(--border2);
  border-radius:12px;padding:10px 14px;
  transition:border-color .15s
}
.reply-inner:focus-within{border-color:var(--primary)}
#replyText{
  flex:1;background:none;border:none;outline:none;
  color:var(--text);font-size:13.5px;font-family:inherit;
  resize:none;min-height:20px;max-height:120px;line-height:1.5
}
#replyText::placeholder{color:var(--text3)}
#sendBtn{
  width:36px;height:36px;border-radius:50%;border:none;
  background:var(--primary);color:#fff;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  font-size:15px;flex-shrink:0;transition:background .15s
}
#sendBtn:hover{background:var(--primary-dark)}
#sendBtn:disabled{background:var(--surface3);opacity:.5;cursor:not-allowed}
.reply-hint{font-size:11px;color:var(--text3);margin-top:6px;padding:0 2px}

/* ── RIGHT PANEL — Profile ── */
.ib-right{
  width:260px;flex-shrink:0;
  border-left:1px solid var(--border);
  background:var(--surface);
  display:flex;flex-direction:column;overflow-y:auto
}
.ib-right::-webkit-scrollbar{width:4px}
.ib-right::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}
.profile-top{padding:28px 20px 20px;text-align:center;border-bottom:1px solid var(--border)}
.profile-avatar{
  width:64px;height:64px;border-radius:50%;margin:0 auto 12px;
  background:linear-gradient(135deg,var(--primary),#7c3aed);
  display:flex;align-items:center;justify-content:center;
  font-size:26px;font-weight:700;color:#fff;overflow:hidden
}
.profile-avatar img{width:100%;height:100%;object-fit:cover}
.profile-name{font-size:15px;font-weight:700;margin-bottom:4px}
.profile-sub{font-size:12px;color:var(--text3)}
.profile-section{padding:16px 20px;border-bottom:1px solid var(--border)}
.profile-section h4{font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px}
.profile-stat{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.profile-stat span:first-child{font-size:12px;color:var(--text2)}
.profile-stat span:last-child{font-size:12px;font-weight:600;color:var(--text)}
.profile-empty{padding:40px 20px;text-align:center;color:var(--text3);font-size:13px}

/* Loading */
.ib-loading{display:flex;align-items:center;justify-content:center;gap:8px;padding:20px;color:var(--text3);font-size:13px}
.spin{animation:spin .7s linear infinite;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}

/* Toast */
.ib-toast{
  position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
  background:#1e2230;border:1px solid var(--border2);color:var(--text);
  padding:10px 20px;border-radius:10px;font-size:13px;
  z-index:9999;opacity:0;transition:opacity .3s;pointer-events:none
}
.ib-toast.show{opacity:1}
.ib-toast.error{border-color:var(--red);color:#fca5a5}
.ib-toast.success{border-color:var(--green);color:#86efac}

@media(max-width:900px){.ib-right{display:none}}
@media(max-width:600px){.ib-left{width:240px}}
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="ib-topbar">
  <a href="/" class="ib-brand">
    <img src="images/castpro2.png" alt="FBCast Pro">
    <span>FBCast Pro</span>
  </a>
  <span class="ib-title">/ Inbox</span>
  <div class="ib-topbar-right">
    <span id="syncStatus"></span>
    <button class="ib-btn" id="syncBtn" onclick="doSync()">
      <i class="fa-solid fa-rotate"></i> Sync
    </button>
    <a href="/" class="ib-btn">
      <i class="fa-solid fa-arrow-left"></i> Dashboard
    </a>
  </div>
</div>

<!-- BODY -->
<div class="ib-body">

  <!-- LEFT: Conversations -->
  <div class="ib-left">
    <div class="ib-left-header">
      <h2>Conversations</h2>
      <div class="ib-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search by name…">
      </div>
    </div>
    <div class="ib-conv-list" id="convList">
      <div class="ib-loading"><i class="fa-solid fa-circle-notch spin"></i> Loading…</div>
    </div>
  </div>

  <!-- MIDDLE: Chat -->
  <div class="ib-chat" id="chatPanel">
    <div class="chat-empty-state" id="emptyState">
      <i class="fa-brands fa-facebook-messenger"></i>
      <p>Select a conversation to start chatting</p>
    </div>

    <div id="chatHeader" class="chat-header" style="display:none">
      <div class="chat-header-avatar" id="chatHeaderAvatar"></div>
      <div class="chat-header-info">
        <div class="chat-header-name" id="chatHeaderName"></div>
        <div class="chat-header-sub" id="chatHeaderSub">Messenger</div>
      </div>
    </div>

    <div id="messagesList" class="chat-messages" style="display:none"></div>

    <div id="replyBox" class="chat-reply" style="display:none">
      <div class="reply-inner">
        <textarea id="replyText" placeholder="Type a message…" rows="1"></textarea>
        <button id="sendBtn" title="Send"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
      <div class="reply-hint">Press Enter to send · Shift+Enter for new line</div>
    </div>
  </div>

  <!-- RIGHT: Profile -->
  <div class="ib-right" id="profilePanel">
    <div class="profile-empty" id="profileEmpty">
      <i class="fa-regular fa-user" style="font-size:32px;opacity:.3;display:block;margin-bottom:12px"></i>
      Select a conversation
    </div>
    <div id="profileContent" style="display:none">
      <div class="profile-top">
        <div class="profile-avatar" id="profileAvatar"></div>
        <div class="profile-name" id="profileName"></div>
        <div class="profile-sub">Facebook Messenger</div>
      </div>
      <div class="profile-section">
        <h4>Conversation Info</h4>
        <div class="profile-stat"><span>First contact</span><span id="profileFirst">—</span></div>
        <div class="profile-stat"><span>Last message</span><span id="profileLast">—</span></div>
        <div class="profile-stat"><span>Total messages</span><span id="profileTotal">—</span></div>
      </div>
    </div>
  </div>

</div>

<!-- Toast -->
<div class="ib-toast" id="ibToast"></div>

<script>
// ── State ─────────────────────────────────────────────
var PAGE  = null; // {id, name, token}
var convs = [];
var activeConvId   = null;
var lastMsgTime    = null;
var pollTimer      = null;
var sending        = false;

// ── Init ──────────────────────────────────────────────
(function init() {
  var pages = JSON.parse(localStorage.getItem('fb_pages') || '[]');
  if (!pages.length) {
    showToast('No Facebook Page connected. Please connect from the dashboard.', 'error', 5000);
    setTimeout(function(){ window.location = '/'; }, 3000);
    return;
  }
  // Use first page (or the one marked selected)
  PAGE = pages.find(function(p){ return p.selected; }) || pages[0];
  if (!PAGE) { window.location = '/'; return; }

  loadConversations();

  // Search
  var si = document.getElementById('searchInput');
  var st; si.addEventListener('input', function(){
    clearTimeout(st);
    st = setTimeout(function(){ loadConversations(si.value.trim()); }, 300);
  });

  // Auto-sync every 15s
  setInterval(function(){
    if (PAGE) syncQuiet();
  }, 15000);
})();

// ── API calls ─────────────────────────────────────────
function api(action, params, method, body) {
  var url = 'inbox_api.php?action=' + action;
  if (params) url += '&' + new URLSearchParams(params).toString();
  var opts = { credentials: 'same-origin', headers: { 'Content-Type': 'application/json' } };
  if (method === 'POST') { opts.method = 'POST'; opts.body = JSON.stringify(body || {}); }
  return fetch(url, opts).then(function(r){ return r.json(); });
}

// ── Load conversations from DB ─────────────────────────
function loadConversations(search) {
  api('conversations', { page_id: PAGE.id, search: search || '' }).then(function(data) {
    convs = data.conversations || [];
    renderConvList();
  }).catch(function(){ showToast('Failed to load conversations', 'error'); });
}

function renderConvList() {
  var list = document.getElementById('convList');
  if (!convs.length) {
    list.innerHTML = '<div class="conv-empty">No conversations yet.<br>Click <strong>Sync</strong> to fetch from Facebook.</div>';
    return;
  }
  list.innerHTML = convs.map(function(c) {
    var initials = (c.customer_name || '?').split(' ').map(function(w){return w[0];}).join('').slice(0,2).toUpperCase();
    var preview  = escHtml(c.last_message || '');
    if (c.last_direction === 'out') preview = '↗ ' + preview;
    var badge    = c.unread_count > 0 ? '<span class="conv-badge">'+c.unread_count+'</span>' : '';
    var active   = c.id == activeConvId ? ' active' : '';
    var unreadCls= c.unread_count > 0 ? ' unread' : '';
    var avatarHtml = c.customer_avatar
      ? '<img src="'+escHtml(c.customer_avatar)+'" onerror="this.style.display=\'none\'">'
      : initials;
    return '<div class="conv-item'+active+'" data-id="'+c.id+'" onclick="openConv('+c.id+')">'+
      '<div class="conv-avatar">'+avatarHtml+'</div>'+
      '<div class="conv-info">'+
        '<div class="conv-name">'+escHtml(c.customer_name || 'Unknown')+'</div>'+
        '<div class="conv-preview'+unreadCls+'">'+preview+'</div>'+
      '</div>'+
      '<div class="conv-meta">'+
        '<span class="conv-time">'+relTime(c.last_message_at)+'</span>'+
        badge+
      '</div>'+
    '</div>';
  }).join('');
}

// ── Open a conversation ────────────────────────────────
function openConv(id) {
  activeConvId = id;
  lastMsgTime  = null;
  clearInterval(pollTimer);

  var conv = convs.find(function(c){ return c.id == id; });
  if (!conv) return;

  // Update active state in list
  document.querySelectorAll('.conv-item').forEach(function(el){
    el.classList.toggle('active', el.dataset.id == id);
  });
  conv.unread_count = 0;
  renderConvList();

  // Mark read
  api('mark_read', { conv_id: id });

  // Show chat panels
  document.getElementById('emptyState').style.display  = 'none';
  document.getElementById('chatHeader').style.display  = 'flex';
  document.getElementById('messagesList').style.display= 'flex';
  document.getElementById('replyBox').style.display    = 'block';
  document.getElementById('profileEmpty').style.display  = 'none';
  document.getElementById('profileContent').style.display= 'block';

  // Header
  var initials = (conv.customer_name||'?').split(' ').map(function(w){return w[0];}).join('').slice(0,2).toUpperCase();
  var avatarHtml = conv.customer_avatar
    ? '<img src="'+escHtml(conv.customer_avatar)+'" onerror="this.style.display=\'none\'">'
    : initials;
  document.getElementById('chatHeaderAvatar').innerHTML = avatarHtml;
  document.getElementById('chatHeaderName').textContent = conv.customer_name || 'Unknown';

  // Profile panel
  document.getElementById('profileAvatar').innerHTML = avatarHtml;
  document.getElementById('profileName').textContent = conv.customer_name || 'Unknown';
  document.getElementById('profileFirst').textContent= fmtDate(conv.created_at);
  document.getElementById('profileLast').textContent = fmtDate(conv.last_message_at);

  // Load messages
  loadMessages(id, false);

  // Poll for new messages every 10s
  pollTimer = setInterval(function(){ pollNewMessages(); }, 10000);
}

function loadMessages(convId, append) {
  var params = { conv_id: convId };
  if (append && lastMsgTime) params.since = lastMsgTime;

  api('messages', params).then(function(data) {
    var msgs = data.messages || [];
    if (!msgs.length && append) return;

    if (!append) {
      renderMessages(msgs);
    } else {
      appendMessages(msgs);
    }

    if (msgs.length) {
      lastMsgTime = msgs[msgs.length - 1].sent_at;
      document.getElementById('profileTotal').textContent = msgs.length + '+';
    }
  });
}

function renderMessages(msgs) {
  var list = document.getElementById('messagesList');
  list.innerHTML = '';
  var lastDate = '';
  msgs.forEach(function(m) {
    var d = (m.sent_at || '').slice(0,10);
    if (d !== lastDate) {
      lastDate = d;
      var div = document.createElement('div');
      div.className = 'msg-date-divider';
      div.innerHTML = '<span>'+fmtDate(m.sent_at)+'</span>';
      list.appendChild(div);
    }
    list.appendChild(buildBubble(m));
  });
  list.scrollTop = list.scrollHeight;
}

function appendMessages(msgs) {
  var list = document.getElementById('messagesList');
  var atBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 60;
  msgs.forEach(function(m){ list.appendChild(buildBubble(m)); });
  if (atBottom) list.scrollTop = list.scrollHeight;
}

function buildBubble(m) {
  var conv     = convs.find(function(c){ return c.id == activeConvId; });
  var initials = conv ? (conv.customer_name||'?').split(' ').map(function(w){return w[0];}).join('').slice(0,2).toUpperCase() : '?';
  var avatarHtml = (conv && conv.customer_avatar)
    ? '<img src="'+escHtml(conv.customer_avatar)+'" onerror="this.style.display=\'none\'">'
    : initials;

  var row  = document.createElement('div');
  row.className = 'msg-row ' + m.direction;

  var avatarEl = document.createElement('div');
  avatarEl.className = 'msg-bubble-avatar';
  avatarEl.innerHTML = m.direction === 'in' ? avatarHtml : '<i class="fa-brands fa-facebook" style="font-size:13px"></i>';

  var bubble = document.createElement('div');
  bubble.className = 'msg-bubble';
  if (m.attachment_url) {
    bubble.innerHTML = escHtml(m.message_text||'') + (m.message_text ? '<br>' : '') +
      '<span class="msg-attachment"><a href="'+escHtml(m.attachment_url)+'" target="_blank" rel="noopener">📎 View attachment</a></span>';
  } else {
    bubble.textContent = m.message_text || '';
  }

  var time = document.createElement('div');
  time.className = 'msg-time';
  time.textContent = fmtTime(m.sent_at);

  var wrap = document.createElement('div');
  wrap.style.display = 'flex';
  wrap.style.flexDirection = 'column';
  wrap.appendChild(bubble);
  wrap.appendChild(time);

  if (m.direction === 'in') {
    row.appendChild(avatarEl);
    row.appendChild(wrap);
  } else {
    row.appendChild(wrap);
    row.appendChild(avatarEl);
  }
  return row;
}

function pollNewMessages() {
  if (!activeConvId) return;
  loadMessages(activeConvId, true);
}

// ── Sync from Facebook ─────────────────────────────────
function doSync() {
  var btn = document.getElementById('syncBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-circle-notch spin"></i> Syncing…';
  document.getElementById('syncStatus').textContent = '';

  api('sync', null, 'POST', { page_id: PAGE.id, page_token: PAGE.access_token })
    .then(function(d) {
      if (d.error) { showToast(d.error, 'error'); return; }
      document.getElementById('syncStatus').textContent = d.synced + ' synced';
      loadConversations(document.getElementById('searchInput').value.trim());
      showToast('Synced ' + d.synced + ' conversations', 'success');
    })
    .catch(function(){ showToast('Sync failed', 'error'); })
    .finally(function(){
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Sync';
    });
}

function syncQuiet() {
  api('sync', null, 'POST', { page_id: PAGE.id, page_token: PAGE.access_token })
    .then(function(d) {
      if (d.synced > 0) {
        loadConversations(document.getElementById('searchInput').value.trim());
        if (activeConvId) pollNewMessages();
      }
    }).catch(function(){});
}

// ── Send message ───────────────────────────────────────
var replyText = document.getElementById('replyText');
var sendBtn   = document.getElementById('sendBtn');

replyText.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
replyText.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
sendBtn.addEventListener('click', sendMessage);

function sendMessage() {
  if (sending || !activeConvId) return;
  var text = replyText.value.trim();
  if (!text) return;

  sending = true;
  sendBtn.disabled = true;
  replyText.disabled = true;

  api('send', null, 'POST', {
    conv_id: activeConvId, message: text,
    page_id: PAGE.id, page_token: PAGE.access_token
  }).then(function(d) {
    if (d.error) { showToast(d.error, 'error'); return; }
    replyText.value = '';
    replyText.style.height = 'auto';
    // Optimistic append
    appendMessages([{
      direction: 'out', message_text: text,
      sent_at: d.sent_at || new Date().toISOString(),
      fb_message_id: d.message_id
    }]);
    // Refresh conv list preview
    var conv = convs.find(function(c){ return c.id == activeConvId; });
    if (conv) { conv.last_message = text; conv.last_direction = 'out'; renderConvList(); }
  }).catch(function(){ showToast('Failed to send', 'error'); })
  .finally(function(){
    sending = false;
    sendBtn.disabled = false;
    replyText.disabled = false;
    replyText.focus();
  });
}

// ── Helpers ────────────────────────────────────────────
function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function relTime(iso) {
  if (!iso) return '';
  var d = new Date(iso.replace(' ','T') + (iso.includes('Z')?'':'Z'));
  var diff = (Date.now() - d) / 1000;
  if (diff < 60) return 'now';
  if (diff < 3600) return Math.floor(diff/60) + 'm';
  if (diff < 86400) return Math.floor(diff/3600) + 'h';
  if (diff < 604800) return Math.floor(diff/86400) + 'd';
  return d.toLocaleDateString('en', {month:'short',day:'numeric'});
}

function fmtDate(iso) {
  if (!iso) return '—';
  var d = new Date(iso.replace(' ','T') + (iso.includes('Z')?'':'Z'));
  return d.toLocaleDateString('en', {month:'short',day:'numeric',year:'numeric'});
}

function fmtTime(iso) {
  if (!iso) return '';
  var d = new Date(iso.replace(' ','T') + (iso.includes('Z')?'':'Z'));
  return d.toLocaleTimeString('en', {hour:'2-digit',minute:'2-digit',hour12:true});
}

function showToast(msg, type, dur) {
  var t = document.getElementById('ibToast');
  t.textContent = msg;
  t.className = 'ib-toast show ' + (type||'');
  clearTimeout(t._timer);
  t._timer = setTimeout(function(){ t.className = 'ib-toast'; }, dur||3000);
}
</script>
</body>
</html>
