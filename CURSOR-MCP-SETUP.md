# Cursor MCP Setup for Netwrk Laravel API

## 🤖 What is Laravel MCP?

Laravel MCP (Model Context Protocol) allows AI assistants like Cursor to directly interact with your Laravel application. This means Cursor can:
- Generate code based on your actual models
- Create migrations that match your database
- Test API endpoints directly
- Understand your project structure

## 📋 Setup Instructions for Cursor

### Step 1: Configure MCP in Cursor

1. Open **Cursor Settings** (Cmd/Ctrl + ,)
2. Go to **Features** → **AI** → **MCP Servers**
3. Click **"Add MCP Server"**
4. Add this configuration:

```json
{
  "mcpServers": {
    "netwrk-laravel": {
      "command": "php",
      "args": [
        "/var/www/html/netwrk-laravel/artisan",
        "mcp:serve",
        "local",
        "netwrk"
      ],
      "cwd": "/var/www/html/netwrk-laravel"
    }
  }
}
```

### Step 2: Create MCP Server (if needed)

```bash
cd /var/www/html/netwrk-laravel
php artisan make:mcp-server NetwrkServer
```

### Step 3: Register MCP Routes

The `routes/ai.php` file is already created. You can add custom MCP tools here.

## 🎯 How to Use MCP with Cursor

### Example Prompts for Cursor with MCP:

#### 1. Generate New Endpoints
```
@netwrk-laravel Create a new ContactsController with CRUD operations 
for managing user contacts. Include:
- Get all contacts
- Create contact
- Update contact
- Delete contact
- Import contacts from CSV

Make sure it follows the same pattern as AuthController
```

#### 2. Create Database Migrations
```
@netwrk-laravel Create a migration for the contacts table based on this structure:
- user_id (foreign key to users)
- first_name, last_name, email
- phone, position, company_name
- tags (JSON array)
- socials (JSON object)
- created_at, updated_at

Include proper indexes and relationships
```

#### 3. Generate Models
```
@netwrk-laravel Create a Contact model with:
- Relationships to User
- UUID primary key
- JSON casts for tags and socials
- Fillable fields
- Search scopes
```

#### 4. Test API Endpoints
```
@netwrk-laravel Test the /api/auth/signup endpoint with:
{
  "email": "test@example.com",
  "password": "password123",
  "first_name": "Test",
  "last_name": "User"
}

Show me the response
```

#### 5. Generate Swagger Documentation
```
@netwrk-laravel Add Swagger annotations to the ContactsController 
following the same format as AuthController
```

#### 6. Create Validation Rules
```
@netwrk-laravel Create a FormRequest for creating contacts with validation:
- email must be unique
- first_name and last_name required
- phone optional but must be valid format
- tags must be array
```

## 🔥 Advanced MCP Features

### 1. Code Generation
```
@netwrk-laravel Convert this NestJS controller to Laravel:

[paste your NestJS controller code]

Make sure to:
- Use Laravel conventions
- Add proper validation
- Include Swagger annotations
- Follow the existing code style
```

### 2. Database Queries
```
@netwrk-laravel Write an Eloquent query to:
- Get all users with their contacts
- Filter by city
- Include contact count
- Sort by created_at
- Paginate results
```

### 3. API Resource Transformation
```
@netwrk-laravel Create UserResource and ContactResource 
for API responses that match the NestJS format:

{
  "user": {...},
  "contacts": [...]
}
```

## 🎨 MCP Prompt Templates

### For Controllers:
```
@netwrk-laravel Generate [ModuleName]Controller with these endpoints:
1. GET /api/[module] - List all
2. POST /api/[module] - Create new
3. GET /api/[module]/{id} - Get single
4. PUT /api/[module]/{id} - Update
5. DELETE /api/[module]/{id} - Delete

Include:
- Sanctum authentication
- Input validation
- Swagger documentation
- Error handling
```

### For Models:
```
@netwrk-laravel Create [ModelName] model with:
- UUID primary key
- Relationships to [RelatedModels]
- JSON casts for [fields]
- Scopes for [common queries]
- Accessors for [computed fields]
```

### For Testing:
```
@netwrk-laravel Create tests for [ControllerName]:
- Test successful creation
- Test validation errors
- Test authentication required
- Test authorization
- Test edge cases
```

## 💡 Pro Tips

### 1. Context-Aware Generation
Cursor with MCP understands your project structure:
```
@netwrk-laravel Add a method to UserController that matches 
the style of existing methods in AuthController
```

### 2. Migration from NestJS
```
@netwrk-laravel I have this NestJS service:
[paste code]

Convert it to Laravel following the project conventions
```

### 3. Bulk Operations
```
@netwrk-laravel Generate all files needed for the Contacts module:
- Migration
- Model
- Controller
- FormRequests
- Resource
- Routes
- Tests
```

## 🔧 Troubleshooting

### MCP Not Working?

1. **Check Server is Running**:
```bash
cd /var/www/html/netwrk-laravel
php artisan mcp:inspector netwrk
```

2. **Regenerate MCP Routes**:
```bash
php artisan vendor:publish --tag=ai-routes --force
```

3. **Clear Cache**:
```bash
php artisan cache:clear
php artisan config:clear
```

### MCP Server Not Found?

Make sure the path in Cursor settings is correct:
```json
{
  "cwd": "/var/www/html/netwrk-laravel"  // ← Check this path
}
```

## 🎯 Next Steps with MCP

Now you can use Cursor to:

1. **Generate Contacts Module** (next chunk):
   ```
   @netwrk-laravel Create the complete Contacts module with 
   all CRUD operations, CSV import, and search functionality
   ```

2. **Generate Groups Module**:
   ```
   @netwrk-laravel Create Groups module for managing user groups
   with member management
   ```

3. **Generate Referrals Module**:
   ```
   @netwrk-laravel Create Referrals module for the introduction system
   ```

## ✨ Benefits of Using MCP

1. **Faster Development**: Generate code in seconds
2. **Consistent Style**: Code matches your existing patterns
3. **Less Errors**: AI understands your project structure
4. **Better Testing**: Test directly through Cursor
5. **Documentation**: Auto-generate Swagger docs

## 🚀 Start Using MCP Now!

Try this prompt in Cursor:
```
@netwrk-laravel Show me the current project structure and suggest 
what we should build next for the 2-week timeline
```

Happy coding with AI-powered Laravel development! 🎉
