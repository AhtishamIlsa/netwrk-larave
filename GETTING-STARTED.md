# Netwrk Laravel API - Getting Started

## ✅ What We've Completed

### 1. Laravel 12 Project Setup
- ✅ Laravel 12 installed
- ✅ Laravel MCP installed (for AI Cursor integration)
- ✅ Laravel Sanctum installed (authentication)
- ✅ Swagger (L5-Swagger) installed (API documentation)
- ✅ League CSV installed (for contact imports)
- ✅ Ramsey UUID installed (for UUID support)

### 2. Database Setup
- ✅ Users table created with all fields
- ✅ User profiles table created (for secondary profiles)
- ✅ Sanctum personal_access_tokens table
- ✅ Jobs and cache tables for queue system

### 3. Models Created
- ✅ User model with HasApiTokens, HasUuids
- ✅ UserProfile model with relationships
- ✅ Proper relationships between models

### 4. API Endpoints Implemented (First Chunk)

#### Authentication Endpoints:
- ✅ `POST /api/auth/signup` - Register new user
- ✅ `POST /api/auth/signin` - User login
- ✅ `POST /api/auth/check-user-exist` - Check if user exists
- ✅ `GET /api/auth/me` - Get current user
- ✅ `POST /api/auth/logout` - Logout user

#### User Profile Endpoints:
- ✅ `GET /api/users/profile` - Get user profile with secondary profiles
- ✅ `PUT /api/users/profile` - Update user profile
- ✅ `POST /api/users/secondary-profile` - Create secondary profile
- ✅ `GET /api/users/secondary-profiles` - Get all secondary profiles
- ✅ `DELETE /api/users/secondary-profile/{id}` - Delete secondary profile

### 5. Features Implemented
- ✅ UUID primary keys for all tables
- ✅ JWT-like authentication with Sanctum tokens
- ✅ Swagger documentation with OpenAPI annotations
- ✅ Input validation on all endpoints
- ✅ Proper error handling
- ✅ JSON responses matching frontend expectations

## 🚀 Server is Running

- **API URL**: http://localhost:8001
- **Swagger Documentation**: http://localhost:8001/api/documentation

## 📋 How to Test the API

### Option 1: Using Swagger UI
1. Open http://localhost:8001/api/documentation
2. Try the `/api/auth/signup` endpoint
3. Copy the token from the response
4. Click "Authorize" button and paste the token
5. Try other protected endpoints

### Option 2: Using Postman or Insomnia

#### 1. Register a User
```http
POST http://localhost:8001/api/auth/signup
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "company_name": "Tech Corp",
  "position": "Software Engineer",
  "location": "New York, USA",
  "bio": "Full stack developer",
  "industries": [
    {"name": "Technology"},
    {"name": "Software Development"}
  ],
  "social_links": {
    "linkedin": "https://linkedin.com/in/johndoe",
    "twitter": "https://twitter.com/johndoe"
  },
  "city": "New York"
}
```

#### 2. Login
```http
POST http://localhost:8001/api/auth/signin
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

#### 3. Get Profile (use token from login)
```http
GET http://localhost:8001/api/users/profile
Authorization: Bearer YOUR_TOKEN_HERE
```

#### 4. Create Secondary Profile
```http
POST http://localhost:8001/api/users/secondary-profile
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Smith",
  "email": "john.smith@example.com",
  "phone": "+1234567891",
  "company_name": "Another Corp",
  "position": "CTO",
  "location": "San Francisco",
  "industries": [
    {"name": "Technology"},
    {"name": "Startups"}
  ],
  "social_links": {
    "linkedin": "https://linkedin.com/in/johnsmith"
  }
}
```

## 🤖 Laravel MCP for AI Cursor

Laravel MCP is already set up! You can use AI Cursor to interact with your Laravel application.

### MCP Configuration for Cursor:

1. Open Cursor settings
2. Go to AI Features → MCP Servers
3. Add this configuration:

```json
{
  "netwrk": {
    "command": "php",
    "args": [
      "artisan",
      "mcp:serve",
      "netwrk"
    ],
    "cwd": "/var/www/html/netwrk-laravel"
  }
}
```

### How to Use MCP with Cursor:

Once configured, you can ask Cursor to:
- "Generate a new API endpoint for contacts"
- "Create a migration for the contacts table"
- "Test the signup endpoint"
- "Add validation to the user controller"

Cursor will use the MCP server to directly interact with your Laravel application!

## 📁 Project Structure

```
netwrk-laravel/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php (✅ Authentication)
│   │           └── UserController.php (✅ User profiles)
│   └── Models/
│       ├── User.php (✅ User model)
│       └── UserProfile.php (✅ Secondary profiles)
├── database/
│   └── migrations/
│       ├── create_users_table.php (✅)
│       └── create_user_profiles_table.php (✅)
└── routes/
    └── api.php (✅ API routes)
```

## 🎯 Next Steps (For Login Implementation)

Now that registration is working, next we'll implement:

1. **Enhanced Authentication**:
   - Password reset functionality
   - Email verification
   - OTP verification
   - Refresh token support

2. **Contacts Module** (Next chunk):
   - Contact CRUD operations
   - CSV import/export
   - Google/Outlook integration
   - Contact search and filtering

## 💡 Quick Commands

```bash
# Run migrations
php artisan migrate

# Regenerate Swagger docs
php artisan l5-swagger:generate

# Create new controller
php artisan make:controller Api/ContactsController

# Create new model with migration
php artisan make:model Contact -m

# Run tests
php artisan test

# Clear cache
php artisan cache:clear
php artisan config:clear
```

## 🔧 Environment Setup

Make sure your `.env` file is configured:

```env
APP_NAME=Netwrk
APP_URL=http://localhost:8001

DB_CONNECTION=sqlite
# OR for PostgreSQL:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=netwrk
# DB_USERNAME=postgres
# DB_PASSWORD=your_password
```

## ✨ What's Working Now

1. ✅ User registration (primary profile)
2. ✅ User login with JWT-like tokens
3. ✅ User profile management
4. ✅ Secondary profile creation
5. ✅ Secondary profile management
6. ✅ Swagger documentation
7. ✅ Laravel MCP for AI Cursor

## 🎉 Ready for Frontend Integration

Your React frontend can now connect to:
- Base URL: `http://localhost:8001/api`
- Authentication: Use `Bearer TOKEN` in Authorization header
- All endpoints documented at `/api/documentation`

The API responses match your existing NestJS format, so minimal frontend changes needed!

## 📞 Need Help?

Use AI Cursor with these prompts:
- "Show me how to add a new endpoint"
- "Help me test the API endpoints"
- "Generate a migration for contacts table"
- "Create a controller for groups management"

Happy coding! 🚀
