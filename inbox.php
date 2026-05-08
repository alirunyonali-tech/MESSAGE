<?php
define('FBCAST_PAGE_CONTEXT', true);
require_once __DIR__ . '/config/load-env.php';
if (empty($_SESSION['fb_user_id'])) { header('Location: /'); exit; }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inbox — FBCast Pro</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0c10;--surface:#12151c;--surface2:#1a1d26;--surface3:#232736;
  --border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.13);
  --primary:#0866FF;--primary-d:#0550c8;
  --text:#e4e6eb;--text2:#94a3b8;--text3:#55637a;
  --green:#22c55e;--red:#ef4444;
  --bubble-in:#1e2535;--bubble-out:#0866FF;
  --r:12px;
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);height:100vh;overflow:hidden;display:flex;flex-direction:column}

/* ── TOPBAR ── */
.ib-top{display:flex;align-items:center;gap:10px;padding:0 16px;height:52px;background:var(--surface);border-bottom:1px solid var(--border);flex-shrink:0;z-index:10}
.ib-brand{display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);flex-shrink:0}
.ib-brand img{width:26px;height:26px;border-radius:7px;object-fit:cover}
.ib-brand b{font-size:14px;font-weight:700}
.ib-sep{color:var(--text3);font-size:16px;margin:0 2px}

/* Page selector */
.page-sel{
  display:flex;align-items:center;gap:7px;
  background:var(--surface2);border:1px solid var(--border2);
  border-radius:8px;padding:5px 10px 5px 8px;cursor:pointer;
  transition:border-color .15s;position:relative;
}
.page-sel:hover{border-color:var(--primary)}
.page-sel-avatar{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;overflow:hidden;flex-shrink:0}
.page-sel-avatar img{width:100%;height:100%;object-fit:cover}
.page-sel-name{font-size:12.5px;font-weight:600;max-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.page-sel-arrow{font-size:10px;color:var(--text3);margin-left:2px;transition:transform .2s}
.page-sel.open .page-sel-arrow{transform:rotate(180deg)}

/* Page dropdown */
.page-dropdown{
  position:absolute;top:calc(100% + 6px);left:0;min-width:200px;
  background:var(--surface2);border:1px solid var(--border2);border-radius:10px;
  box-shadow:0 12px 40px rgba(0,0,0,.5);z-index:100;overflow:hidden;
  opacity:0;transform:translateY(-6px) scale(.97);pointer-events:none;
  transition:all .15s cubic-bezier(.16,1,.3,1);
}
.page-dropdown.open{opacity:1;transform:none;pointer-events:all}
.page-opt{display:flex;align-items:center;gap:9px;padding:10px 14px;cursor:pointer;transition:background .1s;font-size:13px}
.page-opt:hover{background:var(--surface3)}
.page-opt.active{color:var(--primary);font-weight:600}
.page-opt-av{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;overflow:hidden;flex-shrink:0}
.page-opt-av img{width:100%;height:100%;object-fit:cover}

.ib-top-right{margin-left:auto;display:flex;align-items:center;gap:8px}
.ib-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;border:1px solid var(--border2);background:var(--surface2);color:var(--text);font-size:12.5px;font-weight:500;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap}
.ib-btn:hover{background:var(--primary);border-color:var(--primary);color:#fff}
.ib-btn i{font-size:13px}
#syncStatus{font-size:11.5px;color:var(--text3);transition:color .3s}

/* ── BODY ── */
.ib-body{display:flex;flex:1;overflow:hidden}

/* ── LEFT — Conversations ── */
.ib-left{width:290px;flex-shrink:0;display:flex;flex-direction:column;border-right:1px solid var(--border);background:var(--surface)}
.ib-left-head{padding:12px 14px 10px;border-bottom:1px solid var(--border)}
.ib-left-head h3{font-size:14px;font-weight:700;margin-bottom:9px}
.ib-search{display:flex;align-items:center;gap:7px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:7px 11px;transition:border-color .15s}
.ib-search:focus-within{border-color:var(--primary)}
.ib-search i{color:var(--text3);font-size:12px;flex-shrink:0}
.ib-search input{background:none;border:none;outline:none;color:var(--text);font-size:13px;width:100%;font-family:inherit}
.ib-search input::placeholder{color:var(--text3)}

.conv-list{flex:1;overflow-y:auto;overscroll-behavior:contain}
.conv-list::-webkit-scrollbar{width:3px}
.conv-list::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

/* Skeleton */
.skel{animation:skel-pulse 1.4s ease-in-out infinite}
@keyframes skel-pulse{0%,100%{opacity:.4}50%{opacity:.9}}
.skel-item{display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid var(--border)}
.skel-av{width:40px;height:40px;border-radius:50%;background:var(--surface2);flex-shrink:0}
.skel-lines{flex:1}
.skel-l1{height:11px;background:var(--surface2);border-radius:4px;width:55%;margin-bottom:6px}
.skel-l2{height:10px;background:var(--surface2);border-radius:4px;width:80%}

/* Conversation item */
.conv-item{display:flex;align-items:center;gap:10px;padding:11px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .08s;position:relative}
.conv-item:hover{background:var(--surface2)}
.conv-item.active{background:rgba(8,102,255,.1);border-right:2px solid var(--primary)}
.conv-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden}
.conv-av img{width:100%;height:100%;object-fit:cover}
.conv-info{flex:1;min-width:0}
.conv-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px}
.conv-prev{font-size:11.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.conv-prev.unread{color:var(--text2);font-weight:500}
.conv-meta{display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0}
.conv-time{font-size:10.5px;color:var(--text3)}
.conv-badge{background:var(--primary);color:#fff;font-size:9.5px;font-weight:700;min-width:17px;height:17px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px}
.no-convs{padding:32px 16px;text-align:center;color:var(--text3);font-size:13px;line-height:1.6}
/* Messaging window dot */
.win-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-left:auto}
.win-dot.open{background:var(--green)}
.win-dot.closed{background:#f59e0b}
/* Load more */
.load-more-btn{display:block;width:calc(100% - 28px);margin:10px 14px;padding:8px;background:var(--surface2);border:1px solid var(--border2);border-radius:8px;color:var(--text2);font-size:12px;font-family:inherit;cursor:pointer;transition:background .15s}
.load-more-btn:hover{background:var(--surface3)}
/* Unread badge in topbar */
.ib-unread-badge{background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 4px;margin-left:4px}
/* 24h window warning */
.win-warning{padding:8px 18px;background:rgba(245,158,11,.08);border-bottom:1px solid rgba(245,158,11,.2);font-size:11.5px;color:#fbbf24;display:flex;align-items:center;gap:7px;flex-shrink:0}
.win-warning i{font-size:12px}

/* ── MIDDLE — Chat ── */
.ib-chat{flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--bg);position:relative}
.chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:var(--text3)}
.chat-empty i{font-size:52px;opacity:.2}
.chat-empty p{font-size:13.5px}

.chat-head{display:flex;align-items:center;gap:10px;padding:12px 18px;border-bottom:1px solid var(--border);background:var(--surface);flex-shrink:0}
.chat-head-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;overflow:hidden;flex-shrink:0}
.chat-head-av img{width:100%;height:100%;object-fit:cover}
.chat-head-name{font-size:14px;font-weight:700}
.chat-head-sub{font-size:11px;color:var(--text3)}

