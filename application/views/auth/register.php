<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register · Chat</title>
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
          <h1 class="h3 mb-1 fw-semibold">Create account</h1>
          <p class="text-secondary mb-0">Join and start messaging</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2"><?= html_escape($error) ?></div>
        <?php endif; ?>

        <?= form_open('register') ?>
          <div class="mb-3">
            <label class="form-label required" for="display_name">Display name</label>
            <input class="form-control form-control-lg" type="text" id="display_name" name="display_name"
                   value="<?= html_escape($form['display_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label required" for="username">Username</label>
            <input class="form-control form-control-lg" type="text" id="username" name="username"
                   value="<?= html_escape($form['username']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label required" for="email">Email</label>
            <input class="form-control form-control-lg" type="email" id="email" name="email"
                   value="<?= html_escape($form['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label required" for="password">Password</label>
            <input class="form-control form-control-lg" type="password" id="password" name="password" required>
          </div>
          <div class="mb-4">
            <label class="form-label required" for="confirm_password">Confirm password</label>
            <input class="form-control form-control-lg" type="password" id="confirm_password" name="confirm_password" required>
          </div>
          <button class="btn btn-primary btn-lg w-100" type="submit">Create account</button>
        <?= form_close() ?>

        <p class="text-center text-secondary mt-4 mb-0">
          Already have an account?
          <a href="<?= site_url('login') ?>">Sign in</a>
        </p>
      </div>
    </div>
  </div>
  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
