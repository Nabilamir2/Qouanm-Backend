# Qounam API Documentation

## Base URL

```
https://yourdomain.com/wp-json/qounam/v1/
```

## Authentication

- JWT Authentication is used for protected endpoints
- Include token in the `Authorization` header: `Bearer YOUR_JWT_TOKEN`

## Endpoints

### 1. Account Management

#### Register a New User

- **Endpoint**: `POST /account/register`
- **Request Body**:
  ```json
  {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "phone_number": "+1234567890",
    "company": "ACME Corp",
    "position": "HR Manager",
    "government": "Ministry of Education"
  }
  ```
- **Response**:
  ```json
  {
    "success": true,
    "message": "Registration successful",
    "user_id": 123
  }
  ```

#### Login

- **Endpoint**: `POST /account/login`
- **Request Body**:
  ```json
  {
    "email": "john@example.com",
    "password": "SecurePass123!"
  }
  ```
- **Response**:
  ```json
  {
    "success": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 123,
      "email": "john@example.com",
      "first_name": "John",
      "last_name": "Doe"
    }
  }
  ```

### 2. Homepage

- **Endpoint**: `GET /homepage`
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "hero": {
        "label": "Welcome",
        "title": "Qounam",
        "subtitle": "Professional Development",
        "image": "https://example.com/hero.jpg"
      },
      "explore": {
        "label": "Explore",
        "title": "Our Programs",
        "subtitle": "Discover opportunities",
        "programs": [
          {
            "id": 1,
            "title": "Leadership Training",
            "thumbnail": "https://example.com/thumbnail.jpg",
            "permalink": "https://example.com/programs/leadership-training",
            "starting_date": "December 01, 2023",
            "ending_date": "December 15, 2023",
            "limited_offer": true,
            "stock_alert": "Limited seats available",
            "program_category": "Leadership",
            "duration_per_week": "2 days/Week",
            "duration_total": "12 weeks",
            "price": "1000",
            "currency": "EGP"
          }
        ]
      },
      "overview": {
        "label": "Overview",
        "title": "About Us",
        "subtitle": "Learn more",
        "content": "We provide comprehensive training..."
      },
      "facilities": {
        "label": "Facilities",
        "title": "Our Centers",
        "subtitle": "State-of-the-art facilities",
        "items": [
          {
            "id": 1,
            "title": "Main Campus",
            "description": "Modern facilities"
          }
        ]
      },
      "news": {
        "label": "News",
        "title": "Latest Updates",
        "subtitle": "Stay informed",
        "items": [
          {
            "id": 1,
            "title": "New Course Available",
            "thumbnail": "https://example.com/thumbnail.jpg",
            "date": "December 01, 2023",
            "permalink": "https://example.com/news/new-course-available"
          }
        ]
      },
      "partners": {
        "label": "Partners",
        "title": "Our Partners",
        "subtitle": "Trusted collaborations",
        "items": [
          {
            "id": 1,
            "name": "Partner Name",
            "logo": "https://example.com/logo.jpg"
          }
        ]
      }
    }
  }
  ```

### 3. Success Stories

- **Endpoint**: `GET /success-stories`
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "title": "Success Stories",
      "stories": [
        {
          "id": 1,
          "text": "The training was exceptional...",
          "image": "https://example.com/image.jpg",
          "name": "John Doe"
        }
      ]
    }
  }
  ```

### 4. FAQs

- **Endpoint**: `GET /faqs`
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "red_label_text": "FAQs",
      "title": "Frequently Asked Questions",
      "image": "https://example.com/faq-image.jpg",
      "faqs": [
        {
          "id": 1,
          "question": "How do I register?",
          "answer": "You can register through our website...",
          "featured": true  (to flag faqs on homepage)
        }
      ]
    }
  }
  ```

### 6. Subscription Management

#### Subscribe to Newsletter

- **Endpoint**: `POST /subscribe`
- **Request Body**:
  ```json
  {
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone_number": "+1234567890",
    "company": "ACME Corp",
    "position": "HR Manager",
    "government": "Ministry of Education",
    "interests": "Leadership training, team development",
    "subscription_type": "newsletter"
  }
  ```
- **Response**:
  ```json
  {
    "success": true,
    "message": "Thank you for subscribing! Please check your email for confirmation.",
    "subscriber_id": 789,
    "email_sent": true
  }
  ```

#### Get All Subscribers (Admin Only)

- **Endpoint**: `GET /subscribers`
- **Query Parameters**:
  - `per_page` (optional): Number of subscribers per page (default: 10)
  - `page` (optional): Page number (default: 1)
  - `status` (optional): Post status filter (publish, pending, draft, trash, any)
  - `search` (optional): Search term for email or name
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "subscribers": [
        {
          "id": 789,
          "title": "Subscriber: john@example.com",
          "date": "2025-01-20 10:30:00",
          "status": "publish",
          "fields": {
            "email": "john@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "phone_number": "+1234567890",
            "company": "ACME Corp",
            "position": "HR Manager",
            "government": "Ministry of Education",
            "interests": "Leadership training, team development",
            "subscription_type": "newsletter",
            "subscription_date": "2025-01-20 10:30:00",
            "subscriber_status": "active"
          }
        }
      ],
      "pagination": {
        "total": 150,
        "pages": 15,
        "current_page": 1,
        "per_page": 10
      }
    }
  }
  ```

#### Confirm Subscription

- **Endpoint**: `GET /confirm-subscription`
- **Query Parameters**:
  - `token` (required): Confirmation token from email
- **Response**:
  ```json
  {
    "success": true,
    "message": "Your subscription has been confirmed successfully! Welcome to our newsletter.",
    "subscriber_id": 789
  }
  ```

### 7. Navigation Menus (Header and Footer Menus)

- **Endpoint**: `GET /menu/{menu-slug}`
- **Example**: `GET /menu/primary`
- **Response**:
  ```json
  {
    "id": 123,
    "name": "Primary Menu",
    "items": [
      {
        "id": 1,
        "title": "Home",
        "url": "https://example.com",
        "target": "",
        "classes": [],
        "children": []
      }
    ]
  }
  ```

## Error Handling

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "code": "error_code",
  "data": {}
}
```

## Common HTTP Status Codes

- `200 OK` - Successful request
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request parameters
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

## Rate Limiting

- Public endpoints: 100 requests per minute
- Authenticated endpoints: 500 requests per minute

## CORS

- All endpoints support CORS
- Allowed origins: Your frontend domain

## Versioning

- API version is included in the URL path (`/wp-json/qounam/v1/`)
- Breaking changes will be released in a new version

## Support

For API support, please contact the development team with:

- Endpoint being accessed
- Request/response details
- Any error messages received

---

This documentation is automatically generated and may be updated as the API evolves. Always refer to the latest version of this document.
