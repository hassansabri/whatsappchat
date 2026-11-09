<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users · Chat</title>
  <link rel="icon" href="<?= base_url('assets/images/favicon.svg') ?>" type="image/svg+xml">
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="container py-4 py-md-5">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <img src="<?= base_url('assets/images/logo.svg') ?>" alt="Chat" width="40" height="40" class="brand-logo">
        <div>
          <h1 class="h3 mb-0 fw-semibold">Users</h1>
          <p class="text-secondary mb-0 small">
            <?php if (!empty($is_system)): ?>
              <?= count($users) ?> accounts · anyone can add · only system chooses type
            <?php else: ?>
              Your friends · you can add new users (always normal)
            <?php endif; ?>
          </p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-success" href="<?= site_url('users/add') ?>">
          <i class="bi bi-person-plus me-1"></i> Add user
        </a>
        <a class="btn btn-outline-primary" href="<?= site_url('friends') ?>">
          <i class="bi bi-person-hearts me-1"></i> Friends
        </a>
        <a class="btn btn-primary" href="<?= site_url('chat') ?>"><i class="bi bi-chat-dots me-1"></i> Open chat</a>
        <a class="btn btn-outline-secondary" href="<?= site_url('logout') ?>">Logout</a>
      </div>
    </div>

    <?php if ($success === 'added'): ?>
      <div class="alert alert-success">User created successfully.</div>
    <?php elseif ($success === 'updated'): ?>
      <div class="alert alert-success">User profile updated successfully.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-lg users-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">User</th>
                <?php if (!empty($is_system)): ?>
                <th scope="col">Username</th>
                <?php endif; ?>
                <th scope="col">Email</th>
                <th scope="col">Phone</th>
                <th scope="col">Gender</th>
                <th scope="col">Type</th>
                <th scope="col">Status</th>
                <th scope="col">Joined</th>
                <th scope="col" class="pe-4 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr><td colspan="<?= !empty($is_system) ? 9 : 8 ?>" class="text-center text-secondary py-4">No users found.</td></tr>
              <?php endif; ?>
              <?php foreach ($users as $u): ?>
                <tr class="<?= $u['id'] === $currentUserId ? 'table-primary' : '' ?>">
                  <td class="ps-4">
                    <div class="d-flex align-items-center gap-3">
                      <?= render_user_avatar($u) ?>
                      <div>
                        <div class="fw-semibold"><?= html_escape($u['display_name']) ?></div>
                        <?php if ($u['id'] === $currentUserId): ?>
                          <span class="badge text-bg-primary">You</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <?php if (!empty($is_system)): ?>
                  <td>@<?= html_escape($u['username']) ?></td>
                  <?php endif; ?>
                  <td><?= html_escape($u['email']) ?></td>
                  <td class="small"><?= html_escape($u['phone_display']) ?></td>
                  <td><?= html_escape($u['gender_name']) ?></td>
                  <td>
                    <?php if ($u['user_type'] === 'system'): ?>
                      <span class="badge text-bg-dark">System</span>
                    <?php else: ?>
                      <span class="badge text-bg-secondary">Normal</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($u['online'])): ?>
                      <span class="status-pill status-online"><i class="bi bi-circle-fill"></i> Online</span>
                    <?php else: ?>
                      <span class="status-pill status-offline"><i class="bi bi-circle"></i> Offline</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-secondary small">
                    <?= !empty($u['created_at']) ? date('Y-m-d', strtotime($u['created_at'])) : '—' ?>
                  </td>
                  <td class="pe-4 text-end">
                    <div class="d-inline-flex gap-2">
                      <?php if (!empty($is_system)): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('users/'.$u['id'].'/edit') ?>">
                          <i class="bi bi-pencil"></i> Edit
                        </a>
                      <?php elseif ($u['id'] === $currentUserId): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('profile') ?>">
                          <i class="bi bi-person-circle"></i> Profile
                        </a>
                      <?php endif; ?>
                      <?php if ($u['id'] !== $currentUserId): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('chat?start='.$u['id']) ?>">
                          <i class="bi bi-chat"></i> Chat
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
