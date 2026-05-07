// web_ui.js — Web/localhost UI controller for Pro Facebook Page Messenger
// Version: 2.1.0 - Production UI improvements (2026-04-15)
// Requires fb_api.js to be loaded first. Calls fb_api.js functions directly.

const $ = (id) => document.getElementById(id);
const pct = (n) => `${Math.round((n || 0) * 100)}%`;
const uiTrackEvent = (name, props) => { if (typeof window.trackEvent === 'function') window.trackEvent(name, props || {}); };

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

let sendStartTime = null;
let allRecipients = [];
let recipientsPageId = null;
let isManualBroadcastRunning = false;
let activeBroadcastPageId = null;

function riskClass(level) {
  if (level === 'low') return 'intel-good';
  if (level === 'medium') return 'intel-warn';
  if (level === 'high') return 'intel-bad';
  return 'intel-neutral';
}

function setIntelValue(id, text, cls) {
  const el = $(id);
  if (!el) return;
  el.textContent = text;
  el.classList.remove('intel-good', 'intel-warn', 'intel-bad', 'intel-neutral');
  if (cls) el.classList.add(cls);
}

function formatEta(ms) {
  if (!ms || ms < 0) return '';
  const s = Math.round(ms / 1000);
  if (s < 60) return `~${s}s left`;
  const m = Math.floor(s / 60), rem = s % 60;
  return rem > 0 ? `~${m}m ${rem}s left` : `~${m}m left`;
}

function updateCampaignIntel() {
  const pageId = $('pageSelect')?.value || '';
  const message = ($('messageText')?.value || '').trim();
  const delay = Math.max(500, parseInt($('delayMs')?.value, 10) || 1200);
  const adviceEl = $('intelAdvice');

  const hasLoadedAudience = !!allRecipients.length && recipientsPageId === pageId;
  const audienceCount = hasLoadedAudience ? allRecipients.length : 0;
  const audienceLabel = hasLoadedAudience
    ? `${audienceCount.toLocaleString()} loaded`
    : (pageId ? 'Auto-load on start' : 'Select page first');
  setIntelValue('intelAudience', audienceLabel);

  let pace = 'Balanced';
  let paceRisk = 'low';
  if (delay < 900) { pace = 'Aggressive'; paceRisk = 'high'; }
  else if (delay < 1800) { pace = 'Balanced'; paceRisk = 'medium'; }
  else { pace = 'Safe'; paceRisk = 'low'; }
  setIntelValue('intelPace', pace, riskClass(paceRisk));

  const urlCount = (message.match(/https?:\/\/|www\./gi) || []).length;
  const exclamations = (message.match(/!/g) || []).length;
  const upperChars = (message.match(/[A-Z]/g) || []).length;
  const letterChars = (message.match(/[A-Za-z]/g) || []).length;
  const upperRatio = letterChars > 0 ? (upperChars / letterChars) : 0;
  const spamTerms = /(free money|guaranteed|urgent offer|click now|limited time)/i.test(message);

  let riskPoints = 0;
  if (message.length > 900) riskPoints += 1;
  if (urlCount > 1) riskPoints += 1;
  if (exclamations >= 4) riskPoints += 1;
  if (upperRatio > 0.45 && message.length > 30) riskPoints += 1;
  if (delay < 900) riskPoints += 1;
  if (spamTerms) riskPoints += 1;

  let riskLabel = 'Low';
  let riskLevel = 'low';
  if (riskPoints >= 4) { riskLabel = 'High'; riskLevel = 'high'; }
  else if (riskPoints >= 2) { riskLabel = 'Medium'; riskLevel = 'medium'; }
  setIntelValue('intelRisk', riskLabel, riskClass(riskLevel));

  const etaText = audienceCount > 0 ? formatEta(audienceCount * delay) : 'After load';
  setIntelValue('intelEta', etaText || 'After load');

  if (!pageId) {
    if (adviceEl) adviceEl.textContent = 'Select a page to enable broadcast readiness checks.';
    return;
  }
  if (!message) {
    if (adviceEl) adviceEl.textContent = 'Write a message to preview risk level and estimated campaign speed.';
    return;
  }

  const messages = [];
  if (message.length < 15) messages.push('Message is very short; add context for better trust.');
  if (urlCount > 1) messages.push('Multiple links detected; fewer links usually perform better.');
  if (riskLevel === 'high') messages.push('High risk detected; increase delay and soften wording.');
  if (paceRisk === 'high') messages.push('Aggressive pace can trigger rate limits on large audiences.');
  if (!messages.length) messages.push('Looks healthy. You are ready to start this campaign.');
  if (adviceEl) adviceEl.textContent = messages[0];
}