.chat-msgs{flex:1;overflow-y:auto;padding:16px 18px 8px;display:flex;flex-direction:column;gap:4px;overscroll-behavior:contain}
.chat-msgs::-webkit-scrollbar{width:3px}
.chat-msgs::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

/* Msg skeleton */
.msg-skel{display:flex;gap:8px;max-width:60%;margin-bottom:4px}
.msg-skel.out{align-self:flex-end;flex-direction:row-reverse}
.msg-skel-av{width:26px;height:26px;border-radius:50%;background:var(--surface2);flex-shrink:0}
.msg-skel-bubble{height:36px;border-radius:14px;background:var(--surface2);flex:1}
.msg-skel.out .msg-skel-bubble{background:rgba(8,102,255,.2)}

.msg-date{text-align:center;margin:10px 0 6px}
.msg-date span{font-size:11px;color:var(--text3);background:var(--surface2);padding:3px 10px;border-radius:20px}

.msg-row{display:flex;align-items:flex-end;gap:7px;max-width:76%;animation:msg-in .18s ease}
@keyframes msg-in{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.msg-row.in{align-self:flex-start}
.msg-row.out{align-self:flex-end;flex-direction:row-reverse}
.msg-av{width:26px;height:26px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;overflow:hidden}
.msg-av img{width:100%;height:100%;object-fit:cover}
.msg-col{display:flex;flex-direction:column;gap:2px;min-width:0}
.msg-row.out .msg-col{align-items:flex-end}
.msg-bubble{padding:9px 13px;border-radius:17px;font-size:13.5px;line-height:1.5;word-break:break-word;max-width:100%}
.msg-row.in  .msg-bubble{background:var(--bubble-in);border-bottom-left-radius:4px}
.msg-row.out .msg-bubble{background:var(--bubble-out);color:#fff;border-bottom-right-radius:4px}
.msg-time{font-size:10px;color:var(--text3);padding:0 3px}
.msg-attach a{color:inherit;text-decoration:underline;font-size:13px;opacity:.85}

/* Reply */
.chat-reply{padding:12px 18px;border-top:1px solid var(--border);background:var(--surface);flex-shrink:0}
.reply-wrap{display:flex;align-items:flex-end;gap:9px;background:var(--surface2);border:1px solid var(--border2);border-radius:12px;padding:9px 12px;transition:border-color .15s}
.reply-wrap:focus-within{border-color:var(--primary)}
#replyText{flex:1;background:none;border:none;outline:none;color:var(--text);font-size:13.5px;font-family:inherit;resize:none;min-height:20px;max-height:100px;line-height:1.5}
#replyText::placeholder{color:var(--text3)}
#sendBtn{width:34px;height:34px;border-radius:50%;border:none;background:var(--primary);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;transition:background .15s,transform .1s}
#sendBtn:hover{background:var(--primary-d)}
#sendBtn:active{transform:scale(.93)}
#sendBtn:disabled{background:var(--surface3);opacity:.5;cursor:not-allowed}
.reply-hint{font-size:10.5px;color:var(--text3);margin-top:5px;padding:0 2px}

/* ── RIGHT — Profile ── */
.ib-right{width:240px;flex-shrink:0;border-left:1px solid var(--border);background:var(--surface);display:flex;flex-direction:column;overflow-y:auto}
.ib-right::-webkit-scrollbar{width:3px}
.ib-right::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
.prof-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--text3);font-size:12.5px}
.prof-empty i{font-size:30px;opacity:.2}
.prof-top{padding:24px 16px 18px;text-align:center;border-bottom:1px solid var(--border)}
.prof-av{width:58px;height:58px;border-radius:50%;margin:0 auto 10px;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;overflow:hidden}
.prof-av img{width:100%;height:100%;object-fit:cover}
.prof-name{font-size:14px;font-weight:700;margin-bottom:3px}
.prof-sub{font-size:11px;color:var(--text3)}
.prof-sec{padding:14px 16px;border-bottom:1px solid var(--border)}
.prof-sec h4{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.prof-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;gap:8px}
.prof-row span:first-child{font-size:11.5px;color:var(--text2);flex-shrink:0}
.prof-row span:last-child{font-size:11.5px;font-weight:600;color:var(--text);text-align:right}

