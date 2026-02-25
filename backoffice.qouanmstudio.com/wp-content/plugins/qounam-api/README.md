# Qounam API - Complete Guide

A lightweight WordPress plugin with JWT authentication, account management, and custom API endpoints for your Qounam headless CMS.

## Features

✅ JWT Authentication (Login, Register, Token Refresh)  
✅ Account Management (Profile, Password, Settings)  
✅ Custom API Endpoints (Ready to extend)  
✅ CORS Support  
✅ WordPress REST API Integration  
✅ ACF Pro Compatible  
✅ Customizable REST API URL

## Installation

1. Upload `qounam-api` to `/wp-content/plugins/`
2. Activate the plugin
3. Configure JWT secret (optional, see Configuration)

## Configuration

### Set JWT Secret Key

Add to `wp-config.php`:

```php
define('JWT_AUTH_SECRET_KEY', 'your-super-secret-key-change-this');
```

### Customize REST API URL

1. Go to WordPress Admin → Settings → Qounam API
2. Change the REST API URL prefix (default: `api`)
3. Save changes

**Examples:**

- Default: `http://your-site.com/api/qounam/v1/`
- Custom: `http://your-site.com/v1/qounam/v1/`
- WordPress: `http://your-site.com/wp-json/qounam/v1/`

## Authentication Endpoints

### Register User

```
POST /api/qounam/v1/auth/register
Content-Type: application/json

{
  "username": "johndoe",
  "email": "john@example.com",
  "password": "SecurePass123",
  "name": "John Doe"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Registration successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "johndoe",
    "email": "john@example.com",
    "name": "John Doe",
    "avatar": "https://..."
  }
}
```

### Login User

```
POST /api/qounam/v1/auth/login
Content-Type: application/json

{
  "username": "johndoe",
  "password": "SecurePass123"
}
```

### Verify Token

```
POST /api/qounam/v1/auth/verify
Content-Type: application/json

{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

### Refresh Token

```
POST /api/qounam/v1/auth/refresh
Content-Type: application/json

