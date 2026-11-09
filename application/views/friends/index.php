<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Friends · Chat</title>
  <link rel="icon" href="<?= base_url('assets/images/favicon.svg') ?>" type="image/svg+xml">
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body" data-base="<?= rtrim(site_url(), '/') ?>">
  <div class="container py-4 py-md-5">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <img src="<?= base_url('assets/images/logo.svg') ?>" alt="Chat" width="40" height="40" class="brand-logo">
        <div>
          <h1 class="h3 mb-0 fw-semibold">My friends</h1>
          <p class="text-secondary mb-0 small">Any user can add friends · each account has its own list</p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFriendModal">
          <i class="bi bi-person-plus me-1"></i> Add friend
        </button>
        <a class="btn btn-primary" href="<?= site_url('chat') ?>"><i class="bi bi-chat-dots me-1"></i> Open chat</a>
        <a class="btn btn-outline-secondary" href="<?= site_url('profile') ?>">Profile</a>
        <a class="btn btn-outline-secondary" href="<?= site_url('users') ?>">Users</a>
        <a class="btn btn-outline-secondary" href="<?= site_url('logout') ?>">Logout</a>
      </div>
    </div>

    <?php if ($success === 'sent'): ?>
      <div class="alert alert-success">Friend request sent.</div>
    <?php elseif ($success === 'accepted'): ?>
      <div class="alert alert-success">Friend request accepted.</div>
    <?php elseif ($success === 'removed'): ?>
      <div class="alert alert-success">Request removed.</div>
    <?php elseif ($success === 'unfriended'): ?>
      <div class="alert alert-success">Friend removed.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= html_escape($error) ?></div>
    <?php endif; ?>
    <div id="friendsFlash" class="d-none"></div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card border-0 shadow-lg users-card mb-4">
          <div class="card-header bg-white border-0 pt-3 px-4 d-flex align-items-center justify-content-between">
            <h2 class="h5 mb-0">Friends (<?= count($friends) ?>)</h2>
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addFriendModal">
              <i class="bi bi-person-plus"></i> Add
            </button>
          </div>
          <div class="list-group list-group-flush">
            <?php if (empty($friends)): ?>
              <div class="px-4 py-3 text-secondary">No friends yet. Use <strong>Add friend</strong> to search and invite someone.</div>
            <?php endif; ?>
            <?php foreach ($friends as $u): ?>
              <div class="list-group-item px-4 py-3 d-flex align-items-center gap-3">
                <?= render_user_avatar($u) ?>
                <div class="flex-grow-1 overflow-hidden">
                  <div class="fw-semibold text-truncate"><?= html_escape($u['display_name']) ?></div>
                  <?php if (!empty($is_system)): ?>
                  <div class="small text-secondary">@<?= html_escape($u['username']) ?></div>
                  <?php else: ?>
                  <div class="small text-secondary"><?= !empty($u['online']) ? 'Online' : 'Offline' ?></div>
                  <?php endif; ?>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('chat?start='.$u['id']) ?>">Chat</a>
                <a class="btn btn-sm btn-outline-danger" href="<?= site_url('friends/unfriend/'.$u['id']) ?>" onclick="return confirm('Remove this friend?')">Remove</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card border-0 shadow-lg users-card">
          <div class="card-header bg-white border-0 pt-3 px-4">
            <h2 class="h5 mb-0">Incoming requests (<?= count($incoming) ?>)</h2>
          </div>
          <div class="list-group list-group-flush">
            <?php if (empty($incoming)): ?>
              <div class="px-4 py-3 text-secondary">No pending requests.</div>
            <?php endif; ?>
            <?php foreach ($incoming as $u): ?>
              <div class="list-group-item px-4 py-3 d-flex align-items-center gap-3">
                <?= render_user_avatar($u) ?>
                <div class="flex-grow-1 overflow-hidden">
                  <div class="fw-semibold text-truncate"><?= html_escape($u['display_name']) ?></div>
                  <?php if (!empty($is_system)): ?>
                  <div class="small text-secondary">@<?= html_escape($u['username']) ?></div>
                  <?php else: ?>
                  <div class="small text-secondary">Wants to be friends</div>
                  <?php endif; ?>
                </div>
                <a class="btn btn-sm btn-success" href="<?= site_url('friends/accept/'.$u['friendship_id']) ?>">Accept</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('friends/remove/'.$u['friendship_id']) ?>">Decline</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-lg users-card">
          <div class="card-header bg-white border-0 pt-3 px-4">
            <h2 class="h5 mb-0">Sent requests (<?= count($outgoing) ?>)</h2>
          </div>
          <div class="list-group list-group-flush">
            <?php if (empty($outgoing)): ?>
              <div class="px-4 py-3 text-secondary">No sent requests.</div>
            <?php endif; ?>
            <?php foreach ($outgoing as $u): ?>
              <div class="list-group-item px-4 py-3 d-flex align-items-center gap-3">
                <?= render_user_avatar($u) ?>
                <div class="flex-grow-1 overflow-hidden">
                  <div class="fw-semibold text-truncate"><?= html_escape($u['display_name']) ?></div>
                  <div class="small text-secondary">Waiting for response</div>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('friends/remove/'.$u['friendship_id']) ?>">Cancel</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addFriendModal" tabindex="-1" aria-labelledby="addFriendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg friend-search-modal">
        <div class="modal-header border-0 pb-0">
          <div>
            <h2 class="modal-title h5" id="addFriendModalLabel">Add friend</h2>
            <p class="text-secondary small mb-0">
              <?= !empty($is_system) ? 'Search by name, username, or email' : 'Search by name or email' ?>
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-3">
          <div class="input-group mb-3">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" class="form-control form-control-lg" id="friendSearchInput"
                   placeholder="Type to search users..." autocomplete="off">
          </div>
          <div id="friendSearchStatus" class="small text-secondary mb-2">Type a name to find people.</div>
          <div class="list-group friend-search-results" id="friendSearchResults"></div>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/friends.js') ?>"></script>
</body>
</html>