/* Toast */
.ib-toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:var(--surface2);border:1px solid var(--border2);color:var(--text);padding:9px 18px;border-radius:10px;font-size:12.5px;z-index:9999;opacity:0;transition:opacity .25s;pointer-events:none;white-space:nowrap}
.ib-toast.show{opacity:1}
.ib-toast.err{border-color:var(--red);color:#fca5a5}
.ib-toast.ok{border-color:var(--green);color:#86efac}

@media(max-width:860px){.ib-right{display:none}}
@media(max-width:580px){.ib-left{width:220px}}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="ib-top">
  <a href="/" class="ib-brand">
    <img src="images/castpro2.png" alt="FBCast Pro">
    <b>FBCast Pro</b>
  </a>
  <span class="ib-sep">/</span>

  <!-- Page Selector -->
  <div class="page-sel" id="pageSel" onclick="togglePageDrop()">
    <div class="page-sel-avatar" id="pageSelAv">?</div>
    <span class="page-sel-name" id="pageSelName">Select Page</span>
    <i class="fa-solid fa-chevron-down page-sel-arrow" id="pageSelArrow"></i>
    <div class="page-dropdown" id="pageDropdown"></div>
  </div>

  <div class="ib-top-right">
    <span id="syncStatus"></span>
    <span id="totalUnreadBadge" class="ib-unread-badge" style="display:none"></span>
    <button class="ib-btn" id="syncBtn" onclick="manualSync()"><i class="fa-solid fa-rotate"></i> Sync</button>
    <a href="/" class="ib-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>
</div>

<!-- BODY -->
<div class="ib-body">

  <!-- LEFT -->
  <div class="ib-left">
    <div class="ib-left-head">
      <h3>Messages</h3>
      <div class="ib-search"><i class="fa-solid fa-magnifying-glass"></i><input id="searchInput" placeholder="Search conversations…" autocomplete="off"></div>
    </div>
    <div class="conv-list" id="convList"></div>
  </div>

  <!-- CHAT -->
  <div class="ib-chat">
    <div class="chat-empty" id="chatEmpty">
      <i class="fa-brands fa-facebook-messenger"></i>
      <p>Select a conversation</p>
    </div>
    <div id="chatHead" class="chat-head" style="display:none">
      <div class="chat-head-av" id="chatHeadAv"></div>
      <div><div class="chat-head-name" id="chatHeadName"></div><div class="chat-head-sub">Messenger</div></div>
    </div>
    <div id="winWarning" class="win-warning" style="display:none">
      <i class="fa-solid fa-clock"></i>
      <span>Outside 24-hour messaging window. Only message tags can be used to reply.</span>
    </div>
    <div id="chatMsgs" class="chat-msgs" style="display:none"></div>
    <div id="chatReply" class="chat-reply" style="display:none">
      <div class="reply-wrap">
        <textarea id="replyText" placeholder="Type a message…" rows="1"></textarea>
        <button id="sendBtn"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
      <div class="reply-hint">Enter to send · Shift+Enter new line</div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="ib-right">
    <div class="prof-empty" id="profEmpty"><i class="fa-regular fa-user"></i><span>Select a conversation</span></div>
    <div id="profContent" style="display:none">
      <div class="prof-top">
        <div class="prof-av" id="profAv"></div>
        <div class="prof-name" id="profName"></div>
        <div class="prof-sub">Facebook Messenger</div>
      </div>
      <div class="prof-sec">
        <h4>Details</h4>
        <div class="prof-row"><span>First contact</span><span id="profFirst">—</span></div>
        <div class="prof-row"><span>Last message</span><span id="profLast">—</span></div>
        <div class="prof-row"><span>Messages</span><span id="profTotal">—</span></div>
        <div class="prof-row"><span>Direction</span><span id="profDir">—</span></div>
      </div>
    </div>
  </div>
</div>

<div class="ib-toast" id="toast"></div>

<script>
// ── State ──────────────────────────────────────────────
var PAGE      = null;
var PAGES     = [];
var convs     = [];
var activeId  = null;
var msgCache  = {};    // {convId: [msgs]} — instant switching
var lastSeen  = {};    // {convId: iso} — poll only new msgs
var pollTmr   = null;
var sending   = false;
var syncing   = false;
var hasMore   = false;
var oldestId  = null;

// ── Boot ───────────────────────────────────────────────
(function boot() {
  PAGES = JSON.parse(localStorage.getItem('fb_pages') || '[]');
  if (!PAGES.length) {
    toast('No Facebook Page connected. Redirecting…', 'err', 4000);
    setTimeout(function(){ window.location='/'; }, 3000);
    return;
  }

  var savedId = localStorage.getItem('inbox_page_id');
  PAGE = PAGES.find(function(p){ return p.id === savedId; }) || PAGES[0];

  buildPageDropdown();
  updatePageSelUI();

  // Load conversations from DB immediately (instant if already synced)
  loadConvs();

  // Auto-sync from Facebook in background right away — no manual click needed
  setTimeout(autoSync, 1200);

  // Keep refreshing every 30s
  setInterval(autoSync, 30000);

  // Search with debounce
  var st;
  document.getElementById('searchInput').addEventListener('input', function(){
    clearTimeout(st);
    var v = this.value.trim();
    st = setTimeout(function(){ loadConvs(v); }, 250);
  });
})();

// ── Page selector ──────────────────────────────────────
function buildPageDropdown() {
  var dd = document.getElementById('pageDropdown');
  dd.innerHTML = PAGES.map(function(p) {
    var init = (p.name||'P')[0].toUpperCase();
    var av   = p.picture ? '<img src="'+esc(p.picture)+'" onerror="this.style.display=\'none\'">' : init;
    var act  = PAGE && p.id === PAGE.id ? ' active' : '';
    return '<div class="page-opt'+act+'" onclick="selectPage(\''+p.id+'\',event)">'+
      '<div class="page-opt-av">'+av+'</div>'+
      '<span>'+esc(p.name||p.id)+'</span>'+
    '</div>';
  }).join('');
}

function updatePageSelUI() {
  if (!PAGE) return;
  var init = (PAGE.name||'P')[0].toUpperCase();
  var avEl = document.getElementById('pageSelAv');
  avEl.innerHTML = PAGE.picture ? '<img src="'+esc(PAGE.picture)+'" onerror="this.style.display=\'none\'">' : init;
  document.getElementById('pageSelName').textContent = PAGE.name || PAGE.id;
}

function togglePageDrop() {
  var sel = document.getElementById('pageSel');
  var dd  = document.getElementById('pageDropdown');
  var open = dd.classList.toggle('open');
  sel.classList.toggle('open', open);
  if (open) {
    setTimeout(function(){
      document.addEventListener('click', closeDrop, { once: true });
    }, 10);
  }
}
function closeDrop(){
  document.getElementById('pageDropdown').classList.remove('open');
  document.getElementById('pageSel').classList.remove('open');
}

function selectPage(id, e) {
  e && e.stopPropagation();
  PAGE = PAGES.find(function(p){ return p.id === id; });
  if (!PAGE) return;
  localStorage.setItem('inbox_page_id', id);
  closeDrop();
  buildPageDropdown();
  updatePageSelUI();
  activeId = null;
  msgCache = {};
  lastSeen = {};
  hasMore  = false;
  oldestId = null;
  clearInterval(pollTmr);
  hideChatPanel();
  loadConvs();
  setTimeout(autoSync, 400);
}

// ── Conversations ──────────────────────────────────────
function loadConvs(search, append) {
  if (!PAGE) return;
  var params = 'page_id=' + encodeURIComponent(PAGE.id);
  if (search)  params += '&search='    + encodeURIComponent(search);
  if (append && oldestId) params += '&before_id=' + oldestId;

  if (!append && !convs.length) showConvSkel();

  fetch('inbox_api.php?action=conversations&' + params, { credentials: 'same-origin' })
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (append) {
        convs = convs.concat(d.conversations || []);
      } else {
        convs = d.conversations || [];
      }
      hasMore  = !!d.has_more;
      oldestId = d.oldest_id || null;
      // Update total unread badge
      var tu = d.total_unread || 0;
      var badge = document.getElementById('totalUnreadBadge');
      badge.textContent = tu > 99 ? '99+' : tu;
      badge.style.display = tu > 0 ? 'inline-flex' : 'none';
      // Update page title
      document.title = tu > 0 ? '(' + tu + ') Inbox — FBCast Pro' : 'Inbox — FBCast Pro';

      renderConvList();
      if (!search) preloadAllMsgs();
    }).catch(function(){ toast('Failed to load conversations', 'err'); });
}

