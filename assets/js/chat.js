(() => {
  const BASE = document.body.dataset.base || '';

  const state = {
    conversations: [],
    users: [],
    me: null,
    activeConversationId: null,
    activePeer: null,
    lastMessageId: 0,
    pollTimer: null,
    lastInputValue: '',
    lastTypingStatus: 'idle',
    typingSendTimer: null,
    idleTimer: null,
  };

  const EMOJIS = [
    '😀', '😁', '😂', '🤣', '😊', '😍', '😘', '😎',
    '🙂', '😉', '🤗', '🤩', '🤔', '🤨', '😐', '😑',
    '😶', '🙄', '😏', '😣', '😥', '😮', '🤐', '😯',
    '😪', '😫', '🥱', '😴', '😌', '😛', '😜', '😝',
    '🤤', '😒', '😓', '😔', '😕', '🙃', '🤑', '😲',
    '☹️', '🙁', '😖', '😞', '😟', '😤', '😢', '😭',
    '😦', '😧', '😨', '😩', '🤯', '😬', '😰', '😱',
    '🥵', '🥶', '😳', '🤪', '😵', '😡', '😠', '🤬',
    '😷', '🤒', '🤕', '🤢', '🤮', '🥴', '😇', '🥳',
    '😈', '👿', '💀', '☠️', '💩', '🤡', '👹', '👺',
    '👻', '👽', '👾', '🤖', '😺', '😸', '😹', '😻',
    '👍', '👎', '👏', '🙌', '👐', '🤝', '✌️', '🤞',
    '🤟', '🤘', '👌', '🤌', '👈', '👉', '👆', '👇',
    '👋', '🤚', '🖐️', '✋', '🖖', '👊', '✊', '🤛',
    '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍',
    '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘',
    '🔥', '⭐', '🌟', '✨', '⚡', '💥', '🎉', '🎊',
    '🎈', '🎁', '🏆', '🥇', '🎯', '🎵', '🎶', '💯',
    '✅', '❌', '❓', '❗', '💬', '👋', '🙏', '💪',
  ];

  const els = {
    sidebar: document.getElementById('sidebar'),
    conversationList: document.getElementById('conversationList'),
    peopleList: document.getElementById('peopleList'),
    userSearch: document.getElementById('userSearch'),
    emptyState: document.getElementById('emptyState'),
    thread: document.getElementById('thread'),
    messages: document.getElementById('messages'),
    composer: document.getElementById('composer'),
    messageInput: document.getElementById('messageInput'),
    peerAvatar: document.getElementById('peerAvatar'),
    peerName: document.getElementById('peerName'),
    peerStatus: document.getElementById('peerStatus'),
    backToList: document.getElementById('backToList'),
    chatMain: document.querySelector('.chat-main'),
    typingIndicator: document.getElementById('typingIndicator'),
    typingText: document.getElementById('typingText'),
    emojiBtn: document.getElementById('emojiBtn'),
    emojiPanel: document.getElementById('emojiPanel'),
    emojiGrid: document.getElementById('emojiGrid'),
    attachInput: document.getElementById('attachInput'),
    attachPreview: document.getElementById('attachPreview'),
    attachName: document.getElementById('attachName'),
    attachClear: document.getElementById('attachClear'),
    locationBtn: document.getElementById('locationBtn'),
    sendBtn: document.getElementById('sendBtn'),
    micBtn: document.getElementById('micBtn'),
    composerMain: document.getElementById('composerMain'),
    voiceRecordBar: document.getElementById('voiceRecordBar'),
    voiceCancelBtn: document.getElementById('voiceCancelBtn'),
    voiceSendBtn: document.getElementById('voiceSendBtn'),
    voiceTimer: document.getElementById('voiceTimer'),
    voiceSlideHint: document.getElementById('voiceSlideHint'),
    chatMenuBtn: document.getElementById('chatMenuBtn'),
    chatMenuDropdown: document.getElementById('chatMenuDropdown'),
  };

  let selectedMessage = null;
  let longPressTimer = null;

  let selectedFile = null;
  let activeAudio = null;

  const voice = {
    mediaRecorder: null,
    stream: null,
    chunks: [],
    startedAt: 0,
    timerId: null,
    duration: 0,
    recording: false,
    cancelled: false,
    mimeType: '',
    maxSeconds: 300,
    locked: false,
    holding: false,
    pressStart: 0,
    pressX: 0,
    pressY: 0,
    slideCancel: false,
    pointerId: null,
    abortBeforeStart: false,
    starting: false,
    audioContext: null,
    processor: null,
    sourceNode: null,
    pcmChunks: [],
    sampleRate: 16000,
    peakLevel: 0,
  };

  function escapeHtml(text) {
    return String(text)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatTime(value) {
    if (!value) return '';
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString([], {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  async function api(url, options = {}) {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
    });
    const data = await res.json();
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || 'Request failed');
    }
    return data;
  }

  function parseServerDate(value) {
    if (!value) return null;
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return null;
    return date;
  }

  function startOfDay(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return d;
  }

  function formatLastSeenText(lastSeen) {
    const date = parseServerDate(lastSeen);
    if (!date) return 'last seen recently';

    const now = new Date();
    const today = startOfDay(now);
    const thatDay = startOfDay(date);
    const dayDiff = Math.round((today - thatDay) / 86400000);
    const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    if (dayDiff === 0) return `last seen today at ${time}`;
    if (dayDiff === 1) return `last seen yesterday at ${time}`;
    if (dayDiff > 1 && dayDiff < 7) {
      const weekday = date.toLocaleDateString([], { weekday: 'long' });
      return `last seen ${weekday} at ${time}`;
    }
    const day = date.toLocaleDateString([], { day: '2-digit', month: '2-digit', year: 'numeric' });
    return `last seen ${day} at ${time}`;
  }

  function statusIconHtml(kind, peer = null) {
    if (kind === 'online') {
      return '<span class="status-pill status-online">online</span>';
    }
    if (kind === 'typing') {
      return '<span class="status-pill status-typing">typing...</span>';
    }
    if (kind === 'erasing') {
      return '<span class="status-pill status-erasing">erasing...</span>';
    }
    const lastSeen = peer && peer.last_seen ? peer.last_seen : null;
    return `<span class="status-pill status-offline">${escapeHtml(formatLastSeenText(lastSeen))}</span>`;
  }

  function defaultPeerStatusHtml() {
    if (!state.activePeer) return statusIconHtml('offline');
    if (state.activePeer.online) return statusIconHtml('online', state.activePeer);
    return statusIconHtml('offline', state.activePeer);
  }

  function presenceIcon(online) {
    return online
      ? '<i class="bi bi-circle-fill presence-icon online" title="Online"></i>'
      : '<i class="bi bi-circle presence-icon offline" title="Offline"></i>';
  }

  function setPeerActivity(status) {
    if (!els.typingIndicator || !els.typingText || !els.peerStatus) return;

    if (status === 'typing') {
      els.typingIndicator.classList.remove('d-none', 'is-erasing');
      els.typingText.textContent = 'typing...';
      els.peerStatus.innerHTML = statusIconHtml('typing');
      return;
    }

    if (status === 'erasing') {
      els.typingIndicator.classList.remove('d-none');
      els.typingIndicator.classList.add('is-erasing');
      els.typingText.textContent = 'erasing...';
      els.peerStatus.innerHTML = statusIconHtml('erasing');
      return;
    }

    els.typingIndicator.classList.add('d-none');
    els.typingIndicator.classList.remove('is-erasing');
    els.typingText.textContent = 'typing...';
    els.peerStatus.innerHTML = defaultPeerStatusHtml();
  }

  async function sendTypingStatus(status) {
    if (!state.activeConversationId) return;
    if (state.lastTypingStatus === status) return;

    state.lastTypingStatus = status;
    try {
      await api(`${BASE}/api/typing`, {
        method: 'POST',
        body: JSON.stringify({
          conversation_id: state.activeConversationId,
          status,
        }),
      });
    } catch (_) {
      // ignore transient typing errors
    }
  }

  function scheduleIdle() {
    clearTimeout(state.idleTimer);
    state.idleTimer = setTimeout(() => {
      sendTypingStatus('idle');
    }, 1500);
  }

  function handleComposerInput() {
    updateComposerAction();
    if (!state.activeConversationId) return;

    const value = els.messageInput.value;
    const prev = state.lastInputValue;
    let status = 'idle';

    if (value.length === 0) {
      status = 'idle';
    } else if (value.length < prev.length) {
      status = 'erasing';
    } else if (value.length > prev.length) {
      status = 'typing';
    } else {
      status = value.trim() ? 'typing' : 'idle';
    }

    state.lastInputValue = value;

    clearTimeout(state.typingSendTimer);
    state.typingSendTimer = setTimeout(() => {
      sendTypingStatus(status);
      if (status !== 'idle') {
        scheduleIdle();
      }
    }, 120);
  }

  function avatarHtml(person, online) {
    const onlineClass = online ? ' online' : '';
    if (person.avatar_url) {
      return `<div class="avatar has-image${onlineClass}"><img src="${escapeHtml(person.avatar_url)}" alt=""></div>`;
    }
    const color = person.avatar_color || '#0d6efd';
    const letter = person.initials || '?';
    return `<div class="avatar${onlineClass}" style="background:${escapeHtml(color)}">${escapeHtml(letter)}</div>`;
  }

  function setPeerAvatar(peer) {
    els.peerAvatar.classList.toggle('online', !!peer.online);
    els.peerAvatar.classList.toggle('has-image', !!peer.avatar_url);
    if (peer.avatar_url) {
      els.peerAvatar.style.background = '#e5e7eb';
      els.peerAvatar.innerHTML = `<img src="${escapeHtml(peer.avatar_url)}" alt="">`;
    } else {
      els.peerAvatar.style.background = peer.avatar_color || '#0d6efd';
      els.peerAvatar.textContent = peer.initials || '?';
    }
  }

  function renderLists(filter = '') {
    const q = filter.trim().toLowerCase();

    const isSystem = (state.me && state.me.user_type === 'system')
      || document.body.dataset.userType === 'system';

    const conversations = state.conversations.filter((c) => {
      const hay = `${c.display_name} ${isSystem && c.username ? c.username : ''}`.toLowerCase();
      return !q || hay.includes(q);
    });

    els.conversationList.innerHTML = conversations.length
      ? conversations.map((c) => `
          <button class="list-item ${state.activeConversationId === c.conversation_id ? 'active' : ''}"
                  data-conversation-id="${c.conversation_id}" type="button">
            ${avatarHtml(c, !!c.online)}
            <div class="meta">
              <div class="d-flex justify-content-between gap-2 align-items-center">
                <strong class="text-truncate">${escapeHtml(c.display_name)}</strong>
                <span class="d-inline-flex align-items-center gap-2">
                  ${presenceIcon(!!c.online)}
                  ${c.unread_count > 0 ? `<span class="badge-unread">${c.unread_count}</span>` : ''}
                </span>
              </div>
              <div class="preview">${escapeHtml(c.last_message || 'No messages yet')}</div>
            </div>
          </button>
        `).join('')
      : '<div class="px-3 py-2 text-secondary small">No conversations yet</div>';

    const people = state.users.filter((u) => {
      const hay = `${u.display_name} ${isSystem && u.username ? u.username : ''}`.toLowerCase();
      return !q || hay.includes(q);
    });

    els.peopleList.innerHTML = people.length
      ? people.map((u) => {
          const preview = isSystem && u.username
            ? `@${escapeHtml(u.username)} · ${u.has_conversation ? 'Open chat' : 'Start chat'}`
            : (u.has_conversation ? 'Open chat' : 'Start chat');
          return `
          <button class="list-item" data-user-id="${u.id}" type="button">
            ${avatarHtml(u, !!u.online)}
            <div class="meta">
              <div class="d-flex justify-content-between gap-2 align-items-center">
                <strong class="text-truncate">${escapeHtml(u.display_name)}</strong>
                ${presenceIcon(!!u.online)}
              </div>
              <div class="preview">${preview}</div>
            </div>
          </button>`;
        }).join('')
      : `<div class="px-3 py-2 text-secondary small">No friends yet. <a href="${BASE}/friends">Add friends</a></div>`;
  }

  function showThread(peer) {
    state.activePeer = peer;
    state.lastInputValue = '';
    state.lastTypingStatus = 'idle';
    closeChatMenu();
    els.emptyState.classList.add('d-none');
    els.thread.classList.remove('d-none');
    setPeerAvatar(peer);
    els.peerName.textContent = peer.display_name;
    els.peerStatus.innerHTML = defaultPeerStatusHtml();
    setPeerActivity('idle');
    els.sidebar.classList.remove('show-mobile');
    els.chatMain.classList.remove('hide-mobile');
  }

  function closeThreadView() {
    closeChatMenu();
    state.activeConversationId = null;
    state.activePeer = null;
    state.lastMessageId = 0;
    if (els.messages) els.messages.innerHTML = '';
    els.thread.classList.add('d-none');
    els.emptyState.classList.remove('d-none');
    els.sidebar.classList.add('show-mobile');
    els.chatMain.classList.add('hide-mobile');
    renderLists(els.userSearch ? els.userSearch.value : '');
  }

  function closeChatMenu() {
    if (els.chatMenuDropdown) els.chatMenuDropdown.classList.add('d-none');
    if (els.chatMenuBtn) els.chatMenuBtn.setAttribute('aria-expanded', 'false');
    const wrap = els.chatMenuBtn && els.chatMenuBtn.closest('.chat-menu');
    if (wrap) wrap.classList.remove('is-open');
  }

  function toggleChatMenu() {
    if (!els.chatMenuDropdown) return;
    const open = els.chatMenuDropdown.classList.contains('d-none');
    if (open) {
      els.chatMenuDropdown.classList.remove('d-none');
      if (els.chatMenuBtn) els.chatMenuBtn.setAttribute('aria-expanded', 'true');
      const wrap = els.chatMenuBtn && els.chatMenuBtn.closest('.chat-menu');
      if (wrap) wrap.classList.add('is-open');
    } else {
      closeChatMenu();
    }
  }

  async function clearActiveChat() {
    if (!state.activeConversationId) return;
    if (!window.confirm('Clear this chat? Messages will be removed from this chat only for you.')) {
      return;
    }
    const conversationId = state.activeConversationId;
    closeChatMenu();
    try {
      await api(`${BASE}/api/conversation-clear`, {
        method: 'POST',
        body: JSON.stringify({ conversation_id: conversationId }),
      });
      if (els.messages) els.messages.innerHTML = '';
      state.lastMessageId = 0;
      await loadConversations();
    } catch (err) {
      alert(err.message || 'Could not clear chat');
    }
  }

  async function deleteActiveChat() {
    if (!state.activeConversationId) return;
    if (!window.confirm('Delete this chat? It will be removed from your chat list.')) {
      return;
    }
    const conversationId = state.activeConversationId;
    closeChatMenu();
    try {
      await api(`${BASE}/api/conversation-delete`, {
        method: 'POST',
        body: JSON.stringify({ conversation_id: conversationId }),
      });
      await sendTypingStatus('idle');
      closeThreadView();
      await loadConversations();
    } catch (err) {
      alert(err.message || 'Could not delete chat');
    }
  }

  function formatBytes(bytes) {
    const n = Number(bytes) || 0;
    if (n < 1024) return `${n} B`;
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
    return `${(n / (1024 * 1024)).toFixed(1)} MB`;
  }

  function formatDuration(seconds) {
    const total = Math.max(0, Math.floor(Number(seconds) || 0));
    const m = Math.floor(total / 60);
    const s = String(total % 60).padStart(2, '0');
    return `${m}:${s}`;
  }

  function buildWaveBars(seed) {
    const heights = [
      8, 14, 22, 12, 18, 26, 10, 20, 16, 24, 11, 19, 27, 13, 21, 9, 17, 23, 15, 25,
      12, 18, 22, 10, 16, 24, 14, 20, 11, 19, 26, 13, 21, 15, 23, 9, 17, 25, 12, 18,
    ];
    const offset = Math.abs(Number(seed) || 0) % heights.length;
    return heights
      .map((_, i) => {
        const height = heights[(i + offset) % heights.length];
        return `<span style="height:${height}px"></span>`;
      })
      .join('');
  }

  function formatVoiceClock(value) {
    if (!value) return '';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function receiptStatus(m) {
    if (!m) return 'sent';
    if (m.status === 'seen' || m.is_read) return 'seen';
    if (m.status === 'delivered' || m.is_delivered) return 'delivered';
    return 'sent';
  }

  function renderTicks(m) {
    if (!m || !m.is_mine) return '';
    const status = receiptStatus(m);
    const playedClass = m.is_audio && m.is_played ? ' is-played' : '';
    let icon = 'bi-check';
    let title = 'Sent';
    if (status === 'delivered') {
      icon = 'bi-check-all';
      title = 'Delivered';
    } else if (status === 'seen') {
      icon = 'bi-check-all';
      title = m.is_audio && m.is_played ? 'Played' : 'Seen';
    }
    const playedMic = (m.is_audio && m.is_played)
      ? '<i class="bi bi-mic-fill tick-played-mic" title="Played"></i>'
      : '';
    return `<span class="msg-ticks status-${status}${playedClass}" data-ticks title="${title}">${playedMic}<i class="bi ${icon}"></i></span>`;
  }

  function renderMeta(m, { voice = false } = {}) {
    const clock = voice ? formatVoiceClock(m.created_at) : formatTime(m.created_at);
    return `
      <span class="msg-meta">
        <span class="msg-clock">${escapeHtml(clock)}</span>
        ${renderTicks(m)}
      </span>
    `;
  }

  function applyReceipts(receipts) {
    if (!Array.isArray(receipts) || !receipts.length || !els.messages) return;
    receipts.forEach((r) => {
      const row = els.messages.querySelector(`[data-message-id="${r.id}"]`);
      if (!row) return;
      row.dataset.status = r.status || 'sent';
      row.dataset.isRead = r.is_read ? '1' : '0';
      row.dataset.isDelivered = r.is_delivered ? '1' : '0';
      row.dataset.isPlayed = r.is_played ? '1' : '0';

      const ticks = row.querySelector('[data-ticks]');
      if (ticks) {
        const fake = {
          is_mine: true,
          is_audio: !!r.is_audio || !!row.querySelector('.voice-message'),
          is_read: !!r.is_read,
          is_delivered: !!r.is_delivered,
          is_played: !!r.is_played,
          status: r.status,
        };
        ticks.outerHTML = renderTicks(fake);
      }
    });
  }

  async function markVoicePlayed(messageId) {
    if (!messageId) return;
    try {
      const data = await api(`${BASE}/api/message-played`, {
        method: 'POST',
        body: JSON.stringify({ message_id: Number(messageId) }),
      });
      if (data.receipt) applyReceipts([data.receipt]);
    } catch (_) {
      // ignore
    }
  }

  function renderAttachment(m) {
    let html = '';
    if (m.has_location && m.maps_url) {
      html += `
        <a class="location-card" href="${escapeHtml(m.maps_url)}" target="_blank" rel="noopener">
          <strong><i class="bi bi-geo-alt-fill"></i> Current location</strong>
          <small>${escapeHtml(Number(m.location_lat).toFixed(5))}, ${escapeHtml(Number(m.location_lng).toFixed(5))}</small>
          <small>Open in map</small>
        </a>
      `;
    }
    if (!m.has_file) return html;
    if (m.is_audio && m.file_url) {
      const duration = Number(m.duration_seconds) || 0;
      html += `
        <div class="msg-attachment voice-attachment">
          <div class="voice-message" data-voice-id="${m.id}" data-duration="${duration}" data-is-mine="${m.is_mine ? '1' : '0'}">
            <button type="button" class="voice-play-btn" data-voice-play aria-label="Play voice message">
              <i class="bi bi-play-fill"></i>
            </button>
            <div class="voice-meta">
              <div class="voice-wave" data-voice-wave>${buildWaveBars(m.id)}</div>
              <div class="voice-footer">
                <span data-voice-time>${escapeHtml(formatDuration(duration))}</span>
                <button type="button" class="voice-speed d-none" data-voice-speed>1x</button>
                ${renderMeta(m, { voice: true })}
              </div>
            </div>
            <audio preload="none" src="${escapeHtml(m.file_url)}" data-src="${escapeHtml(m.file_url)}"></audio>
          </div>
        </div>
      `;
      return html;
    }
    if (m.is_image && m.file_url) {
      html += `
        <div class="msg-attachment">
          <a href="${escapeHtml(m.file_url)}" target="_blank" rel="noopener">
            <img src="${escapeHtml(m.file_url)}" alt="${escapeHtml(m.file_name || 'image')}">
          </a>
        </div>
      `;
      return html;
    }
    html += `
      <div class="msg-attachment">
        <a class="file-chip" href="${escapeHtml(m.download_url || m.file_url)}" target="_blank" rel="noopener">
          <i class="bi bi-file-earmark"></i>
          <span>${escapeHtml(m.file_name || 'File')} · ${escapeHtml(formatBytes(m.file_size))}</span>
        </a>
      </div>
    `;
    return html;
  }

  function updateComposerAction() {
    // WhatsApp-style: mic when empty, send when typing/file attached.
    const canSend = !!(els.messageInput && els.messageInput.value.trim()) || !!selectedFile;
    if (voice.recording) {
      if (els.sendBtn) els.sendBtn.classList.add('d-none');
      if (els.micBtn) els.micBtn.classList.add('d-none');
      return;
    }
    if (els.sendBtn) {
      els.sendBtn.disabled = false;
      els.sendBtn.classList.toggle('d-none', !canSend);
    }
    if (els.micBtn) {
      els.micBtn.disabled = false;
      els.micBtn.classList.toggle('d-none', canSend);
    }
  }

  function setVoiceUiRecording(isRecording) {
    if (els.composer) els.composer.classList.toggle('is-recording', isRecording);
    if (els.voiceRecordBar) els.voiceRecordBar.classList.toggle('d-none', !isRecording);
    if (els.composerMain) els.composerMain.classList.toggle('d-none', isRecording);
    if (els.micBtn) els.micBtn.classList.toggle('recording', isRecording);
    if (els.voiceSlideHint) {
      els.voiceSlideHint.textContent = isRecording ? 'Recording... tap send' : 'Recording...';
    }
    if (!isRecording) {
      voice.locked = false;
      voice.holding = false;
      voice.slideCancel = false;
      voice.starting = false;
    }
    updateComposerAction();
  }

  function stopVoiceTracks() {
    try {
      if (voice.processor) {
        voice.processor.onaudioprocess = null;
        voice.processor.disconnect();
      }
    } catch (_) {}
    try {
      if (voice.sourceNode) voice.sourceNode.disconnect();
    } catch (_) {}
    try {
      if (voice.audioContext && voice.audioContext.state !== 'closed') {
        voice.audioContext.close();
      }
    } catch (_) {}
    voice.processor = null;
    voice.sourceNode = null;
    voice.audioContext = null;

    if (voice.stream) {
      voice.stream.getTracks().forEach((track) => track.stop());
      voice.stream = null;
    }
  }

  function clearVoiceTimer() {
    if (voice.timerId) {
      clearInterval(voice.timerId);
      voice.timerId = null;
    }
  }

  function encodeWav(pcmChunks, sampleRate) {
    let total = 0;
    pcmChunks.forEach((c) => { total += c.length; });
    const merged = new Float32Array(total);
    let offset = 0;
    pcmChunks.forEach((c) => {
      merged.set(c, offset);
      offset += c.length;
    });

    // Downsample to 16 kHz for smaller WhatsApp-like voice notes.
    const targetRate = 16000;
    const ratio = sampleRate / targetRate;
    const outLen = Math.max(1, Math.floor(merged.length / ratio));
    const pcm = new Float32Array(outLen);
    for (let i = 0; i < outLen; i += 1) {
      pcm[i] = merged[Math.min(merged.length - 1, Math.floor(i * ratio))] || 0;
    }

    const buffer = new ArrayBuffer(44 + pcm.length * 2);
    const view = new DataView(buffer);
    const writeStr = (pos, str) => {
      for (let i = 0; i < str.length; i += 1) view.setUint8(pos + i, str.charCodeAt(i));
    };

    writeStr(0, 'RIFF');
    view.setUint32(4, 36 + pcm.length * 2, true);
    writeStr(8, 'WAVE');
    writeStr(12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true);
    view.setUint16(22, 1, true);
    view.setUint32(24, targetRate, true);
    view.setUint32(28, targetRate * 2, true);
    view.setUint16(32, 2, true);
    view.setUint16(34, 16, true);
    writeStr(36, 'data');
    view.setUint32(40, pcm.length * 2, true);

    let idx = 44;
    for (let i = 0; i < pcm.length; i += 1) {
      const s = Math.max(-1, Math.min(1, pcm[i]));
      view.setInt16(idx, s < 0 ? s * 0x8000 : s * 0x7fff, true);
      idx += 2;
    }
    return new Blob([buffer], { type: 'audio/wav' });
  }

  async function startVoiceRecording() {
    if (voice.recording || voice.starting || !state.activeConversationId) return false;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !(window.AudioContext || window.webkitAudioContext)) {
      alert('Voice messages are not supported in this browser.');
      return false;
    }

    voice.starting = true;
    voice.locked = true;
    voice.holding = false;
    voice.slideCancel = false;
    voice.abortBeforeStart = false;

    try {
      voice.stream = await navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
          channelCount: 1,
        },
      });
    } catch (err) {
      voice.starting = false;
      voice.locked = false;
      const name = err && err.name ? err.name : '';
      if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
        alert('Microphone blocked. Allow mic access for localhost in your browser settings, then try again.');
      } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
        alert('No microphone found. Plug in a mic and try again.');
      } else {
        alert('Could not access microphone. Check browser permissions and try again.');
      }
      return false;
    }

    if (voice.abortBeforeStart) {
      voice.abortBeforeStart = false;
      voice.starting = false;
      voice.locked = false;
      stopVoiceTracks();
      return false;
    }

    voice.pcmChunks = [];
    voice.peakLevel = 0;
    voice.cancelled = false;
    voice.duration = 0;
    voice.startedAt = Date.now();
    voice.mimeType = 'audio/wav';

    try {
      const AC = window.AudioContext || window.webkitAudioContext;
      voice.audioContext = new AC();
      if (voice.audioContext.state === 'suspended') {
        await voice.audioContext.resume();
      }
      voice.sampleRate = voice.audioContext.sampleRate || 16000;
      voice.sourceNode = voice.audioContext.createMediaStreamSource(voice.stream);
      voice.processor = voice.audioContext.createScriptProcessor(4096, 1, 1);
      voice.processor.onaudioprocess = (e) => {
        if (!voice.recording) return;
        const input = e.inputBuffer.getChannelData(0);
        const copy = new Float32Array(input.length);
        copy.set(input);
        voice.pcmChunks.push(copy);
        let peak = 0;
        for (let i = 0; i < input.length; i += 1) {
          const v = Math.abs(input[i]);
          if (v > peak) peak = v;
        }
        if (peak > voice.peakLevel) voice.peakLevel = peak;
      };
      voice.sourceNode.connect(voice.processor);
      const mute = voice.audioContext.createGain();
      mute.gain.value = 0;
      voice.processor.connect(mute);
      mute.connect(voice.audioContext.destination);
    } catch (_) {
      voice.starting = false;
      stopVoiceTracks();
      alert('Could not start voice recorder.');
      return false;
    }

    voice.recording = true;
    voice.starting = false;
    setVoiceUiRecording(true);
    if (els.voiceTimer) els.voiceTimer.textContent = '0:00';

    clearVoiceTimer();
    voice.timerId = setInterval(() => {
      voice.duration = (Date.now() - voice.startedAt) / 1000;
      if (els.voiceTimer) els.voiceTimer.textContent = formatDuration(voice.duration);
      if (voice.duration >= voice.maxSeconds) {
        stopVoiceRecording({ send: true });
      }
    }, 200);
    return true;
  }

  async function stopVoiceRecording({ send = false, cancel = false } = {}) {
    if (!voice.recording) {
      if (voice.starting) voice.abortBeforeStart = true;
      return;
    }
    voice.cancelled = !!cancel;
    voice.recording = false;
    voice._sendOnStop = !!send && !cancel;
    voice.duration = Math.max(voice.duration, (Date.now() - voice.startedAt) / 1000);
    clearVoiceTimer();
    setVoiceUiRecording(false);

    const shouldSend = !!voice._sendOnStop && !voice.cancelled;
    const pcm = voice.pcmChunks.slice();
    const sampleRate = voice.sampleRate || 16000;
    const duration = Number(voice.duration) || 0;
    voice.pcmChunks = [];
    voice._sendOnStop = false;
    stopVoiceTracks();

    if (!shouldSend) return;
    if (duration < 0.4) {
      alert('Recording is too short. Tap the mic, speak, then tap send.');
      return;
    }
    if (!pcm.length) {
      alert('No audio was captured. Allow microphone access and try again.');
      return;
    }

    const blob = encodeWav(pcm, sampleRate);
    if (!blob.size || blob.size < 1000) {
      alert('Recorded audio was empty. Please try again.');
      return;
    }

    const file = new File([blob], `voice-${Date.now()}.wav`, { type: 'audio/wav' });
    await sendVoiceMessage(file, duration);
  }

  async function sendVoiceMessage(file, duration) {
    if (!state.activeConversationId) return;

    const formData = new FormData();
    formData.append('conversation_id', String(state.activeConversationId));
    formData.append('body', '');
    formData.append('attachment', file);
    formData.append('duration_seconds', String(Math.round(duration * 10) / 10));

    try {
      const res = await fetch(`${BASE}/api/messages`, {
        method: 'POST',
        body: formData,
      });
      const data = await res.json();
      if (!res.ok || data.ok === false) {
        throw new Error(data.error || 'Could not send voice message');
      }
      appendMessages([data.message]);
      await loadConversations();
    } catch (err) {
      alert(err.message);
    }
  }

  function pauseActiveAudio(exceptEl) {
    if (activeAudio && activeAudio !== exceptEl) {
      activeAudio.pause();
      const wrap = activeAudio.closest('.voice-message');
      if (wrap) syncVoicePlayerUi(wrap);
    }
  }

  async function prepareVoiceAudio(audio) {
    if (!audio) return false;
    if (audio.dataset.blobReady === '1') return true;

    const src = audio.getAttribute('data-src') || audio.src;
    if (!src) return false;

    try {
      const res = await fetch(src, { credentials: 'same-origin', cache: 'no-store' });
      if (!res.ok) throw new Error('Could not load voice file');
      const blob = await res.blob();
      if (!blob.size) throw new Error('Voice file is empty');

      // Prefer a clean audio blob type for Chrome demuxing.
      const typed = blob.type && blob.type.startsWith('audio/')
        ? blob
        : new Blob([blob], { type: 'audio/webm' });
      const objectUrl = URL.createObjectURL(typed);
      if (audio.dataset.objectUrl) {
        URL.revokeObjectURL(audio.dataset.objectUrl);
      }
      audio.dataset.objectUrl = objectUrl;
      audio.src = objectUrl;
      audio.dataset.blobReady = '1';
      audio.load();

      await new Promise((resolve) => {
        const done = () => resolve();
        audio.addEventListener('loadedmetadata', done, { once: true });
        audio.addEventListener('canplay', done, { once: true });
        audio.addEventListener('error', done, { once: true });
        setTimeout(done, 2500);
      });
      return !(audio.error && audio.error.code);
    } catch (_) {
      return false;
    }
  }

  function syncVoicePlayerUi(wrap) {
    if (!wrap) return;
    const audio = wrap.querySelector('audio');
    if (!audio) return;
    const playBtn = wrap.querySelector('[data-voice-play] i');
    const timeEl = wrap.querySelector('[data-voice-time]');
    const speedBtn = wrap.querySelector('[data-voice-speed]');
    const bars = wrap.querySelectorAll('[data-voice-wave] span');
    const stored = Number(wrap.dataset.duration) || 0;
    const duration = Number.isFinite(audio.duration) && audio.duration > 0 ? audio.duration : stored;
    const atStart = audio.paused && audio.currentTime < 0.05;
    const current = atStart ? duration : audio.currentTime;
    const progress = duration > 0 ? Math.min(1, audio.currentTime / duration) : 0;

    if (playBtn) {
      playBtn.className = audio.paused ? 'bi bi-play-fill' : 'bi bi-pause-fill';
    }
    if (timeEl) {
      timeEl.textContent = formatDuration(current);
    }
    if (speedBtn) {
      const showSpeed = !audio.paused || audio.currentTime > 0.05;
      speedBtn.classList.toggle('d-none', !showSpeed);
    }
    bars.forEach((bar, index) => {
      const threshold = (index + 1) / Math.max(bars.length, 1);
      bar.classList.toggle('played', progress >= threshold);
    });
  }

  function bindVoicePlayers(root) {
    const scope = root || els.messages;
    if (!scope) return;

    scope.querySelectorAll('.voice-message').forEach((wrap) => {
      if (wrap.dataset.bound === '1') return;
      wrap.dataset.bound = '1';

      const audio = wrap.querySelector('audio');
      const playBtn = wrap.querySelector('[data-voice-play]');
      const speedBtn = wrap.querySelector('[data-voice-speed]');
      if (!audio || !playBtn) return;

      if (!audio.getAttribute('data-src')) {
        audio.setAttribute('data-src', audio.getAttribute('src') || '');
      }

      audio.addEventListener('loadedmetadata', () => {
        if (Number.isFinite(audio.duration) && audio.duration > 0) {
          wrap.dataset.duration = String(audio.duration);
        }
        syncVoicePlayerUi(wrap);
      });
      audio.addEventListener('timeupdate', () => syncVoicePlayerUi(wrap));
      audio.addEventListener('ended', () => {
        audio.currentTime = 0;
        syncVoicePlayerUi(wrap);
      });
      audio.addEventListener('play', () => {
        activeAudio = audio;
        pauseActiveAudio(audio);
        syncVoicePlayerUi(wrap);
      });
      audio.addEventListener('pause', () => syncVoicePlayerUi(wrap));

      playBtn.addEventListener('click', async () => {
        try {
          if (!audio.paused) {
            audio.pause();
            return;
          }
          playBtn.disabled = true;
          const ok = await prepareVoiceAudio(audio);
          playBtn.disabled = false;
          if (!ok) {
            alert('This voice message cannot be played. Please record a new one.');
            return;
          }
          pauseActiveAudio(audio);
          audio.volume = 1;
          audio.muted = false;
          await audio.play();
          if (wrap.dataset.isMine !== '1') {
            markVoicePlayed(wrap.dataset.voiceId);
          }
        } catch (err) {
          playBtn.disabled = false;
          alert(err && err.message ? err.message : 'Could not play this voice message.');
        }
      });

      if (speedBtn) {
        const rates = [1, 1.5, 2];
        let idx = 0;
        speedBtn.addEventListener('click', () => {
          idx = (idx + 1) % rates.length;
          audio.playbackRate = rates[idx];
          speedBtn.textContent = `${rates[idx]}x`;
        });
      }

      const wave = wrap.querySelector('[data-voice-wave]');
      if (wave) {
        wave.addEventListener('click', async (e) => {
          const ok = await prepareVoiceAudio(audio);
          if (!ok) return;
          const stored = Number(wrap.dataset.duration) || 0;
          const duration = Number.isFinite(audio.duration) && audio.duration > 0 ? audio.duration : stored;
          if (!duration) return;
          const rect = wave.getBoundingClientRect();
          const ratio = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
          audio.currentTime = duration * ratio;
          syncVoicePlayerUi(wrap);
        });
      }

      syncVoicePlayerUi(wrap);
    });
  }

  async function sendLocationMessage() {
    if (!state.activeConversationId) {
      alert('Select a conversation first.');
      return;
    }
    if (!navigator.geolocation) {
      alert('Location is not supported in this browser.');
      return;
    }

    if (els.locationBtn) {
      els.locationBtn.disabled = true;
    }

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        try {
          const data = await api(`${BASE}/api/messages`, {
            method: 'POST',
            body: JSON.stringify({
              conversation_id: state.activeConversationId,
              body: '',
              location_lat: pos.coords.latitude,
              location_lng: pos.coords.longitude,
            }),
          });
          appendMessages([data.message]);
          await loadConversations();
        } catch (err) {
          alert(err.message);
        } finally {
          if (els.locationBtn) els.locationBtn.disabled = false;
        }
      },
      (err) => {
        let msg = 'Could not get your location.';
        if (err && err.code === 1) msg = 'Location permission denied.';
        if (err && err.code === 2) msg = 'Location unavailable.';
        if (err && err.code === 3) msg = 'Location request timed out.';
        alert(msg);
        if (els.locationBtn) els.locationBtn.disabled = false;
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0,
      }
    );
  }

  function renderDeleteIcon(m) {
    const showEveryone = !!(m && m.is_mine && m.can_delete_everyone && !m.deleted_everyone);
    return `
      <div class="msg-delete-menu">
        <button type="button" class="msg-delete-icon" data-msg-delete title="More" aria-label="Message options" aria-haspopup="true">
          <i class="bi bi-chevron-down"></i>
        </button>
        <div class="msg-delete-dropdown d-none" data-msg-dropdown role="menu">
          ${showEveryone ? `
            <button type="button" class="msg-delete-item" data-delete-scope="everyone" role="menuitem">Delete for everyone</button>
          ` : ''}
          <button type="button" class="msg-delete-item" data-delete-scope="me" role="menuitem">Delete for me</button>
        </div>
      </div>
    `;
  }

  function renderDeletedBubble(m) {
    return `
      <div class="bubble">
        <div class="bubble-deleted">
          <i class="bi bi-slash-circle"></i>
          <span>This message was deleted</span>
        </div>
        ${renderMeta(m)}
      </div>
    `;
  }

  function appendMessages(messages, { replace = false } = {}) {
    if (replace) {
      els.messages.innerHTML = '';
      state.lastMessageId = 0;
    }

    if (!messages.length) return;

    const frag = document.createDocumentFragment();
    messages.forEach((m) => {
      const row = document.createElement('div');
      row.className = `bubble-row ${m.is_mine ? 'mine' : 'theirs'}`;
      row.dataset.messageId = String(m.id);
      row.dataset.status = receiptStatus(m);
      row.dataset.isRead = m.is_read ? '1' : '0';
      row.dataset.isDelivered = m.is_delivered ? '1' : '0';
      row.dataset.isPlayed = m.is_played ? '1' : '0';
      row.dataset.isMine = m.is_mine ? '1' : '0';
      row.dataset.canDeleteEveryone = m.can_delete_everyone ? '1' : '0';
      row.dataset.createdAt = m.created_at || '';
      row.dataset.deletedEveryone = m.deleted_everyone ? '1' : '0';

      if (m.deleted_everyone) {
        row.innerHTML = renderDeletedBubble(m);
      } else {
        const isVoiceOnly = !!(m.is_audio && (!m.body || m.body === ''));
        const textHtml = m.body ? `<div>${escapeHtml(m.body)}</div>` : '';
        const timeHtml = isVoiceOnly ? '' : renderMeta(m);
        row.innerHTML = `
          <div class="bubble-wrap">
            <div class="bubble has-msg-menu${isVoiceOnly ? ' bubble-voice' : ''}">
              ${textHtml}
              ${renderAttachment(m)}
              ${timeHtml}
            </div>
            ${renderDeleteIcon(m)}
          </div>
        `;
      }
      frag.appendChild(row);
      state.lastMessageId = Math.max(state.lastMessageId, m.id);
    });
    els.messages.appendChild(frag);
    els.messages.scrollTop = els.messages.scrollHeight;
    bindVoicePlayers(els.messages);
  }

  function applyDeletedEveryone(deleted) {
    if (!Array.isArray(deleted) || !deleted.length || !els.messages) return;
    deleted.forEach((item) => {
      const row = els.messages.querySelector(`[data-message-id="${item.id}"]`);
      if (!row || row.dataset.deletedEveryone === '1') return;
      markMessageDeleted(row, null);
    });
  }

  function closeMessageActions() {
    selectedMessage = null;
    if (!els.messages) return;
    els.messages.querySelectorAll('.bubble-row.is-selected').forEach((el) => {
      el.classList.remove('is-selected');
    });
    els.messages.querySelectorAll('[data-msg-dropdown]').forEach((menu) => {
      menu.classList.add('d-none');
    });
  }

  function openMessageActions(row) {
    if (!row) return;
    closeMessageActions();
    selectedMessage = {
      id: Number(row.dataset.messageId),
      isMine: row.dataset.isMine === '1',
      canDeleteEveryone: row.dataset.canDeleteEveryone === '1',
      deletedEveryone: row.dataset.deletedEveryone === '1',
      el: row,
    };
    row.classList.add('is-selected');
    const menu = row.querySelector('[data-msg-dropdown]');
    if (menu) menu.classList.remove('d-none');
  }

  function markMessageDeleted(row, message) {
    if (!row) return;
    row.dataset.deletedEveryone = '1';
    row.dataset.canDeleteEveryone = '0';
    const payload = message || {
      is_mine: row.dataset.isMine === '1',
      is_read: row.dataset.isRead === '1',
      is_delivered: row.dataset.isDelivered === '1',
      status: row.dataset.status || 'sent',
      created_at: row.dataset.createdAt || '',
    };
    row.innerHTML = renderDeletedBubble(payload);
  }

  async function deleteSelectedMessage(scope) {
    if (!selectedMessage || !selectedMessage.id) return;
    const messageId = selectedMessage.id;
    const row = selectedMessage.el;
    const deleteScope = scope || (selectedMessage.canDeleteEveryone ? 'everyone' : 'me');
    closeMessageActions();

    try {
      const data = await api(`${BASE}/api/message-delete`, {
        method: 'POST',
        body: JSON.stringify({ message_id: messageId, scope: deleteScope }),
      });

      if (data.scope === 'everyone') {
        markMessageDeleted(row, data.message || null);
      } else if (data.scope === 'me') {
        if (row && row.parentNode) row.remove();
      }
      await loadConversations();
    } catch (err) {
      alert(err.message || 'Could not delete message');
    }
  }

  function clearSelectedFile() {
    selectedFile = null;
    if (els.attachInput) els.attachInput.value = '';
    if (els.attachPreview) els.attachPreview.classList.add('d-none');
    if (els.attachName) els.attachName.textContent = 'file';
    updateComposerAction();
  }

  function isAllowedAttachment(file) {
    const name = (file.name || '').toLowerCase();
    const extOk = /\.(jpe?g|png|gif|webp|pdf)$/i.test(name);
    const type = (file.type || '').toLowerCase();
    const typeOk = type.startsWith('image/') || type === 'application/pdf' || type === '';
    return extOk && typeOk;
  }

  function setSelectedFile(file) {
    if (!file) {
      clearSelectedFile();
      return;
    }
    if (!isAllowedAttachment(file)) {
      alert('Only images and PDF files are allowed.');
      clearSelectedFile();
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      alert('File must be 5 MB or smaller.');
      clearSelectedFile();
      return;
    }
    selectedFile = file;
    if (els.attachName) els.attachName.textContent = `${file.name} (${formatBytes(file.size)})`;
    if (els.attachPreview) els.attachPreview.classList.remove('d-none');
    updateComposerAction();
  }

  async function loadConversations() {
    const data = await api(`${BASE}/api/conversations`);
    state.conversations = data.conversations;
    state.users = data.users;
    state.me = data.me || state.me;
    renderLists(els.userSearch.value);

    if (state.activeConversationId && state.activePeer) {
      const updated = state.conversations.find((c) => c.conversation_id === state.activeConversationId);
      if (updated) {
        state.activePeer.online = updated.online;
        state.activePeer.last_seen = updated.last_seen || state.activePeer.last_seen;
        if (!els.typingIndicator || els.typingIndicator.classList.contains('d-none')) {
          els.peerStatus.innerHTML = defaultPeerStatusHtml();
        }
        els.peerAvatar.classList.toggle('online', !!updated.online);
      }
    }
  }

  async function openConversation(conversationId, peer) {
    if (state.activeConversationId && state.activeConversationId !== conversationId) {
      await sendTypingStatus('idle');
    }

    state.activeConversationId = conversationId;
    showThread(peer);
    renderLists(els.userSearch.value);

    const data = await api(`${BASE}/api/messages?conversation_id=${conversationId}`);
    appendMessages(data.messages, { replace: true });
    applyReceipts(data.receipts || []);
    applyDeletedEveryone(data.deleted || []);
    await loadConversations();
  }

  async function startChatWithUser(userId) {
    const data = await api(`${BASE}/api/conversations`, {
      method: 'POST',
      body: JSON.stringify({ user_id: userId }),
    });

    const peer = state.users.find((u) => u.id === userId);
    if (!peer) return;
    await openConversation(data.conversation_id, {
      display_name: peer.display_name,
      avatar_color: peer.avatar_color,
      avatar_url: peer.avatar_url || null,
      initials: peer.initials,
      online: peer.online,
      last_seen: peer.last_seen || null,
      username: peer.username,
    });
  }

  async function pollMessages() {
    if (!state.activeConversationId) return;
    try {
      const data = await api(
        `${BASE}/api/messages?conversation_id=${state.activeConversationId}&after_id=${state.lastMessageId}`
      );
      if (data.messages.length) {
        appendMessages(data.messages);
        setPeerActivity('idle');
        await loadConversations();
      }
      applyReceipts(data.receipts || []);
      applyDeletedEveryone(data.deleted || []);
    } catch (_) {
      // ignore transient poll errors
    }
  }

  async function pollTyping() {
    if (!state.activeConversationId) return;
    try {
      const data = await api(
        `${BASE}/api/typing?conversation_id=${state.activeConversationId}`
      );
      setPeerActivity(data.typing && data.typing.status ? data.typing.status : 'idle');
    } catch (_) {
      // ignore transient poll errors
    }
  }

  els.conversationList.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-conversation-id]');
    if (!btn) return;
    const id = Number(btn.dataset.conversationId);
    const conv = state.conversations.find((c) => c.conversation_id === id);
    if (!conv) return;
    await openConversation(id, {
      display_name: conv.display_name,
      avatar_color: conv.avatar_color,
      avatar_url: conv.avatar_url || null,
      initials: conv.initials,
      online: conv.online,
      last_seen: conv.last_seen || null,
      username: conv.username,
    });
  });

  els.peopleList.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-user-id]');
    if (!btn) return;
    await startChatWithUser(Number(btn.dataset.userId));
  });

  els.userSearch.addEventListener('input', () => {
    renderLists(els.userSearch.value);
  });

  els.messageInput.addEventListener('input', handleComposerInput);
  els.messageInput.addEventListener('keyup', updateComposerAction);
  els.messageInput.addEventListener('change', updateComposerAction);
  els.messageInput.addEventListener('paste', () => setTimeout(updateComposerAction, 0));

  function buildEmojiPicker() {
    if (!els.emojiGrid) return;
    els.emojiGrid.innerHTML = EMOJIS.map(
      (emoji) => `<button type="button" class="emoji-btn" data-emoji="${emoji}">${emoji}</button>`
    ).join('');
  }

  function toggleEmojiPanel(force) {
    if (!els.emojiPanel) return;
    const shouldOpen = typeof force === 'boolean'
      ? force
      : els.emojiPanel.classList.contains('d-none');
    els.emojiPanel.classList.toggle('d-none', !shouldOpen);
  }

  function insertEmoji(emoji) {
    const input = els.messageInput;
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    const before = input.value.slice(0, start);
    const after = input.value.slice(end);
    const next = before + emoji + after;

    if (next.length > 2000) return;

    input.value = next;
    const caret = start + emoji.length;
    input.focus();
    input.setSelectionRange(caret, caret);
    handleComposerInput();
  }

  if (els.emojiBtn && els.emojiPanel && els.emojiGrid) {
    buildEmojiPicker();

    els.emojiBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleEmojiPanel();
    });

    els.emojiGrid.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-emoji]');
      if (!btn) return;
      insertEmoji(btn.dataset.emoji);
    });

    document.addEventListener('click', (e) => {
      if (!els.emojiPanel.classList.contains('d-none')) {
        if (!e.target.closest('.emoji-wrap')) {
          toggleEmojiPanel(false);
        }
      }
    });
  }

  if (els.attachInput) {
    els.attachInput.addEventListener('change', () => {
      const file = els.attachInput.files && els.attachInput.files[0] ? els.attachInput.files[0] : null;
      setSelectedFile(file);
    });
  }

  if (els.attachClear) {
    els.attachClear.addEventListener('click', () => clearSelectedFile());
  }

  if (els.locationBtn) {
    els.locationBtn.addEventListener('click', (e) => {
      e.preventDefault();
      sendLocationMessage();
    });
  }

  if (els.messages) {
    const clearLongPress = () => {
      if (longPressTimer) {
        clearTimeout(longPressTimer);
        longPressTimer = null;
      }
    };

    els.messages.addEventListener('click', (e) => {
      const scopeBtn = e.target.closest('[data-delete-scope]');
      if (scopeBtn) {
        e.preventDefault();
        e.stopPropagation();
        const row = scopeBtn.closest('.bubble-row');
        if (row) {
          selectedMessage = {
            id: Number(row.dataset.messageId),
            isMine: row.dataset.isMine === '1',
            canDeleteEveryone: row.dataset.canDeleteEveryone === '1',
            deletedEveryone: row.dataset.deletedEveryone === '1',
            el: row,
          };
        }
        deleteSelectedMessage(scopeBtn.dataset.deleteScope || 'me');
        return;
      }

      const delBtn = e.target.closest('[data-msg-delete]');
      if (delBtn) {
        e.preventDefault();
        e.stopPropagation();
        const row = delBtn.closest('.bubble-row');
        if (!row || row.dataset.deletedEveryone === '1') return;
        const menu = row.querySelector('[data-msg-dropdown]');
        const isOpen = menu && !menu.classList.contains('d-none');
        closeMessageActions();
        if (!isOpen) openMessageActions(row);
        return;
      }

      if (!e.target.closest('.msg-delete-menu')) {
        closeMessageActions();
      }
    });

    els.messages.addEventListener('contextmenu', (e) => {
      const row = e.target.closest('.bubble-row');
      if (!row || row.dataset.deletedEveryone === '1') return;
      e.preventDefault();
      openMessageActions(row);
    });

    els.messages.addEventListener('pointerdown', (e) => {
      const row = e.target.closest('.bubble-row');
      if (!row || row.dataset.deletedEveryone === '1') return;
      if (e.target.closest('a, button, audio, input, .voice-wave, .msg-delete-menu')) return;
      clearLongPress();
      longPressTimer = setTimeout(() => {
        openMessageActions(row);
      }, 480);
    });

    els.messages.addEventListener('pointerup', clearLongPress);
    els.messages.addEventListener('pointercancel', clearLongPress);
    els.messages.addEventListener('pointerleave', clearLongPress);
    els.messages.addEventListener('pointermove', (e) => {
      if (!longPressTimer) return;
      if (Math.abs(e.movementX) + Math.abs(e.movementY) > 8) clearLongPress();
    });
  }

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.msg-delete-menu')) {
      closeMessageActions();
    }
  });

  if (els.micBtn) {
    els.micBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (!state.activeConversationId) {
        alert('Select a conversation first.');
        return;
      }
      if (voice.recording || voice.starting) return;
      toggleEmojiPanel(false);
      els.micBtn.disabled = true;
      const started = await startVoiceRecording();
      els.micBtn.disabled = false;
      if (!started) updateComposerAction();
    });
  }

  if (els.voiceCancelBtn) {
    els.voiceCancelBtn.addEventListener('click', (e) => {
      e.preventDefault();
      stopVoiceRecording({ cancel: true });
    });
  }

  if (els.voiceSendBtn) {
    els.voiceSendBtn.addEventListener('click', (e) => {
      e.preventDefault();
      stopVoiceRecording({ send: true });
    });
  }

  els.composer.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = els.messageInput.value.trim();
    if ((!body && !selectedFile) || !state.activeConversationId) return;

    const formData = new FormData();
    formData.append('conversation_id', String(state.activeConversationId));
    formData.append('body', body);
    if (selectedFile) {
      formData.append('attachment', selectedFile);
    }

    els.messageInput.value = '';
    state.lastInputValue = '';
    clearSelectedFile();
    updateComposerAction();
    toggleEmojiPanel(false);
    await sendTypingStatus('idle');

    try {
      const res = await fetch(`${BASE}/api/messages`, {
        method: 'POST',
        body: formData,
      });
      const data = await res.json();
      if (!res.ok || data.ok === false) {
        throw new Error(data.error || 'Upload failed');
      }
      appendMessages([data.message]);
      await loadConversations();
    } catch (err) {
      alert(err.message);
    }
  });

  els.backToList.addEventListener('click', () => {
    closeChatMenu();
    els.sidebar.classList.add('show-mobile');
    els.chatMain.classList.add('hide-mobile');
  });

  if (els.chatMenuBtn) {
    els.chatMenuBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleChatMenu();
    });
  }

  if (els.chatMenuDropdown) {
    els.chatMenuDropdown.addEventListener('click', async (e) => {
      const actionBtn = e.target.closest('[data-chat-action]');
      if (!actionBtn) return;
      e.preventDefault();
      e.stopPropagation();
      const action = actionBtn.dataset.chatAction;
      if (action === 'clear') await clearActiveChat();
      if (action === 'delete') await deleteActiveChat();
    });
  }

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.chat-menu')) closeChatMenu();
  });

  window.addEventListener('beforeunload', () => {
    if (!state.activeConversationId || state.lastTypingStatus === 'idle') return;
    if (navigator.sendBeacon) {
      const blob = new Blob(
        [JSON.stringify({ conversation_id: state.activeConversationId, status: 'idle' })],
        { type: 'application/json' }
      );
      navigator.sendBeacon(`${BASE}/api/typing`, blob);
    }
  });

  async function boot() {
    updateComposerAction();
    await loadConversations();
    if (window.innerWidth < 992) {
      els.sidebar.classList.add('show-mobile');
      els.chatMain.classList.add('hide-mobile');
    }

    const startId = Number(new URLSearchParams(window.location.search).get('start') || 0);
    if (startId > 0) {
      await startChatWithUser(startId);
      window.history.replaceState({}, '', `${BASE}/chat`);
    }

    state.pollTimer = setInterval(async () => {
      await loadConversations();
      await pollMessages();
      await pollTyping();
    }, 1200);
  }

  boot().catch((err) => {
    console.error(err);
    alert('Could not load chat. Please refresh.');
  });
})();
