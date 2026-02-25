# Quick Start Guide - Qounam API

## Step 1: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "Qounam API"
3. Click "Activate"

## Step 2: Set JWT Secret (Optional but Recommended)

Add to `wp-config.php`:

```php
define('JWT_AUTH_SECRET_KEY', 'your-unique-secret-key-here-change-this-in-production');
```

## Step 3: Customize REST API URL (Optional)

1. Go to WordPress Admin → Settings → Qounam API
2. Change the REST API URL prefix (default: `api`)
3. Click "Save Changes"
4. Go to Settings → Permalinks → Save Changes (to flush rewrite rules)

**Default URL:** `http://localhost/wamp64/www/the-qounam/api/qounam/v1/`

## Step 4: Test the API

### Register a User

```bash
curl -X POST http://localhost/wamp64/www/the-qounam/api/qounam/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "TestPass123",
    "name": "Test User"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Registration successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "testuser",
    "email": "test@example.com",
    "name": "Test User"
  }
}
```

### Login

```bash
curl -X POST http://localhost/wamp64/www/the-qounam/api/qounam/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "TestPass123"
  }'
```

### Get Profile (Using Token)

```bash
curl -X GET http://localhost/wamp64/www/the-qounam/api/qounam/v1/account/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Step 5: Create Post Type

Install "Custom Post Type UI" plugin or add to `functions.php`:

```php
add_action('init', function() {
    register_post_type('program', array(
        'label' => 'Programs',
        'public' => true,
        'show_in_rest' => true,
        'rest_base' => 'programs',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
    ));
});
```

## Step 6: Add ACF Fields

1. Install ACF Pro plugin
2. Go to ACF → Add Field Group
3. Create fields (price, duration, level, etc.)
4. Assign to "program" post type
5. Fields automatically appear in API!

## Step 7: Create Programs

1. Go to Programs → Add New
2. Fill in title, description, featured image
3. Fill in ACF fields
4. Publish

## Step 8: Access API

**Get all programs:**

```
GET /api/wp/v2/programs
```

**Get single program with ACF fields:**

```
GET /api/wp/v2/programs/1
```

**Get user programs (authenticated):**

```
GET /api/qounam/v1/user/programs
Authorization: Bearer {token}
```

## Frontend Example

```javascript
// Store token after login
const loginResponse = await fetch(
  "http://your-site.com/api/qounam/v1/auth/login",
  {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      username: "testuser",
      password: "TestPass123",
    }),
  },
);

const { token } = await loginResponse.json();
localStorage.setItem("token", token);

// Use token for authenticated requests
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

// Get programs
const programsResponse = await fetch("http://your-site.com/api/wp/v2/programs");
const programs = await programsResponse.json();
console.log(programs);
```

## Available Endpoints

**Authentication:**

- `POST /auth/register` - Register new user
- `POST /auth/login` - Login user
- `POST /auth/verify` - Verify token
- `POST /auth/refresh` - Refresh token

**Account (Authenticated):**

- `GET /account/profile` - Get profile
- `POST /account/profile` - Update profile
- `POST /account/change-password` - Change password
- `GET /account/settings` - Get settings
- `POST /account/settings` - Update settings
- `POST /account/delete` - Delete account

**Custom (Authenticated):**

- `GET /user/programs` - Get user programs
- `POST /user/programs/{id}/enroll` - Enroll in program
- `GET /programs/{id}` - Get program details

**WordPress REST API:**

- `GET /wp/v2/programs` - Get all programs
- `GET /wp/v2/programs/{id}` - Get single program
- `GET /wp/v2/program-categories` - Get categories
- `GET /wp/v2/users` - Get users

## Adding Custom Endpoints

Edit `includes/custom-api.php` and add:

```php
register_rest_route('qounam/v1', '/my-endpoint', array(
    'methods' => 'POST',
    'callback' => 'my_handler',
    'permission_callback' => 'qounam_check_jwt_auth',
));

function my_handler($request) {
    $user_id = qounam_get_current_user_from_jwt();
    // Your code here
    return array('success' => true);
}
```

## That's It!

You now have a fully functional API with:

- ✅ JWT Authentication
- ✅ Account Management
- ✅ Custom Endpoints
- ✅ WordPress REST API
- ✅ ACF Pro Support
- ✅ Customizable REST API URL

Start building your frontend! 🚀