function loadMore() {
  var search = document.getElementById('searchInput').value.trim();
  loadConvs(search || undefined, true);
}

function showConvSkel() {
  var h = '';
  for (var i=0;i<6;i++) h += '<div class="skel-item skel"><div class="skel-av"></div><div class="skel-lines"><div class="skel-l1"></div><div class="skel-l2"></div></div></div>';
  document.getElementById('convList').innerHTML = h;
}

function renderConvList() {
  var el = document.getElementById('convList');
  if (!convs.length) {
    el.innerHTML = '<div class="no-convs">No conversations yet.<br>Auto-syncing from Facebook…</div>';
    return;
  }
  var html = convs.map(function(c) {
    var init = initials(c.customer_name);
    // support both old format (customer_avatar) and new format (customer_profile_pic)
    var picUrl = c.customer_profile_pic || c.customer_avatar || null;
    var av     = picUrl ? '<img src="'+esc(picUrl)+'" onerror="this.style.display=\'none\'">' : init;
    // last message preview — support both old and new format
    var lmText = (c.last_message && c.last_message.text) ? c.last_message.text : (c.last_message||'');
    var lmDir  = (c.last_message && c.last_message.direction) ? c.last_message.direction : c.last_direction;
    var prev   = esc(lmText);
    if (lmDir === 'out' || lmDir === 'outgoing') prev = '↗ ' + prev;
    var badge  = c.unread_count > 0 ? '<span class="conv-badge">'+c.unread_count+'</span>' : '';
    var act    = c.id == activeId ? ' active' : '';
    var unCls  = c.unread_count > 0 ? ' unread' : '';
    // Messaging window dot
    var winCls = c.within_messaging_window ? 'open' : 'closed';
    var winDot = '<div class="win-dot '+winCls+'" title="'+(c.within_messaging_window?'Within 24h window':'Outside 24h window')+'"></div>';
    return '<div class="conv-item'+act+'" data-id="'+c.id+'" onclick="openConv('+c.id+')">'+
      '<div class="conv-av">'+av+'</div>'+
      '<div class="conv-info"><div class="conv-name">'+esc(c.customer_name||'Unknown')+'</div>'+
      '<div class="conv-prev'+unCls+'">'+prev+'</div></div>'+
      '<div class="conv-meta"><span class="conv-time">'+relTime(c.last_message_at)+'</span>'+badge+winDot+'</div>'+
    '</div>';
  }).join('');

  if (hasMore) {
    html += '<button class="load-more-btn" onclick="loadMore()">Load more conversations…</button>';
  }
  el.innerHTML = html;
}

