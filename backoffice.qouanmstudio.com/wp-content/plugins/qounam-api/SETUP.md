# Qounam API - Complete Setup Instructions

## Overview

This plugin provides a complete REST API solution for your Qounam headless CMS with:

- JWT Authentication
- Account Management
- Custom Endpoints
- Customizable REST API URL
- ACF Pro Support

## Installation Steps

### 1. Plugin Activation

1. Go to **WordPress Admin → Plugins**
2. Find **"Qounam API"**
3. Click **"Activate"**

### 2. Configure JWT Secret (Recommended)

Edit `wp-config.php` and add:

```php
define('JWT_AUTH_SECRET_KEY', 'your-unique-secret-key-here-change-this-in-production');
```

**Important:** Change this in production to a strong, random key.

### 3. Customize REST API URL (Optional)

1. Go to **WordPress Admin → Settings → Qounam API**
2. Enter your desired REST API prefix (default: `api`)
3. Click **"Save Changes"**
4. Go to **Settings → Permalinks → Save Changes** (to flush rewrite rules)

**Examples:**

- `api` → `http://your-site.com/api/qounam/v1/`
- `v1` → `http://your-site.com/v1/qounam/v1/`
- `wp-json` → `http://your-site.com/wp-json/qounam/v1/`

## Create Content Structure

### Step 1: Create Custom Post Type

**Option A: Using Plugin (Easiest)**

1. Install "Custom Post Type UI" plugin
2. Create post type: `program`
3. Enable "Show in REST API"

**Option B: Using Code**
Add to `functions.php`:

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

### Step 2: Create Taxonomy/Categories

**Option A: Using Admin UI**

1. Go to **Programs → Categories**
2. Add categories

**Option B: Using Code**

```php
add_action('init', function() {
    register_taxonomy('program_category', 'program', array(
        'label' => 'Categories',
        'show_in_rest' => true,
        'rest_base' => 'program-categories',
    ));
});
```

### Step 3: Add ACF Fields

1. Install **ACF Pro** plugin
2. Go to **ACF → Add Field Group**
3. Create fields:
   - Price (Number)
   - Duration (Text)
   - Level (Select)
   - Instructor (Text)
   - etc.
4. Assign to **"program"** post type
5. Fields automatically appear in REST API!

### Step 4: Create Test Programs

1. Go to **Programs → Add New**
2. Fill in:
   - Title
   - Description
   - Featured Image
   - ACF Fields
3. Click **"Publish"**

## Test the API

### Using cURL

**Register User:**

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

**Login:**

```bash
curl -X POST http://localhost/wamp64/www/the-qounam/api/qounam/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "TestPass123"
  }'
```

**Get Profile:**

```bash
curl -X GET http://localhost/wamp64/www/the-qounam/api/qounam/v1/account/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Get Programs:**

```bash
curl -X GET http://localhost/wamp64/www/the-qounam/api/wp/v2/programs
```

### Using Postman

1. Create new request
2. Set method to **POST**
3. URL: `http://localhost/wamp64/www/the-qounam/api/qounam/v1/auth/login`
4. Headers: `Content-Type: application/json`
5. Body (raw JSON):

```json
{
  "username": "testuser",
  "password": "TestPass123"
}
```

6. Click **Send**

## Connect Your Frontend

### React Example

```javascript
import { useState, useEffect } from "react";

function App() {
  const [programs, setPrograms] = useState([]);
  const [token, setToken] = useState(localStorage.getItem("token"));

  // Login
  const handleLogin = async (username, password) => {
    const response = await fetch(
      "http://your-site.com/api/qounam/v1/auth/login",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password }),
      },
    );
    const data = await response.json();
    localStorage.setItem("token", data.token);
    setToken(data.token);
  };

  // Get Programs
  useEffect(() => {
    fetch("http://your-site.com/api/wp/v2/programs")
      .then((res) => res.json())
      .then((data) => setPrograms(data));
  }, []);

  return (
    <div>
      {programs.map((program) => (
        <div key={program.id}>
          <h3>{program.title.rendered}</h3>
          <p>{program.excerpt.rendered}</p>
        </div>
      ))}
    </div>
  );
}

export default App;
```

