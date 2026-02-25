# Qounam API Plugin - Complete Documentation Index

## 📋 Documentation Files

### 1. **README.md** - Complete API Reference

Full documentation of all API endpoints with examples.

- Authentication endpoints
- Account management endpoints
- Custom endpoints
- Frontend integration examples
- Helper functions
- Security information
- Troubleshooting guide

**Start here for:** Complete API reference and examples

### 2. **QUICK-START.md** - Fast Setup Guide

Get up and running in 8 steps with cURL and JavaScript examples.

- Plugin activation
- JWT secret configuration
- REST API URL customization
- API testing with cURL
- Frontend examples
- Available endpoints summary

**Start here for:** Quick setup and testing

### 3. **SETUP.md** - Detailed Setup Instructions

Step-by-step setup with best practices and troubleshooting.

- Installation steps
- JWT configuration
- REST API URL customization
- Content structure creation
- Post types and taxonomies
- ACF field setup
- Frontend integration (React, Vue)
- Custom endpoints creation
- Security checklist

**Start here for:** Complete setup with all details

## 🚀 Quick Navigation

### I want to...

**Get started quickly**
→ Read [QUICK-START.md](QUICK-START.md)

**Understand all API endpoints**
→ Read [README.md](README.md)

**Set up everything properly**
→ Read [SETUP.md](SETUP.md)