// Pre-load messages for ALL visible conversations — so every click is instant
function preloadAllMsgs() {
  convs.forEach(function(c) {
    if (msgCache[c.id]) return; // already cached
    fetch('inbox_api.php?action=messages&conv_id=' + c.id, { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d) {
        var msgs = d.messages || [];
        if (msgs.length) {
          msgCache[c.id] = msgs;
          lastSeen[c.id] = msgs[msgs.length-1].sent_at;
          // If this is the active conv and we were showing skeleton, render now
          if (activeId === c.id) renderMsgs(msgs);
        }
      }).catch(function(){});
  });
}

// ── Open conversation — INSTANT from cache ─────────────
function openConv(id) {
  if (activeId === id) return;
  clearInterval(pollTmr);
  activeId = id;

  document.querySelectorAll('.conv-item').forEach(function(el){
    el.classList.toggle('active', el.dataset.id == id);
  });

  var conv = convs.find(function(c){ return c.id==id; });
  if (conv && conv.unread_count > 0) {
    conv.unread_count = 0;
    renderConvList();
    fetch('inbox_api.php?action=mark_read&conv_id='+id, { credentials:'same-origin' });
  }

  showChatPanel(conv);

  if (msgCache[id] && msgCache[id].length) {
    // INSTANT — render from cache, then silently fetch only new ones
    renderMsgs(msgCache[id]);
    fetchMsgs(id, lastSeen[id]||null, true);
  } else {
    // Not cached yet — show skeleton briefly while preload finishes
    showMsgSkel();
    fetchMsgs(id, null, false);
  }

  // Poll new messages for active conv every 8s
  pollTmr = setInterval(function(){
    if (activeId === id) fetchMsgs(id, lastSeen[id]||null, true);
  }, 8000);
}