{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

## Account Endpoints

All account endpoints require JWT token in Authorization header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Get Profile

```
GET /api/qounam/v1/account/profile
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "johndoe",
    "email": "john@example.com",
    "name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "avatar": "https://...",
    "bio": "Software developer",
    "phone": "+1234567890",
    "registered": "2025-10-18 18:00:00"
  }
}
```

### Update Profile

```
POST /api/qounam/v1/account/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "name": "John Doe",
  "bio": "Software developer",
  "phone": "+1234567890"
}
```

### Change Password

```
POST /api/qounam/v1/account/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
  "current_password": "OldPass123",
  "new_password": "NewPass456",
  "confirm_password": "NewPass456"
}
```

### Get Settings

```
GET /api/qounam/v1/account/settings
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "settings": {
    "email_notifications": "yes",
    "newsletter": "yes",
    "privacy": "public",
    "two_factor": "no"
  }
}
```

### Update Settings

```
POST /api/qounam/v1/account/settings
Authorization: Bearer {token}
Content-Type: application/json

{
  "email_notifications": "yes",
  "newsletter": "no",
  "privacy": "private",
  "two_factor": "yes"
}
```

### Delete Account

```
POST /api/qounam/v1/account/delete
Authorization: Bearer {token}
Content-Type: application/json

{
  "password": "YourPassword123"
}
```

## Custom API Endpoints

The plugin includes example custom endpoints. Add your own in `includes/custom-api.php`.

### Get User Programs

```
GET /api/qounam/v1/user/programs
Authorization: Bearer {token}
```

### Enroll in Program

```
POST /api/qounam/v1/user/programs/{program_id}/enroll
Authorization: Bearer {token}
```

### Get Program Details

```
GET /api/qounam/v1/programs/{id}
```

## Creating Custom Endpoints

Edit `includes/custom-api.php`:

```php
register_rest_route('qounam/v1', '/my-endpoint', array(
    'methods' => 'POST',
    'callback' => 'my_endpoint_handler',
    'permission_callback' => 'qounam_check_jwt_auth', // Protected
));

function my_endpoint_handler($request) {
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    // Your logic here

    return array('success' => true, 'data' => $data);
}
```

## Frontend Integration

### JavaScript Example

```javascript
// Register
const registerResponse = await fetch(
  "http://your-site.com/api/qounam/v1/auth/register",
  {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      username: "johndoe",
      email: "john@example.com",
      password: "SecurePass123",
      name: "John Doe",
    }),
  },
);

const { token, user } = await registerResponse.json();
localStorage.setItem("token", token);

// Get Profile
const profileResponse = await fetch(
  "http://your-site.com/api/qounam/v1/account/profile",
  {
    headers: {
      Authorization: `Bearer ${localStorage.getItem("token")}`,
    },
  },
);

const profile = await profileResponse.json();
console.log(profile.user);

// Update Profile
await fetch("http://your-site.com/api/qounam/v1/account/profile", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: `Bearer ${localStorage.getItem("token")}`,
  },
  body: JSON.stringify({
    first_name: "John",
    last_name: "Doe",
    bio: "Updated bio",
  }),
});
```

## Standard WordPress REST API

The plugin also supports WordPress's native REST API:

```
GET /api/wp/v2/programs
GET /api/wp/v2/programs/{id}
GET /api/wp/v2/program-categories
GET /api/wp/v2/users
```

## Token Expiration

Tokens expire after **30 days**. Use the refresh endpoint to get a new token:

```javascript
const refreshResponse = await fetch(
  "http://your-site.com/api/qounam/v1/auth/refresh",
  {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      token: localStorage.getItem("token"),
    }),
  },
);

const { token: newToken } = await refreshResponse.json();
localStorage.setItem("token", newToken);
```

## Helper Functions

Use these in your custom endpoints:

```php
// Get current user ID from JWT
$user_id = qounam_get_current_user_from_jwt();

// Check if user is authenticated
if (qounam_check_jwt_auth()) {
    // User is authenticated
}

// Generate JWT token
$token = qounam_generate_jwt($user_id);

// Decode JWT token
$payload = qounam_decode_jwt($token, $secret_key);
```

## Security

- Tokens are signed with HMAC-SHA256
- Change the JWT secret key in production
- Tokens expire after 30 days
- All endpoints validate input
- Password minimum 6 characters
- CORS headers enabled for cross-origin requests

## Troubleshooting

**Token not working?**

- Verify JWT secret key is set
- Check token hasn't expired
- Ensure Authorization header format: `Bearer {token}`

**CORS errors?**

- Plugin handles CORS automatically
- Ensure plugin is activated

**Custom endpoint not working?**

- Check function name matches callback
- Verify permission_callback is correct
- Use `qounam_check_jwt_auth` for protected endpoints

**REST API URL not changing?**

- Go to Settings → Qounam API
- Update the prefix
- Flush rewrite rules (Settings → Permalinks → Save)

## API Endpoints Summary

| Endpoint                     | Method | Auth | Description         |
| ---------------------------- | ------ | ---- | ------------------- |
| `/auth/register`             | POST   | No   | Register new user   |
| `/auth/login`                | POST   | No   | Login user          |
| `/auth/verify`               | POST   | No   | Verify token        |
| `/auth/refresh`              | POST   | No   | Refresh token       |
| `/account/profile`           | GET    | Yes  | Get profile         |
| `/account/profile`           | POST   | Yes  | Update profile      |
| `/account/change-password`   | POST   | Yes  | Change password     |
| `/account/settings`          | GET    | Yes  | Get settings        |
| `/account/settings`          | POST   | Yes  | Update settings     |
| `/account/delete`            | POST   | Yes  | Delete account      |
| `/user/programs`             | GET    | Yes  | Get user programs   |
| `/user/programs/{id}/enroll` | POST   | Yes  | Enroll in program   |
| `/programs/{id}`             | GET    | No   | Get program details |

## Support

For custom endpoints or modifications, edit the files in the `includes/` directory.

## License

GPL v2 or later