**Add custom endpoints**
→ Edit `includes/custom-api.php` and read [README.md](README.md#creating-custom-endpoints)

**Change REST API URL**
→ Go to WordPress Admin → Settings → Qounam API

**Configure JWT secret**
→ Add to `wp-config.php`: `define('JWT_AUTH_SECRET_KEY', 'your-key');`

## 📁 Plugin Structure

```
qounam-api/
├── qounam-api.php          # Main plugin file
│                                 # - CORS headers
│                                 # - Admin settings page
│                                 # - REST API URL customization
│
├── includes/
│   ├── jwt-auth.php              # JWT Authentication
│   │                             # - Register user
│   │                             # - Login user
│   │                             # - Token verification
│   │                             # - Token refresh
│   │
│   ├── account-api.php           # Account Management
│   │                             # - Get/Update profile
│   │                             # - Change password
│   │                             # - Get/Update settings
│   │                             # - Delete account
│   │
│   └── custom-api.php            # Custom Endpoints
│                                 # - Get user programs
│                                 # - Enroll in program
│                                 # - Get program details
│
├── README.md                     # Complete API documentation
├── QUICK-START.md               # Quick setup guide
├── SETUP.md                     # Detailed setup instructions
└── INDEX.md                     # This file
```

## 🔑 Key Features

✅ **JWT Authentication**

- Secure token-based authentication
- HMAC-SHA256 signing
- 30-day token expiration
- Token refresh capability

✅ **Account Management**

- User registration
- User login
- Profile management
- Password change
- Account settings
- Account deletion

✅ **Custom Endpoints**

- Easy to extend
- Template examples included
- Full JWT integration

✅ **REST API Customization**

- Change REST API URL prefix
- Admin settings page
- Default: `/api/`

✅ **WordPress Integration**

- Native REST API support
- ACF Pro compatible
- CORS enabled
- Preflight request handling

## 🔐 Security Features

- HMAC-SHA256 token signing
- Configurable JWT secret
- Token expiration (30 days)
- Input validation & sanitization
- Password minimum 6 characters
- CORS headers
- Nonce verification for admin settings

## 📡 API Base URL

Default: `http://your-site.com/api/qounam/v1/`

Customizable via WordPress Admin → Settings → Qounam API

## 🎯 Common Tasks

### Register a User

```bash
POST /api/qounam/v1/auth/register
{
  "username": "user",
  "email": "user@example.com",
  "password": "pass",
  "name": "User Name"
}
```

### Login

```bash
POST /api/qounam/v1/auth/login
{
  "username": "user",
  "password": "pass"
}
```

### Get Profile (Authenticated)

```bash
GET /api/qounam/v1/account/profile
Authorization: Bearer {token}
```

### Get All Programs

```bash
GET /api/wp/v2/programs
```

### Get Single Program with ACF Fields

```bash
GET /api/wp/v2/programs/1
```

## 🛠️ Configuration

### JWT Secret Key

Add to `wp-config.php`:

```php
define('JWT_AUTH_SECRET_KEY', 'your-unique-secret-key');
```

### REST API URL Prefix

1. WordPress Admin → Settings → Qounam API
2. Enter desired prefix (default: `api`)
3. Save changes
4. Flush rewrite rules (Settings → Permalinks → Save)

## 📚 Helper Functions

Use these in custom endpoints:

```php
// Get current user ID from JWT
$user_id = qounam_get_current_user_from_jwt();

// Check if user is authenticated
if (qounam_check_jwt_auth()) { }

// Generate JWT token
$token = qounam_generate_jwt($user_id);

// Decode JWT token
$payload = qounam_decode_jwt($token, $secret_key);
```

## 🚨 Troubleshooting

**Problem: Token not working**

- Verify JWT secret key is set
- Check token hasn't expired
- Ensure Authorization header: `Bearer {token}`

**Problem: CORS errors**

- Plugin handles CORS automatically
- Ensure plugin is activated

**Problem: REST API URL not changing**

- Go to Settings → Qounam API
- Update prefix
- Flush rewrite rules (Settings → Permalinks → Save)

**Problem: ACF fields not showing**

- Install ACF Pro
- Create field groups
- Assign to post type

See [SETUP.md](SETUP.md#troubleshooting) for more troubleshooting tips.

## 📖 Endpoint Summary

| Category      | Endpoint                     | Method | Auth | Description         |
| ------------- | ---------------------------- | ------ | ---- | ------------------- |
| **Auth**      | `/auth/register`             | POST   | No   | Register user       |
|               | `/auth/login`                | POST   | No   | Login user          |
|               | `/auth/verify`               | POST   | No   | Verify token        |
|               | `/auth/refresh`              | POST   | No   | Refresh token       |
| **Account**   | `/account/profile`           | GET    | Yes  | Get profile         |
|               | `/account/profile`           | POST   | Yes  | Update profile      |
|               | `/account/change-password`   | POST   | Yes  | Change password     |
|               | `/account/settings`          | GET    | Yes  | Get settings        |
|               | `/account/settings`          | POST   | Yes  | Update settings     |
|               | `/account/delete`            | POST   | Yes  | Delete account      |
| **Custom**    | `/user/programs`             | GET    | Yes  | Get user programs   |
|               | `/user/programs/{id}/enroll` | POST   | Yes  | Enroll in program   |
|               | `/programs/{id}`             | GET    | No   | Get program details |
| **WordPress** | `/wp/v2/programs`            | GET    | No   | Get all programs    |
|               | `/wp/v2/programs/{id}`       | GET    | No   | Get single program  |
|               | `/wp/v2/program-categories`  | GET    | No   | Get categories      |
|               | `/wp/v2/users`               | GET    | No   | Get users           |

## 🎓 Learning Path

1. **New to the plugin?**
   - Read [QUICK-START.md](QUICK-START.md)
   - Test endpoints with cURL
   - Try frontend examples

2. **Setting up for production?**
   - Read [SETUP.md](SETUP.md)
   - Follow security checklist
   - Configure JWT secret
   - Customize REST API URL

3. **Need complete reference?**
   - Read [README.md](README.md)
   - Check all endpoints
   - Review helper functions

4. **Adding custom features?**
   - Edit `includes/custom-api.php`
   - Use helper functions
   - Follow endpoint pattern

## 💡 Tips & Best Practices

- Always use HTTPS in production
- Change JWT secret key in production
- Use strong, random passwords
- Monitor API usage
- Keep WordPress and plugins updated
- Test endpoints before deploying
- Use environment variables for secrets
- Document custom endpoints

## 📞 Support

For issues or questions:

1. Check [SETUP.md](SETUP.md#troubleshooting) troubleshooting section
2. Review [README.md](README.md) for detailed documentation
3. Check WordPress debug log for errors
4. Verify plugin is activated
5. Ensure JWT secret is configured

## 📄 License

GPL v2 or later

## ✨ Ready to Go!

Your Qounam API is fully set up and ready for your frontend application!

**Next Steps:**

1. ✅ Activate plugin
2. ✅ Configure JWT secret
3. ✅ Customize REST API URL
4. ✅ Create post types & ACF fields
5. ✅ Test API endpoints
6. ✅ Connect your frontend
7. ✅ Deploy to production

Happy coding! 🚀