function fetchMsgs(convId, since, append) {
  var url = 'inbox_api.php?action=messages&conv_id=' + convId;
  if (since) url += '&since=' + encodeURIComponent(since);

  fetch(url, { credentials:'same-origin' })
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (activeId !== convId) return;
      var msgs = d.messages || [];
      if (!msgs.length && append) return;

      if (append && msgCache[convId] && msgCache[convId].length) {
        var existing = new Set(msgCache[convId].map(function(m){ return m.fb_message_id; }));
        var newMsgs  = msgs.filter(function(m){ return !existing.has(m.fb_message_id); });
        if (!newMsgs.length) return;
        msgCache[convId] = msgCache[convId].concat(newMsgs);
        appendMsgs(newMsgs);
      } else {
        msgCache[convId] = msgs;
        renderMsgs(msgs);
      }

      if (msgs.length) lastSeen[convId] = msgs[msgs.length-1].sent_at;
      document.getElementById('profTotal').textContent = (msgCache[convId]||[]).length + '+';
    }).catch(function(){});
}

// ── Show/hide panels ────────────────────────────────────
function showChatPanel(conv) {
  document.getElementById('chatEmpty').style.display  = 'none';
  document.getElementById('chatHead').style.display   = 'flex';
  document.getElementById('chatMsgs').style.display   = 'flex';
  document.getElementById('chatReply').style.display  = 'block';
  document.getElementById('profEmpty').style.display  = 'none';
  document.getElementById('profContent').style.display= 'block';

  if (!conv) return;
  var picUrl = conv.customer_profile_pic || conv.customer_avatar || null;
  document.getElementById('chatHeadAv').innerHTML     = avatarHtml(picUrl, conv.customer_name);
  document.getElementById('chatHeadName').textContent = conv.customer_name || 'Unknown';
  document.getElementById('profAv').innerHTML         = avatarHtml(picUrl, conv.customer_name);
  document.getElementById('profName').textContent     = conv.customer_name || 'Unknown';
  document.getElementById('profFirst').textContent    = fmtDate(conv.created_at);
  document.getElementById('profLast').textContent     = fmtDate(conv.last_message_at);
  // Direction from new or old format
  var lastDir = (conv.last_message && conv.last_message.direction) ? conv.last_message.direction : conv.last_direction;
  document.getElementById('profDir').textContent = (lastDir === 'out' || lastDir === 'outgoing') ? 'You replied last' : 'Customer replied last';
  document.getElementById('profTotal').textContent = msgCache[conv.id] ? msgCache[conv.id].length + '+' : '…';

  // 24h messaging window warning
  var warn = document.getElementById('winWarning');
  warn.style.display = (conv.within_messaging_window === false) ? 'flex' : 'none';
}