function updateEta(done, total, delayMs) {
  const el = $('etaText');
  if (!el) return;
  if (!done || !total || done >= total) { el.textContent = ''; return; }
  const remaining = total - done;
  const elapsedMs = Date.now() - (sendStartTime || Date.now());
  const msPerItem = elapsedMs / done;
  const eta = remaining * (msPerItem || delayMs);
  el.textContent = formatEta(eta);
}

function setLoading(btn, loading) {
  if (!btn) return;
  btn.disabled = !!loading;
  btn.classList.toggle('is-loading', !!loading);
  btn.classList.toggle('loading', !!loading);
}

async function runWithRetry(action, options = {}) {
  const maxAttempts = Math.max(1, Number(options.maxAttempts || 2));
  const label = options.label || 'request';
  let lastErr = null;
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      return await action(attempt);
    } catch (err) {
      lastErr = err;
      const errorId = (typeof window.reportClientError === 'function')
        ? window.reportClientError(err, { source: 'web_ui', label, attempt, maxAttempts })
        : null;
      if (attempt < maxAttempts) {
        showStatus(`${label} failed, retrying… (${attempt}/${maxAttempts - 1})`, 'warning');
        await new Promise((resolve) => setTimeout(resolve, 350 * attempt));
        continue;
      }
      if (errorId) showStatus(`${label} failed (${errorId}).`, 'error');
      else showStatus(`${label} failed.`, 'error');
    }
  }
  throw lastErr || new Error(label + ' failed');
}

async function loadPagesFromFacebook(options = {}) {
  const silent = !!options.silent;
  if (!silent) {
    showStatus('Loading pages from Facebook…', 'info');
  }
  const pages = await runWithRetry(async () => fetchUserPages(), { label: 'Loading pages', maxAttempts: 2 });
  renderPages(pages || []);
  uiTrackEvent('pages_refresh_success', { count: (pages || []).length, source: silent ? 'auto' : 'manual' });
  if (!silent) {
    if ((pages || []).length === 0) {
      showStatus('No pages found. Confirm app permissions and page admin access.', 'warning');
    } else {
      showStatus(`${(pages || []).length} page(s) loaded.`, 'success');
    }
  }
  return pages || [];
}

let statusTimer;
let recipientsStatusTimer;

function ensureRecipientsStatusCard() {
  const recipients = document.getElementById('recipients');
  if (!recipients) return null;
  let card = document.getElementById('recipientsStatusCard');
  if (!card) {
    card = document.createElement('div');
    card.id = 'recipientsStatusCard';
    card.className = 'rec-status-card';
    card.setAttribute('role', 'status');
    card.setAttribute('aria-live', 'polite');
    recipients.prepend(card);
  }
  return card;
}

function showStatus(msg, type = 'info', count = null) {
  const sb = document.getElementById('statusBar');
  const inlineCard = ensureRecipientsStatusCard();
  if (!sb && !inlineCard) return;
  clearTimeout(statusTimer);
  clearTimeout(recipientsStatusTimer);
  
  const icons = {
    success: 'fa-circle-check',
    error: 'fa-circle-xmark',
    warning: 'fa-triangle-exclamation',
    info: 'fa-circle-info'
  };
  const icon = icons[type] || icons.info;
  
  let html = `<i class="fa-solid ${icon}"></i><span>${String(msg).replace(/</g,'&lt;').replace(/>/g,'&gt;')}</span>`;
  if (count !== null) {
    html += `<span class="sb-count">${count.toLocaleString()} fetched</span>`;
  }

  if (sb) {
    sb.innerHTML = html;
    sb.style.display = 'flex';
    sb.className = `sb-${type}`;
    statusTimer = setTimeout(() => { sb.className = ''; sb.style.display = 'none'; }, 5000);
  }

  if (inlineCard) {
    inlineCard.innerHTML = html;
    inlineCard.className = `rec-status-card rs-${type}`;
    recipientsStatusTimer = setTimeout(() => {
      inlineCard.className = 'rec-status-card';
    }, 5000);
  }
}

