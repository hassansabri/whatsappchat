# Chat

WhatsApp-style messaging app built with **CodeIgniter 3**, **Bootstrap**, and **MySQL**, running on XAMPP at **http://localhost/chat/**.

## Requirements

- XAMPP (Apache + MySQL + PHP 7.4+)
- PHP extensions: `mysqli`, `fileinfo`, `session`, `json`
- Modern browser with microphone permission (for voice messages)

## Setup

1. Place the project in `htdocs/chat`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Create the database and import the schema:

```sql
CREATE DATABASE IF NOT EXISTS chating CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

```bash
mysql -u root chating < sql/schema.sql
```

On Windows (XAMPP):

```bash
D:\xampp\mysql\bin\mysql.exe -u root chating < D:\xampp\htdocs\chat\sql\schema.sql
```

4. Confirm database settings in `application/config/database.php`:

| Setting  | Default (XAMPP) |
|----------|-----------------|
| hostname | `localhost`     |
| username | `root`          |
| password | *(empty)*       |
| database | `chating`       |

5. Confirm base URL in `application/config/config.php`:

```php
$config['base_url'] = 'http://localhost/chat/';
```

6. Ensure these folders are writable:

- `uploads/chat/`
- `uploads/avatars/`
- `application/cache/`
- `application/logs/`

7. Open **http://localhost/chat/**

## Demo login

Password for all demo users: `password123`

| Username | Type   |
|----------|--------|
| alice    | system |
| bob      | normal |
| carol    | normal |

Login uses **username only** (not email).

## Features

### Messaging

- One-to-one chat with polling (near real-time)
- Typing / erasing indicators
- Online / offline status and last seen
- Emoji picker
- Image and PDF attachments
- Location sharing (OpenStreetMap link)
- Voice messages (record, send, play, seek, speed) as WAV

### Receipts (WhatsApp-style)

- Sent / delivered / seen ticks
- Voice played indicator

### Delete & chat management

- Per-message menu: **Delete for everyone** / **Delete for me**
- Deleted for everyone shows: *This message was deleted*
- Header menu: **Clear chat** (empty for you, chat stays in list)
- Header menu: **Delete chat** (clears and removes from your list; reappears if they message you or you start chat again)

### Users & friends

- User types: **system** and **normal** (new accounts default to normal)
- Friends: search, request, accept, unfriend
- System users can see all users and manage accounts
- Normal users chat with friends only
- Profile: display name, avatar, password
- Username / email / phone uniqueness checks
- Gender and phone with country code

## Main URLs

| URL | Description |
|-----|-------------|
| `/login` | Sign in |
| `/register` | Create account |
| `/chat` | Chat home |
| `/profile` | My profile |
| `/friends` | Friends |
| `/users` | Users list (system) |
| `/users/add` | Add user |

## API endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET/POST | `/api/conversations` | List chats / start chat |
| GET/POST | `/api/messages` | Load / send messages |
| POST | `/api/message-delete` | Delete for me / everyone |
| POST | `/api/message-played` | Mark voice as played |
| POST | `/api/conversation-clear` | Clear chat for me |
| POST | `/api/conversation-delete` | Delete chat for me |
| GET/POST | `/api/typing` | Typing status |
| GET | `/files/view/{id}` | View attachment |
| GET | `/files/download/{id}` | Download attachment |
| GET | `/files/avatar/{id}` | User avatar |

## Permissions

- **Logged in:** chat, friends, own profile
- **Normal users:** chat with friends only; other usernames hidden
- **System users:** see all users; manage accounts and user types
- **Own profile:** display name, avatar, and password only

## Project structure

```
chat/
├── application/          # Controllers, models, views, config
├── assets/               # CSS, JS, Bootstrap, icons (local)
├── sql/schema.sql        # Schema + demo seed data
├── system/               # CodeIgniter core
├── uploads/              # Chat files and avatars
├── index.php
└── README.md
```

## Notes

- Assets are local under `assets/` (no CDN required).
- Chat uploads are served through `files/view` and `files/download`, not as direct public paths.
- Avatars are served through `files/avatar/{user_id}`.
- Clear/delete chat and delete-for-me are per-user; the other person keeps their copy unless you use delete for everyone.
- Voice recording needs browser mic permission on localhost/HTTPS.