function hideChatPanel() {
  document.getElementById('chatEmpty').style.display   = 'flex';
  document.getElementById('chatHead').style.display    = 'none';
  document.getElementById('chatMsgs').style.display    = 'none';
  document.getElementById('chatReply').style.display   = 'none';
  document.getElementById('profEmpty').style.display   = 'flex';
  document.getElementById('profContent').style.display = 'none';
  document.getElementById('winWarning').style.display  = 'none';
}

// ── Render messages ────────────────────────────────────
function showMsgSkel() {
  var el = document.getElementById('chatMsgs');
  var dirs = ['in','out','in','in','out'];
  el.innerHTML = dirs.map(function(d){
    return '<div class="msg-skel '+d+' skel"><div class="msg-skel-av"></div><div class="msg-skel-bubble"></div></div>';
  }).join('');
}

function renderMsgs(msgs) {
  var el = document.getElementById('chatMsgs');
  el.innerHTML = '';
  var lastDate = '';
  msgs.forEach(function(m){
    var d = (m.sent_at||'').slice(0,10);
    if (d !== lastDate) { lastDate=d; el.appendChild(dateDivider(m.sent_at)); }
    el.appendChild(buildBubble(m));
  });
  el.scrollTop = el.scrollHeight;
}

function appendMsgs(msgs) {
  var el = document.getElementById('chatMsgs');
  var atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 80;
  msgs.forEach(function(m){ el.appendChild(buildBubble(m)); });
  if (atBottom) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
}

function dateDivider(iso) {
  var d = document.createElement('div');
  d.className = 'msg-date';
  d.innerHTML = '<span>' + fmtDate(iso) + '</span>';
  return d;
}

function buildBubble(m) {
  var conv = convs.find(function(c){ return c.id==activeId; }) || {};
  var row  = document.createElement('div');
  row.className = 'msg-row ' + m.direction;

  var avEl = document.createElement('div');
  avEl.className = 'msg-av';
  avEl.innerHTML = m.direction === 'in'
    ? (conv.customer_avatar ? '<img src="'+esc(conv.customer_avatar)+'" onerror="this.style.display=\'none\'">' : initials(conv.customer_name))
    : '<i class="fa-brands fa-facebook" style="font-size:11px"></i>';

  var col = document.createElement('div');
  col.className = 'msg-col';

  var bub = document.createElement('div');
  bub.className = 'msg-bubble';
  if (m.attachment_url) {
    bub.innerHTML = (m.message_text ? esc(m.message_text)+'<br>' : '') +
      '<span class="msg-attach"><a href="'+esc(m.attachment_url)+'" target="_blank" rel="noopener">📎 View attachment</a></span>';
  } else {
    bub.textContent = m.message_text || '';
  }

  var tm = document.createElement('div');
  tm.className = 'msg-time';
  tm.textContent = fmtTime(m.sent_at);

  col.appendChild(bub);
  col.appendChild(tm);

  if (m.direction === 'in') { row.appendChild(avEl); row.appendChild(col); }
  else { row.appendChild(col); row.appendChild(avEl); }
  return row;
}

