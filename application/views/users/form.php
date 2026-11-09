<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>
    <?= $mode === 'add' ? 'Add user' : (!empty($is_own_profile) ? 'My profile' : 'Edit user') ?> · Chat
  </title>
  <link rel="icon" href="<?= base_url('assets/images/favicon.svg') ?>" type="image/svg+xml">
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <?php
    $letter = avatar_initials(isset($form['display_name']) ? $form['display_name'] : '');
    $preview_url = !empty($form['avatar_url']) ? $form['avatar_url'] : '';
  ?>
  <div class="container py-4 py-md-5 d-flex justify-content-center">
    <div class="w-100" style="max-width: 520px;">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 mb-1 fw-semibold">
            <?= $mode === 'add' ? 'Add user' : (!empty($is_own_profile) ? 'My profile' : 'Edit user') ?>
          </h1>
          <p class="text-secondary mb-0">
            <?php if ($mode === 'add'): ?>
              <?= !empty($can_choose_type) ? 'Create a user and choose their type' : 'Create a normal user account' ?>
            <?php elseif (!empty($is_own_profile)): ?>
              Change display name, avatar image, and password
            <?php else: ?>
              Update full account details
            <?php endif; ?>
          </p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="<?= site_url(!empty($is_own_profile) ? 'chat' : 'users') ?>">Back</a>
      </div>

      <div class="auth-card card border-0 shadow-lg mb-4">
        <div class="card-body p-4 p-md-5">
          <h2 class="h5 fw-semibold mb-3"><?= !empty($is_own_profile) ? 'Profile' : 'Profile details' ?></h2>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= html_escape($error) ?></div>
          <?php endif; ?>
          <?php if (!empty($profile_success) && $profile_success === 'updated'): ?>
            <div class="alert alert-success py-2">Profile updated successfully.</div>
          <?php endif; ?>

          <?= form_open_multipart(
            $mode === 'add' ? 'users/add' : (!empty($is_own_profile) ? 'profile' : 'users/'.$form['id'].'/edit'),
            array(
              'id' => 'userForm',
              'data-check-unique' => site_url('users/check-unique'),
              'data-exclude-id' => ($mode === 'edit' && !empty($form['id'])) ? (int) $form['id'] : 0,
              'data-require-unique' => ($mode === 'add' || !empty($full_edit)) ? '1' : '0',
            )
          ) ?>

            <?php if (!empty($can_change_avatar)): ?>
            <div class="mb-4">
              <label class="form-label">Avatar</label>
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="avatar-preview" id="avatarPreview" style="background:#0d6efd">
                  <?php if ($preview_url): ?>
                    <img src="<?= html_escape($preview_url) ?>" alt="">
                  <?php else: ?>
                    <span id="avatarLetter"><?= html_escape($letter) ?></span>
                  <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                  <input class="form-control" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                  <div class="form-text">Upload an image, or leave empty to use the first letter of the display name.</div>
                  <?php if ($mode === 'edit' && !empty($form['avatar_path'])): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" id="remove_avatar" name="remove_avatar">
                    <label class="form-check-label" for="remove_avatar">Remove current image (use letter)</label>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label required" for="display_name">Display name</label>
              <input class="form-control form-control-lg" type="text" id="display_name" name="display_name"
                     value="<?= html_escape($form['display_name']) ?>" required>
            </div>

            <?php if ($mode === 'add' || !empty($full_edit)): ?>
              <div class="mb-3">
                <label class="form-label required" for="username">Username</label>
                <input class="form-control form-control-lg" type="text" id="username" name="username"
                       value="<?= html_escape($form['username']) ?>" required autocomplete="off">
                <div class="form-text unique-feedback" id="usernameFeedback" aria-live="polite"></div>
              </div>
              <div class="mb-3">
                <label class="form-label required" for="email">Email</label>
                <input class="form-control form-control-lg" type="email" id="email" name="email"
                       value="<?= html_escape($form['email']) ?>" required autocomplete="off">
                <div class="form-text unique-feedback" id="emailFeedback" aria-live="polite"></div>
              </div>
              <div class="mb-3">
                <label class="form-label required" for="phone">Phone number</label>
                <div class="row g-2">
                  <div class="col-5 col-md-4">
                    <select class="form-select form-select-lg" id="country_code" name="country_code" required>
                      <option value="" disabled <?= $form['country_code'] === '' ? 'selected' : '' ?>>Code</option>
                      <?php foreach ($country_codes as $code => $label): ?>
                        <option value="<?= html_escape($code) ?>" <?= $form['country_code'] === $code ? 'selected' : '' ?>>
                          <?= html_escape($label) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-7 col-md-8">
                    <input class="form-control form-control-lg" type="tel" id="phone" name="phone"
                           value="<?= html_escape($form['phone']) ?>" required
                           inputmode="numeric" pattern="[0-9]{6,15}" maxlength="15"
                           placeholder="3001234567" autocomplete="tel-national">
                  </div>
                </div>
                <div class="form-text unique-feedback" id="phoneFeedback" aria-live="polite"></div>
              </div>
              <div class="mb-3">
                <label class="form-label required" for="gender_id">Gender</label>
                <select class="form-select form-select-lg" id="gender_id" name="gender_id" required>
                  <option value="" disabled <?= empty($form['gender_id']) ? 'selected' : '' ?>>Select gender</option>
                  <?php foreach ($genders as $g): ?>
                    <option value="<?= (int) $g['id'] ?>" <?= (string) $form['gender_id'] === (string) $g['id'] ? 'selected' : '' ?>>
                      <?= html_escape($g['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if (!empty($can_choose_type)): ?>
              <div class="mb-3">
                <label class="form-label required" for="user_type">User type</label>
                <select class="form-select form-select-lg" id="user_type" name="user_type" required>
                  <option value="" disabled <?= $form['user_type'] === '' ? 'selected' : '' ?>>Select type</option>
                  <option value="normal" <?= $form['user_type'] === 'normal' ? 'selected' : '' ?>>Normal user</option>
                  <option value="system" <?= $form['user_type'] === 'system' ? 'selected' : '' ?>>System user</option>
                </select>
              </div>
              <?php endif; ?>
            <?php elseif (!empty($is_own_profile)): ?>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input class="form-control form-control-lg" type="text" value="<?= html_escape($form['username']) ?>" readonly disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control form-control-lg" type="email" value="<?= html_escape($form['email']) ?>" readonly disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone</label>
                <input class="form-control form-control-lg" type="text"
                       value="<?= html_escape(format_phone_display($form['country_code'], $form['phone'])) ?>" readonly disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Gender</label>
                <input class="form-control form-control-lg" type="text"
                       value="<?= html_escape(!empty($form['gender_name']) ? $form['gender_name'] : '—') ?>" readonly disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">User type</label>
                <input class="form-control form-control-lg" type="text"
                       value="<?= $form['user_type'] === 'system' ? 'System' : 'Normal' ?>" readonly disabled>
              </div>
            <?php endif; ?>

            <?php if ($mode === 'add'): ?>
            <div class="mb-3">
              <label class="form-label required" for="password">Password</label>
              <input class="form-control form-control-lg" type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="mb-4">
              <label class="form-label required" for="confirm_password">Confirm password</label>
              <input class="form-control form-control-lg" type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <?php else: ?>
            <div class="mb-4"></div>
            <?php endif; ?>

            <button class="btn btn-primary btn-lg w-100" type="submit">
              <?php if ($mode === 'add'): ?>
                Create user
              <?php elseif (!empty($is_own_profile)): ?>
                Save profile
              <?php else: ?>
                Save profile
              <?php endif; ?>
            </button>
          <?= form_close() ?>
        </div>
      </div>

      <?php if ($mode === 'edit' && !empty($can_change_password)): ?>
      <div class="auth-card card border-0 shadow-lg" id="change-password">
        <div class="card-body p-4 p-md-5">
          <h2 class="h5 fw-semibold mb-1">Change password</h2>
          <p class="text-secondary small mb-3">Update only the password here.</p>

          <?php if (!empty($password_success)): ?>
            <div class="alert alert-success py-2">Password updated successfully.</div>
          <?php endif; ?>
          <?php if (!empty($password_error)): ?>
            <div class="alert alert-danger py-2"><?= html_escape($password_error) ?></div>
          <?php endif; ?>

          <?= form_open('users/'.$form['id'].'/password') ?>
            <div class="mb-3">
              <label class="form-label required" for="new_password">New password</label>
              <input class="form-control form-control-lg" type="password" id="new_password" name="password" required minlength="6" autocomplete="new-password">
            </div>
            <div class="mb-4">
              <label class="form-label required" for="confirm_new_password">Confirm new password</label>
              <input class="form-control form-control-lg" type="password" id="confirm_new_password" name="confirm_password" required minlength="6" autocomplete="new-password">
            </div>
            <button class="btn btn-outline-primary btn-lg w-100" type="submit">Update password</button>
          <?= form_close() ?>
        </div>
      </div>
      <?php elseif ($mode === 'edit' && !empty($password_error)): ?>
      <div class="alert alert-danger"><?= html_escape($password_error) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script>
    (() => {
      const nameInput = document.getElementById('display_name');
      const fileInput = document.getElementById('avatar');
      const preview = document.getElementById('avatarPreview');
      const removeBox = document.getElementById('remove_avatar');
      if (!preview) return;

      const letterFrom = (value) => {
        const t = String(value || '').trim();
        return t ? t.charAt(0).toUpperCase() : '?';
      };

      const showLetter = () => {
        preview.innerHTML = `<span id="avatarLetter">${letterFrom(nameInput ? nameInput.value : '')}</span>`;
        preview.style.background = '#0d6efd';
      };

      if (nameInput) {
        nameInput.addEventListener('input', () => {
          if (preview.querySelector('img') && !(removeBox && removeBox.checked)) return;
          showLetter();
        });
      }

      if (fileInput) {
        fileInput.addEventListener('change', () => {
          const file = fileInput.files && fileInput.files[0];
          if (!file) {
            showLetter();
            return;
          }
          if (!file.type.startsWith('image/')) {
            fileInput.value = '';
            showLetter();
            return;
          }
          const url = URL.createObjectURL(file);
          preview.innerHTML = `<img src="${url}" alt="">`;
          if (removeBox) removeBox.checked = false;
        });
      }

      if (removeBox) {
        removeBox.addEventListener('change', () => {
          if (removeBox.checked) {
            if (fileInput) fileInput.value = '';
            showLetter();
          }
        });
      }
    })();

    (() => {
      const form = document.getElementById('userForm');
      if (!form || form.dataset.requireUnique !== '1') return;

      const checkUrl = form.dataset.checkUnique;
      const excludeId = form.dataset.excludeId || '0';
      const username = document.getElementById('username');
      const email = document.getElementById('email');
      const phone = document.getElementById('phone');
      const country = document.getElementById('country_code');
      if (!username || !email || !phone || !country) return;

      const state = { username: true, email: true, phone: true };
      const timers = {};

      const setFeedback = (field, el, available, message) => {
        state[field] = available;
        const hasValue = String(el.value || '').trim() !== '';
        el.classList.toggle('is-invalid', !available);
        el.classList.toggle('is-valid', available && hasValue);
        const box = document.getElementById(field + 'Feedback');
        if (!box) return;
        if (!available) {
          box.textContent = message || 'Already taken';
          box.classList.add('text-danger');
          box.classList.remove('text-success');
        } else if (hasValue) {
          box.textContent = 'Available';
          box.classList.add('text-success');
          box.classList.remove('text-danger');
        } else {
          box.textContent = '';
          box.classList.remove('text-danger', 'text-success');
        }
      };

      const checkField = async (field) => {
        let value = '';
        let params = new URLSearchParams({ field, exclude_id: excludeId });
        if (field === 'username') {
          value = username.value.trim();
          params.set('value', value);
        } else if (field === 'email') {
          value = email.value.trim();
          params.set('value', value);
        } else {
          value = phone.value.replace(/\D+/g, '');
          params.set('value', value);
          params.set('country_code', country.value);
        }
        if (!value || (field === 'phone' && !country.value)) {
          setFeedback(field, field === 'phone' ? phone : document.getElementById(field), true, '');
          document.getElementById(field === 'phone' ? 'phone' : field).classList.remove('is-valid', 'is-invalid');
          const box = document.getElementById(field + 'Feedback');
          if (box) {
            box.textContent = '';
            box.classList.remove('text-danger', 'text-success');
          }
          state[field] = true;
          return true;
        }
        try {
          const res = await fetch(`${checkUrl}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
          });
          const data = await res.json();
          const available = !!(data.ok && data.available);
          const el = field === 'phone' ? phone : document.getElementById(field);
          setFeedback(field, el, available, data.message || '');
          return available;
        } catch (_) {
          return true;
        }
      };

      const schedule = (field) => {
        clearTimeout(timers[field]);
        timers[field] = setTimeout(() => { checkField(field); }, 350);
      };

      username.addEventListener('blur', () => checkField('username'));
      email.addEventListener('blur', () => checkField('email'));
      phone.addEventListener('blur', () => checkField('phone'));
      country.addEventListener('change', () => checkField('phone'));
      username.addEventListener('input', () => schedule('username'));
      email.addEventListener('input', () => schedule('email'));
      phone.addEventListener('input', () => schedule('phone'));

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const results = await Promise.all([
          checkField('username'),
          checkField('email'),
          checkField('phone'),
        ]);
        if (results.some((ok) => !ok) || !state.username || !state.email || !state.phone) {
          const firstInvalid = form.querySelector('.is-invalid');
          if (firstInvalid) firstInvalid.focus();
          return;
        }
        form.submit();
      });
    })();
  </script>
</body>
</html>
