<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat</title>
  <link rel="icon" href="<?= base_url('assets/images/favicon.svg') ?>" type="image/svg+xml">
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="chat-app" data-base="<?= rtrim(site_url(), '/') ?>" data-user-type="<?= is_system_user($user) ? 'system' : 'normal' ?>">
  <div class="chat-shell">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="d-flex align-items-center gap-3">
          <?= render_user_avatar($user) ?>
          <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold text-truncate"><?= html_escape($user['display_name']) ?></div>
            <div class="small text-secondary text-truncate">
              <?php if (is_system_user($user)): ?>
                @<?= html_escape($user['username']) ?> · system
              <?php else: ?>
                normal
              <?php endif; ?>
            </div>
          </div>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                    id="homeMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
              Menu
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="homeMenuBtn">
              <li>
                <a class="dropdown-item" href="<?= site_url('profile') ?>">
                  <i class="bi bi-person-circle me-2"></i>Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="<?= site_url('friends') ?>">
                  <i class="bi bi-person-hearts me-2"></i>Friends
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="<?= site_url('users') ?>">
                  <i class="bi bi-people me-2"></i>Users
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="<?= site_url('users/add') ?>">
                  <i class="bi bi-person-plus me-2"></i>Add user
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="<?= site_url('logout') ?>">
                  <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
        <div class="mt-3">
          <input type="search" class="form-control" id="userSearch"
                 placeholder="<?= is_system_user($user) ? 'Search all users...' : 'Search friends...' ?>">
        </div>
      </div>

      <div class="sidebar-section-title">Conversations</div>
      <div class="list-group list-group-flush conversation-list" id="conversationList"></div>

      <div class="sidebar-section-title px-3">
        <?= is_system_user($user) ? 'All users' : 'Friends' ?>
      </div>
      <div class="list-group list-group-flush people-list" id="peopleList"></div>
    </aside>

    <main class="chat-main">
      <div class="empty-state" id="emptyState">
        <img class="empty-image mb-3" src="<?= base_url('assets/images/chat-empty.svg') ?>" alt="" width="96" height="96">
        <h2 class="h4">Select a conversation</h2>
        <p class="text-secondary mb-0">Pick someone from the sidebar to start chatting.</p>
      </div>

      <div class="thread d-none" id="thread">
        <header class="thread-header">
          <button class="btn btn-link d-lg-none ps-0" id="backToList" type="button">
            <i class="bi bi-arrow-left"></i>
          </button>
          <div class="avatar" id="peerAvatar">?</div>
          <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold text-truncate" id="peerName">—</div>
            <div class="small" id="peerStatus">
              <span class="status-pill status-offline"><i class="bi bi-circle"></i> Offline</span>
            </div>
          </div>
          <div class="chat-menu">
            <button type="button" class="chat-menu-btn" id="chatMenuBtn" title="Chat options" aria-label="Chat options" aria-haspopup="true" aria-expanded="false">
              <i class="bi bi-three-dots-vertical"></i>
            </button>
            <div class="chat-menu-dropdown d-none" id="chatMenuDropdown" role="menu">
              <button type="button" class="chat-menu-item" data-chat-action="clear" role="menuitem">Clear chat</button>
              <button type="button" class="chat-menu-item chat-menu-danger" data-chat-action="delete" role="menuitem">Delete chat</button>
            </div>
          </div>
        </header>

        <div class="messages" id="messages"></div>
        <div class="typing-indicator d-none" id="typingIndicator">
          <span class="typing-dots" aria-hidden="true"><i></i><i></i><i></i></span>
          <span id="typingText">typing...</span>
        </div>

        <div class="attach-preview d-none" id="attachPreview">
          <i class="bi bi-paperclip"></i>
          <span id="attachName">file</span>
          <button type="button" class="btn btn-sm btn-link text-danger p-0" id="attachClear" title="Remove file">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <form class="composer" id="composer">
          <div class="composer-main" id="composerMain">
            <div class="emoji-wrap">
              <button class="btn btn-outline-secondary btn-lg" type="button" id="emojiBtn" title="Emoji">
                <i class="bi bi-emoji-smile"></i>
              </button>
              <div class="emoji-panel d-none" id="emojiPanel" role="dialog" aria-label="Emoji picker">
                <div class="emoji-panel-header">Emoji</div>
                <div class="emoji-grid" id="emojiGrid"></div>
              </div>
            </div>
            <label class="btn btn-outline-secondary btn-lg attach-btn" title="Attach file">
              <i class="bi bi-paperclip"></i>
              <input type="file" id="attachInput" hidden
                     accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,image/*,application/pdf">
            </label>
            <button class="btn btn-outline-secondary btn-lg" type="button" id="locationBtn" title="Send current location">
              <i class="bi bi-geo-alt-fill"></i>
            </button>
            <input type="text" class="form-control form-control-lg" id="messageInput"
                   placeholder="Type a message..." maxlength="2000" autocomplete="off">
            <button class="btn btn-primary btn-lg d-none" type="submit" id="sendBtn" title="Send">
              <i class="bi bi-send-fill"></i>
            </button>
            <button class="btn btn-primary btn-lg mic-btn" type="button" id="micBtn" title="Record voice message">
              <i class="bi bi-mic-fill"></i>
            </button>
          </div>
          <div class="voice-record-bar d-none" id="voiceRecordBar" aria-live="polite">
            <button type="button" class="voice-cancel-btn" id="voiceCancelBtn" title="Delete voice message">
              <i class="bi bi-trash3-fill"></i>
            </button>
            <div class="voice-rec-live">
              <span class="voice-rec-dot" aria-hidden="true"></span>
              <span class="voice-timer" id="voiceTimer">0:00</span>
              <span class="voice-slide-hint" id="voiceSlideHint">Recording...</span>
              <div class="voice-rec-wave" aria-hidden="true">
                <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
              </div>
            </div>
            <button type="button" class="btn btn-primary btn-lg voice-send-btn" id="voiceSendBtn" title="Send voice message">
              <i class="bi bi-send-fill"></i>
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/chat.js') ?>"></script>
</body>
</html>
