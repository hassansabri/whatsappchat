<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login · Chat</title>
  <link rel="icon" href="<?= base_url('assets/images/favicon.svg') ?>" type="image/svg+xml">
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="auth-card card border-0 shadow-lg w-100">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <img class="brand-logo mb-3" src="<?= base_url('assets/images/logo.svg') ?>" alt="Chat logo" width="56" height="56">
          <h1 class="h3 mb-1 fw-semibold">Welcome back</h1>
          <p class="text-secondary mb-0">Sign in to continue chatting</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2"><?= html_escape($error) ?></div>
        <?php endif; ?>

        <?= form_open('login') ?>
          <div class="mb-3">
            <label class="form-label required" for="username">Username</label>
            <input class="form-control form-control-lg" type="text" id="username" name="username"
                   value="<?= html_escape($username) ?>" required autofocus autocomplete="username">
          </div>
          <div class="mb-4">
            <label class="form-label required" for="password">Password</label>
            <input class="form-control form-control-lg" type="password" id="password" name="password" required>
          </div>
          <button class="btn btn-primary btn-lg w-100" type="submit">Sign in</button>
        <?= form_close() ?>

        <p class="text-center text-secondary mt-4 mb-0">
          No account?
          <a href="<?= site_url('register') ?>">Create one</a>
        </p>
        <p class="text-center small text-secondary mt-3 mb-0">
          Demo: <code>alice</code> / <code>password123</code>
        </p>
      </div>
    </div>
  </div>
  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