// ── Auto-sync (background — no user action needed) ─────
function autoSync() {
  if (!PAGE || syncing) return;
  syncing = true;
  setSyncStatus('Syncing…', 'var(--text3)');

  fetch('inbox_api.php?action=sync', {
    method:'POST', credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ page_id: PAGE.id, page_token: PAGE.access_token })
  }).then(function(r){ return r.json(); })
    .then(function(d) {
      if (d.error) { setSyncStatus('', ''); return; }
      var n = d.synced || 0;
      if (n > 0) {
        setSyncStatus(n + ' synced', 'var(--green)');
        // Reload conversation list from start (reset pagination)
        var search = document.getElementById('searchInput').value.trim();
        oldestId = null;
        loadConvs(search || undefined);
        // Refresh active conv messages
        if (activeId) fetchMsgs(activeId, lastSeen[activeId]||null, true);
        setTimeout(function(){ setSyncStatus('', ''); }, 4000);
      } else {
        setSyncStatus('', '');
      }
    })
    .catch(function(){ setSyncStatus('', ''); })
    .finally(function(){ syncing = false; });
}

// Manual sync triggered by Sync button
function manualSync() {
  if (!PAGE) return;
  var btn = document.getElementById('syncBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Syncing…';

  var prevSyncing = syncing;
  syncing = false; // force run even if bg sync is in progress
  autoSync();

  // Re-enable button after sync
  setTimeout(function(){
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Sync';
    syncing = prevSyncing;
  }, 6000);
}

function setSyncStatus(text, color) {
  var el = document.getElementById('syncStatus');
  el.textContent = text;
  el.style.color = color || 'var(--text3)';
}

// ── Send ───────────────────────────────────────────────
var rTxt = document.getElementById('replyText');
var sBtn = document.getElementById('sendBtn');

rTxt.addEventListener('keydown', function(e) {
  if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
});
rTxt.addEventListener('input', function() {
  this.style.height='auto';
  this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});
sBtn.addEventListener('click', sendMsg);

function sendMsg() {
  if (sending || !activeId || !PAGE) return;
  var text = rTxt.value.trim();
  if (!text) return;

  sending = true;
  sBtn.disabled = true;
  rTxt.disabled = true;

  var fakeMsg = { direction:'out', message_text:text, sent_at: new Date().toISOString().replace('T',' ').slice(0,19), fb_message_id:'_'+Date.now() };
  if (!msgCache[activeId]) msgCache[activeId] = [];
  msgCache[activeId].push(fakeMsg);
  appendMsgs([fakeMsg]);
  rTxt.value = '';
  rTxt.style.height = 'auto';

  fetch('inbox_api.php?action=send', {
    method:'POST', credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ conv_id:activeId, message:text, page_id:PAGE.id, page_token:PAGE.access_token })
  }).then(function(r){ return r.json(); })
    .then(function(d) {
      if (d.error) { toast(d.error, 'err'); return; }
      var conv = convs.find(function(c){ return c.id==activeId; });
      if (conv) { conv.last_message=text; conv.last_direction='out'; renderConvList(); }
      if (d.sent_at) lastSeen[activeId] = d.sent_at;
    })
    .catch(function(){ toast('Send failed', 'err'); })
    .finally(function(){
      sending=false; sBtn.disabled=false; rTxt.disabled=false; rTxt.focus();
    });
}

// ── Helpers ────────────────────────────────────────────
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function initials(n){ return (n||'?').split(' ').map(function(w){return w[0]||'';}).join('').slice(0,2).toUpperCase()||'?'; }
function avatarHtml(url, name){ return url ? '<img src="'+esc(url)+'" onerror="this.style.display=\'none\'">' : initials(name); }

function relTime(iso) {
  if (!iso) return '';
  var d = new Date(iso.replace(' ','T')+'Z'), diff=(Date.now()-d)/1000;
  if (diff<60) return 'now';
  if (diff<3600) return Math.floor(diff/60)+'m';
  if (diff<86400) return Math.floor(diff/3600)+'h';
  if (diff<604800) return Math.floor(diff/86400)+'d';
  return d.toLocaleDateString('en',{month:'short',day:'numeric'});
}
function fmtDate(iso) {
  if (!iso) return '—';
  var d = new Date(iso.replace(' ','T')+'Z');
  return d.toLocaleDateString('en',{month:'short',day:'numeric',year:'numeric'});
}
function fmtTime(iso) {
  if (!iso) return '';
  var d = new Date(iso.replace(' ','T')+'Z');
  return d.toLocaleTimeString('en',{hour:'2-digit',minute:'2-digit',hour12:true});
}
function toast(msg, type, dur) {
  var t=document.getElementById('toast');
  t.textContent=msg; t.className='ib-toast show '+(type||'');
  clearTimeout(t._t); t._t=setTimeout(function(){ t.className='ib-toast'; },dur||2800);
}
</script>
</body>
</html>