function setLoginOnline() {
  const ls = document.getElementById('loginStatus');
  if (ls) ls.classList.add('online');
  const lt = document.getElementById('loginStatusText');
  if (lt) lt.textContent = 'Connected';
}

function updateSendHint() {
  const sendHint = $('sendHint');
  const pageSelect = $('pageSelect');
  if (!sendHint) return;
  const pageId = pageSelect?.value;
  if (!pageId) { sendHint.textContent = 'Select a page first'; return; }
  if (!allRecipients.length || recipientsPageId !== pageId) {
    sendHint.textContent = 'Ready to start. Recipients will load automatically.';
    updateCampaignIntel();
    return;
  }
  const filtered = getFilteredRecipients().length;
  sendHint.textContent = `Will send to ${filtered} recipient${filtered !== 1 ? 's' : ''}`;
  updateCampaignIntel();
}

function renderPages(pages) {
  const container = $('pageCards');
  const select = $('pageSelect');
  if (!container || !select) return;
  container.innerHTML = '';
  select.innerHTML = '';

  if (!pages || pages.length === 0) {
    container.innerHTML = `<div class="pages-empty"><i class="fa-brands fa-facebook"></i><p>No pages found.</p></div>`;
    return;
  }

  pages.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    select.appendChild(opt);

    const picUrl = p.picture?.data?.url || '';
    const initial = (p.name || '?').charAt(0).toUpperCase();
    const card = document.createElement('div');
    card.className = 'page-card';
    card.dataset.id = p.id;
    card.innerHTML = `
      ${picUrl
        ? `<img class="page-avatar" src="${escHtml(picUrl)}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
        : ''}
      <div class="page-avatar-fallback" style="${picUrl ? 'display:none' : ''}">${escHtml(initial)}</div>
      <div class="page-info">
        <div class="page-name">${escHtml(p.name)}</div>
        ${p.category ? `<div class="page-category">${escHtml(p.category)}</div>` : ''}
      </div>
      <div class="page-indicator"></div>
    `;
    card.addEventListener('click', () => {
      if (isManualBroadcastRunning && activeBroadcastPageId && p.id !== activeBroadcastPageId) {
        showStatus('Broadcast is running on another page. Pause/Stop first.', 'warning');
        return;
      }
      const previousPageId = select.value || null;
      container.querySelectorAll('.page-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      select.value = p.id;
      if (previousPageId && previousPageId !== p.id) {
        allRecipients = [];
        recipientsPageId = null;
        window.allRecipients = allRecipients;
        renderRecipients();
        updateStats();
      }
      updateSendHint();
      updateCampaignIntel();
    });
    container.appendChild(card);
  });
  if (pages.length > 0) container.querySelector('.page-card')?.click();
}

function getFilteredRecipients() {
  const filter = $('recipientFilter')?.value || 'all';
  if (filter === 'all') return allRecipients;
  if (filter.startsWith('status:')) {
    const s = filter.slice(7);
    return allRecipients.filter(r => r.status === s);
  }
  if (filter.startsWith('label:')) {
    const lbl = filter.slice(6);
    return allRecipients.filter(r => (r.labels || []).includes(lbl));
  }
  return allRecipients;
}

function buildFilterOptions(labelMap) {
  const rf = $('recipientFilter');
  if (!rf) return;
  rf.querySelectorAll('[data-label]').forEach(o => o.remove());
  const allLabels = new Set();
  Object.values(labelMap || {}).forEach(labels => labels.forEach(l => allLabels.add(l)));
  if (allLabels.size === 0) return;
  const sep = document.createElement('option');
  sep.disabled = true; sep.textContent = '── Labels ──'; sep.dataset.label = '1';
  rf.appendChild(sep);
  allLabels.forEach(label => {
    const opt = document.createElement('option');
    opt.value = `label:${label}`; opt.textContent = label.charAt(0).toUpperCase() + label.slice(1); opt.dataset.label = '1';
    rf.appendChild(opt);
  });
}

function renderRecipients() {
  const list = getFilteredRecipients();
  const rc = $('recipientCount');
  const rd = $('recipients');
  if (rc) rc.textContent = list.length;
  if (!rd) return;
  const statusCard = document.getElementById('recipientsStatusCard');
  rd.innerHTML = '';
  if (statusCard) rd.prepend(statusCard);

  if (list.length === 0) {
    rd.innerHTML = `<div class="table-empty"><div class="table-empty-icon">${allRecipients.length === 0 ? '💬' : '🔍'}</div><div>${allRecipients.length === 0 ? 'No recipients yet.<br>Press Start Broadcast to load and send.' : 'No recipients match the current filter.'}</div></div>`;
    return;
  }

  list.forEach(r => {
    const row = document.createElement('div');
    row.className = 'table-row';
    row.dataset.id = r.id;
    const statusClass = { sent: 'badge-sent', failed: 'badge-failed', pending: 'badge-pending' }[r.status] || 'badge-pending';
    const labelBadges = (r.labels || []).slice(0, 2).map(l => `<span class="badge badge-label" title="${escHtml(l)}">${escHtml(l)}</span>`).join('');
    const labelsSummary = (r.labels || []).length > 2 ? `<span class="badge badge-label">+${(r.labels || []).length - 2}</span>` : '';
    row.innerHTML = `<div class="mono truncate">${escHtml(r.id)}</div><div><span class="badge ${statusClass}">${escHtml(r.status)}</span>${labelBadges}${labelsSummary}</div><div class="err" title="${escHtml(r.error || '')}">${escHtml(r.error || '')}</div>`;
    rd.appendChild(row);
  });
  updateSendHint();
  updateCampaignIntel();
}

function updateStats() {
  const total = allRecipients.length;
  const sent = allRecipients.filter(r => r.status === 'sent').length;
  const failed = allRecipients.filter(r => r.status === 'failed').length;
  const st = $('statTotal'), ss = $('statSent'), sf = $('statFailed'), pb = $('progressBar'), pp = $('progressPct'), rc = $('recipientCount');
  if (st) st.textContent = total;
  if (ss) ss.textContent = sent;
  if (sf) sf.textContent = failed;
  const ratio = total ? (sent + failed) / total : 0;
  if (pb) pb.style.width = pct(ratio);
  if (pp) pp.textContent = pct(ratio);
  if (rc) rc.textContent = getFilteredRecipients().length;
}

function updateRecipientRow(item) {
  const r = allRecipients.find(r => r.id === item.id);
  if (r) { r.status = item.status; r.error = item.error || ''; }
  const row = $('recipients')?.querySelector(`[data-id="${item.id}"]`);
  if (!row) return;
  const badge = row.querySelector('.badge');
  const error = row.querySelector('.err');
  const statusClass = { sent: 'badge-sent', failed: 'badge-failed', pending: 'badge-pending' }[item.status] || 'badge-pending';
  if (badge) { badge.className = `badge ${statusClass}`; badge.textContent = item.status; }
  if (error) { error.textContent = item.error || ''; error.title = item.error || ''; }
}

function initFromStorage() {
  try {
    const queue = JSON.parse(localStorage.getItem('send_queue') || '[]');
    if (queue.length > 0) {
      allRecipients = queue;
      recipientsPageId = null;
      window.allRecipients = queue; // Update global reference
      renderRecipients();
      updateStats();
    }
  } catch (_) {}
}

async function loadRecipientsForPage(pageId) {
  const { psids, labelMap } = await runWithRetry(async () => fetchConversations(pageId, ({ fetched, total, pct }) => {
    const progressMsg = (pct != null)
      ? `Loading recipients… ${pct}%${total ? ` (${fetched.toLocaleString()} of ${total.toLocaleString()})` : ''}`
      : 'Loading recipients…';
    showStatus(progressMsg, 'info', fetched);
  }), { label: 'Loading recipients', maxAttempts: 2 });

  const list = (psids || []).map(id => ({ id, status: 'pending', error: '', labels: labelMap?.[id] || [] }));
  allRecipients = list;
  recipientsPageId = pageId;
  window.allRecipients = list;
  buildFilterOptions(labelMap || {});
  renderRecipients();
  updateStats();
  updateSendHint();
  updateCampaignIntel();
  return list;
}

// ── Image Attachment Panel ─────────────────────────────
let currentImageUrl = '';

function initImagePanel() {
  const toggle     = $('imgAttachToggle');
  const panel      = $('imgAttachPanel');
  const tabUrl     = $('imgTabUrl');
  const tabUpload  = $('imgTabUpload');
  const urlArea    = $('imgUrlArea');
  const uploadArea = $('imgUploadArea');
  const urlInput   = $('imgUrlInput');
  const urlLoad    = $('imgUrlLoad');
  const fileInput  = $('imgFileInput');
  const dropZone   = $('imgDropZone');
  const uploadProg = $('imgUploadProgress');
  const uploadTxt  = $('imgUploadProgressText');
  const previewWrap= $('imgPreviewWrap');
  const previewImg = $('imgPreviewThumb');
  const previewLbl = $('imgPreviewLabel');
  const clearBtn   = $('imgClearBtn');
  const badge      = $('imgAttachBadge');
  if (!toggle || !panel) return;

  function showPreview(url, label) {
    currentImageUrl = url;
    if (previewImg) previewImg.src = url;
    if (previewLbl) previewLbl.textContent = label || 'Image ready to send';
    if (previewWrap) previewWrap.style.display = '';
    if (badge) badge.style.display = '';
    if (urlArea) urlArea.style.display = 'none';
    if (uploadArea) uploadArea.style.display = 'none';
  }

  function clearImage() {
    currentImageUrl = '';
    if (previewImg) previewImg.src = '';
    if (previewWrap) previewWrap.style.display = 'none';
    if (badge) badge.style.display = 'none';
    if (urlInput) urlInput.value = '';
    if (fileInput) fileInput.value = '';
    // Show back the active tab area
    const isUpload = tabUpload && tabUpload.classList.contains('active');
    if (urlArea) urlArea.style.display = isUpload ? 'none' : '';
    if (uploadArea) uploadArea.style.display = isUpload ? '' : 'none';
  }

  // Toggle panel open/close
  toggle.addEventListener('click', () => {
    const hidden = panel.hasAttribute('hidden');
    if (hidden) { panel.removeAttribute('hidden'); toggle.setAttribute('aria-expanded', 'true'); }
    else { panel.setAttribute('hidden', ''); toggle.setAttribute('aria-expanded', 'false'); }
  });

  // Tab switching
  function switchTab(tab) {
    const isUpload = tab === 'upload';
    if (tabUrl)    { tabUrl.classList.toggle('active', !isUpload); tabUrl.setAttribute('aria-selected', String(!isUpload)); }
    if (tabUpload) { tabUpload.classList.toggle('active', isUpload); tabUpload.setAttribute('aria-selected', String(isUpload)); }
    if (urlArea)    urlArea.style.display    = isUpload ? 'none' : '';
    if (uploadArea) uploadArea.style.display = isUpload ? '' : 'none';
    if (previewWrap) previewWrap.style.display = 'none';
    clearImage();
  }
  tabUrl?.addEventListener('click', () => switchTab('url'));
  tabUpload?.addEventListener('click', () => switchTab('upload'));

  // URL load
  function loadFromUrl() {
    const url = (urlInput?.value || '').trim();
    if (!url) return;
    if (!/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i.test(url) && !/^https?:\/\/.+/i.test(url)) {
      if (window.showToast) window.showToast('Please enter a valid image URL.', 'warning');
      return;
    }
    const img = new Image();
    img.onload = () => showPreview(url, 'URL image ready');
    img.onerror = () => {
      if (window.showToast) window.showToast('Could not load image from URL. Check the link.', 'error');
    };
    img.src = url;
  }
  urlLoad?.addEventListener('click', loadFromUrl);
  urlInput?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); loadFromUrl(); } });

  // File input
  fileInput?.addEventListener('change', () => { if (fileInput.files[0]) handleFile(fileInput.files[0]); });
  dropZone?.addEventListener('click', () => fileInput?.click());
  dropZone?.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone?.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
  dropZone?.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
  });

  async function handleFile(file) {
    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
      if (window.showToast) window.showToast('Only JPEG, PNG, GIF, WebP images allowed.', 'error');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      if (window.showToast) window.showToast('File too large. Maximum 5 MB.', 'error');
      return;
    }
    // Show upload progress
    if (uploadProg) uploadProg.style.display = '';
    if (uploadTxt)  uploadTxt.textContent = 'Uploading…';
    if (dropZone)   dropZone.style.display = 'none';

    try {
      const csrfToken = (typeof window.getCsrfToken === 'function') ? await window.getCsrfToken() : '';
      const formData = new FormData();
      formData.append('image', file);
      const res  = await fetch('upload_image.php', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: formData
      });
      const data = await res.json();
      if (data.success && data.url) {
        showPreview(data.url, file.name);
        if (window.showToast) window.showToast('Image uploaded successfully.', 'success');
      } else {
        throw new Error(data.error || 'Upload failed.');
      }
    } catch (e) {
      if (window.showToast) window.showToast('Upload error: ' + (e.message || 'Unknown error'), 'error');
      if (dropZone)   dropZone.style.display = '';
    } finally {
      if (uploadProg) uploadProg.style.display = 'none';
    }
  }

  // Clear button
  clearBtn?.addEventListener('click', clearImage);
}

// ── DOM Initialization ─────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initFromStorage();
  initImagePanel();

  const btnLogin = $('btnLogin'), btnFetchPages = $('btnFetchPages'),
        btnStart = $('btnStart'), btnPause = $('btnPause'), btnResume = $('btnResume'), btnStop = $('btnStop'),
        recipientFilter = $('recipientFilter'), messageText = $('messageText'), delayMs = $('delayMs');

  recipientFilter?.addEventListener('change', () => { renderRecipients(); updateSendHint(); });

  messageText?.addEventListener('input', () => {
    const charCount = $('charCount');
    const len = messageText.value.length;
    if (charCount) {
      charCount.textContent = `${len} / 2000`;
      charCount.classList.remove('warn', 'danger');
      if (len >= 2000) charCount.classList.add('danger');
      else if (len >= 1600) charCount.classList.add('warn');
    }
    updateCampaignIntel();
  });

  delayMs?.addEventListener('change', updateCampaignIntel);
  document.querySelectorAll('.delay-preset').forEach(btn => {
    btn.addEventListener('click', () => setTimeout(updateCampaignIntel, 0));
  });

  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      if (btnStart && !btnStart.disabled) btnStart.click();
    }
  });

  btnLogin?.addEventListener('click', async () => {
    setLoading(btnLogin, true);
    try {
      uiTrackEvent('login_attempt', { source: 'web_ui_btnLogin' });
      await startFacebookLogin();
      setLoginOnline();
      uiTrackEvent('login_success', { source: 'web_ui_btnLogin' });
      showStatus('Logged in. Loading your pages…', 'success');
      try { await loadPagesFromFacebook({ silent: true }); } catch (_) {}
    } catch (e) {
      uiTrackEvent('login_error', { source: 'web_ui_btnLogin', message: e.message || 'login_failed' });
      showStatus(e.message || 'Login failed.', 'error');
    }
    setLoading(btnLogin, false);
  });

  btnFetchPages?.addEventListener('click', async () => {
    setLoading(btnFetchPages, true);
    uiTrackEvent('pages_refresh_click', { source: 'manual' });
    try {
      await loadPagesFromFacebook({ silent: false });
    } catch (e) {
      uiTrackEvent('pages_refresh_error', { message: e.message || 'failed_to_fetch_pages' });
      const msg = e && e.message ? e.message : 'Failed to fetch pages.';
      showStatus(msg, 'error');
      if (typeof window.showToast === 'function') {
        window.showToast('Refresh failed: ' + msg, 'error');
      }
    }
    setLoading(btnFetchPages, false);
  });

  btnStart?.addEventListener('click', async () => {
    const pageId = $('pageSelect')?.value, text = messageText?.value.trim(), delay = Math.max(500, parseInt(delayMs?.value, 10) || 1200);
    if (!pageId) return showStatus('Select a page first.', 'warning');
    if (!text && !currentImageUrl) return showStatus('Enter a message or attach an image.', 'warning');

    // Get FB User ID from storage, and ensure it's loaded from server
    let fbUserId = null;
    try {
      const storedUser = JSON.parse(localStorage.getItem('fbcast_user') || '{}');
      fbUserId = storedUser.fb_user_id || storedUser.id || null;
    } catch (e) {}

    // If fbcast_user not loaded, try syncing from server before sending
    if (!fbUserId && typeof window.syncQuotaFromServer === 'function') {
      showStatus('Initializing quota from server…', 'info');
      await window.syncQuotaFromServer();
      try {
        const storedUser = JSON.parse(localStorage.getItem('fbcast_user') || '{}');
        fbUserId = storedUser.fb_user_id || storedUser.id || null;
      } catch (e) {}
    }

    if (!fbUserId) {
      return showStatus('User session not initialized. Please refresh the page.', 'warning');
    }

    setLoading(btnStart, true);
    let recipientIds = [];
    try {
      if (!allRecipients.length || recipientsPageId !== pageId) {
        uiTrackEvent('recipients_auto_load_start', { pageId });
        const loaded = await loadRecipientsForPage(pageId);
        uiTrackEvent('recipients_auto_load_success', { pageId, count: loaded.length });
      }
      recipientIds = getFilteredRecipients().map(r => r.id);
    } catch (e) {
      uiTrackEvent('recipients_auto_load_error', { pageId, message: e.message || 'failed_to_load_recipients' });
      showStatus(e.message || 'Failed to load recipients.', 'error');
      setLoading(btnStart, false);
      return;
    }

    if (!recipientIds.length) {
      showStatus('No conversations found for this page.', 'warning');
      setLoading(btnStart, false);
      return;
    }

    uiTrackEvent('broadcast_start', { mode: 'manual', pageId, recipients: recipientIds.length, delayMs: delay });
    isManualBroadcastRunning = true;
    activeBroadcastPageId = pageId;
    sendStartTime = Date.now();
    $('progressBar')?.classList.add('progress-bar--active');
    try {
      await enqueueAndSendUtility({
        pageId, messageText: text, imageUrl: currentImageUrl, recipientIds, delayMs: delay,
        fbUserId,
        onProgress: ({ index, total, item }) => { updateRecipientRow(item); updateStats(); updateEta(index, total, delay); if(window.updateQuotaUI) window.updateQuotaUI(); },
        onDone: () => {
          isManualBroadcastRunning = false;
          activeBroadcastPageId = null;
          $('progressBar')?.classList.remove('progress-bar--active');
          setLoading(btnStart, false);
          if ($('etaText')) $('etaText').textContent = '';
          showStatus('All messages processed.', 'success');
          uiTrackEvent('broadcast_complete', { mode: 'manual', pageId });
          if(window.updateQuotaUI) window.updateQuotaUI();
        }
      });
    } catch (e) {
      isManualBroadcastRunning = false;
      activeBroadcastPageId = null;
      $('progressBar')?.classList.remove('progress-bar--active');
      uiTrackEvent('broadcast_error', { mode: 'manual', pageId, message: e.message || 'failed_to_start' });
      showStatus(e.message || 'Failed to start.', 'error');
      setLoading(btnStart, false);
    }
  });

  btnPause?.addEventListener('click', () => { pauseSending(); uiTrackEvent('broadcast_pause', { mode: 'manual' }); showStatus('Paused.', 'warning'); });
  btnResume?.addEventListener('click', () => { resumeSending(); uiTrackEvent('broadcast_resume', { mode: 'manual' }); showStatus('Resumed.', 'info'); });
  btnStop?.addEventListener('click', () => {
    stopSending();
    isManualBroadcastRunning = false;
    activeBroadcastPageId = null;
    uiTrackEvent('broadcast_stop', { mode: 'manual' });
    $('progressBar')?.classList.remove('progress-bar--active');
    setLoading(btnStart, false);
    if ($('etaText')) $('etaText').textContent = '';
    showStatus('Stopped.', 'error');
  });

  updateCampaignIntel();
});

// Expose globals for other scripts
window.showStatus = showStatus;
window.loadPagesFromFacebook = loadPagesFromFacebook;
window.renderPages = renderPages;
window.renderRecipients = renderRecipients;
window.updateStats = updateStats;
window.buildFilterOptions = buildFilterOptions;
window.allRecipients = allRecipients;
// Image URL getter for auto-send (index-page.js)
Object.defineProperty(window, '_imgAttachUrl', { get: () => currentImageUrl, configurable: true });