### Vue Example

```vue
<template>
  <div>
    <div v-for="program in programs" :key="program.id">
      <h3>{{ program.title.rendered }}</h3>
      <p>{{ program.excerpt.rendered }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      programs: [],
    };
  },
  mounted() {
    fetch("http://your-site.com/api/wp/v2/programs")
      .then((res) => res.json())
      .then((data) => (this.programs = data));
  },
};
</script>
```

## Add Custom Endpoints

Edit `includes/custom-api.php`:

```php
// Register endpoint
register_rest_route('qounam/v1', '/my-custom-endpoint', array(
    'methods' => 'POST',
    'callback' => 'my_custom_handler',
    'permission_callback' => 'qounam_check_jwt_auth', // Protected
));

// Handler function
function my_custom_handler($request) {
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    // Your logic here
    $data = array('message' => 'Success');

    return array('success' => true, 'data' => $data);
}
```

## API Endpoints Reference

### Authentication (No Auth Required)

- `POST /auth/register` - Register new user
- `POST /auth/login` - Login user
- `POST /auth/verify` - Verify token
- `POST /auth/refresh` - Refresh token

### Account (JWT Required)

- `GET /account/profile` - Get profile
- `POST /account/profile` - Update profile
- `POST /account/change-password` - Change password
- `GET /account/settings` - Get settings
- `POST /account/settings` - Update settings
- `POST /account/delete` - Delete account

### Custom (JWT Required)

- `GET /user/programs` - Get user programs
- `POST /user/programs/{id}/enroll` - Enroll in program
- `GET /programs/{id}` - Get program details

### WordPress REST API

- `GET /wp/v2/programs` - Get all programs
- `GET /wp/v2/programs/{id}` - Get single program
- `GET /wp/v2/program-categories` - Get categories
- `GET /wp/v2/users` - Get users

## Troubleshooting

### CORS Errors

- Plugin handles CORS automatically
- Ensure plugin is activated
- Check browser console for specific error

### Token Not Working

- Verify JWT secret key is set in `wp-config.php`
- Check token hasn't expired (30 days)
- Ensure Authorization header format: `Bearer {token}`

### REST API URL Not Changing

- Go to Settings → Qounam API
- Update the prefix
- Go to Settings → Permalinks → Save Changes

### ACF Fields Not Showing

- Install ACF Pro plugin
- Create field groups
- Assign to post type
- Refresh API

### Post Type Not in API

- Ensure `show_in_rest` is set to `true`
- Flush rewrite rules (Settings → Permalinks → Save)

## Security Checklist

- [ ] Change JWT secret key in production
- [ ] Use HTTPS in production
- [ ] Set strong passwords
- [ ] Regularly update WordPress and plugins
- [ ] Monitor API logs
- [ ] Use environment variables for secrets

## File Structure

```
qounam-api/
├── qounam-api.php       # Main plugin file
├── includes/
│   ├── jwt-auth.php           # JWT authentication
│   ├── account-api.php        # Account management
│   └── custom-api.php         # Custom endpoints
├── README.md                  # Full documentation
├── QUICK-START.md            # Quick start guide
└── SETUP.md                  # This file
```

## Support & Documentation

- **README.md** - Complete API documentation
- **QUICK-START.md** - Quick setup guide
- **SETUP.md** - This setup guide

## Next Steps

1. ✅ Activate plugin
2. ✅ Set JWT secret
3. ✅ Customize REST API URL
4. ✅ Create post types
5. ✅ Add ACF fields
6. ✅ Create test content
7. ✅ Test API endpoints
8. ✅ Connect frontend
9. ✅ Add custom endpoints
10. ✅ Deploy to production

## Ready to Go!

Your Qounam API is now ready for your frontend application! 🚀
