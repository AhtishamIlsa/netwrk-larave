# 📧 Loops Email Templates - Complete Integration

## ✅ **All Email Templates Configured**

Your Laravel project now has **complete Loops email integration** with all the templates from your NestJS project!

### 📋 **Email Templates Available**

| Template | Environment Variable | Template ID | Usage |
|----------|---------------------|-------------|-------|
| **OTP Email** | `OTP_TEMPLATE_ID` | `cm38d4o0e0164pskc20sbsx0m` | Sign-up verification |
| **Welcome Email** | `WELCOME_TEMPLATE_ID` | `cm3clt7va01ht11fgdeo1wzcw` | After successful registration |
| **Password Reset** | `PASSWORD_RESET_TEMPLATE_ID` | `cm3e4tctp00epl6waymsdpngr` | Password reset requests |
| **Introduction** | `INTRO_TEMPLATE_ID` | `cm38bbfnj01ejdu5e1mm671dh` | Make introductions |
| **Login Credentials** | `LOGIN_CREDENTIALS_FROM_GROUP` | `cm6jdi1a600o2o5ij2a239r0n` | Group QR login |

### 🔄 **Introduction Email Templates**

| Template | Environment Variable | Template ID | Usage |
|----------|---------------------|-------------|-------|
| **Intro Decline (Introducer)** | `INTRODUCE_FROM_EMAIL_TEMPLATE_DECLINE` | `cm4l1da1604b3otmk0rz9ajvd` | When introduction is declined |
| **Intro Decline (Introduced)** | `INTRODUCE_FROM_EMAIL_TEMPLATE_DECLINE_INTRODUCED_TO` | `cm6hn98wn018113waenf6d7rt` | When introduction is declined |
| **Intro Accept (Introducer)** | `INTRODUCE_FROM_EMAIL_TEMPLATE_ACCEPT` | `cm4l11w6t03ylvnxet0qsc18q` | When introduction is accepted |
| **Intro Accept (Introduced)** | `INTRODUCE_FROM_EMAIL_TEMPLATE_ACCEPT_INTRODUCED_TO` | `cm6hn57ro01m3bbl3swkifu2k` | When introduction is accepted |

### 📨 **Referral Email Templates**

| Template | Environment Variable | Template ID | Usage |
|----------|---------------------|-------------|-------|
| **Reminder** | `REMINDER_TEMPLATE` | `cm6qis3w701gko3ybmzf1wjr0` | Referral reminders |
| **Revoke** | `REVOKE_TEMPLATE` | `cm6je6dqv030id8vazwh1gp9n` | Referral revocation |

### 👥 **Group Email Templates**

| Template | Environment Variable | Template ID | Usage |
|----------|---------------------|-------------|-------|
| **Join Group** | `JOIN_GROUP_EMAIL_TEMPLATE` | `cm5nleakh01h2ounp59rmxjdt` | When someone joins group |
| **Group Invite** | `JOIN_GROUP_INVITE_TEMPLATE` | `cm5zcp2h1001yr0p8w6mgut8b` | Invite contacts to group |
| **Swap Request** | `SWAP_GROUP_REQUEST_EMAIL_TEMPLATE` | `cm6jb6s5f00afqhjzza42u1ou` | Group swap requests |
| **Swap Reject** | `SWAP_GROUP_REQUEST_REJECT_EMAIL_TEMPLATE` | `cm6kfgoyc03t6p07hxokats0c` | Group swap rejections |
| **Referral Group** | `REFERRAL_GROUP_REQUEST_EMAIL_TEMPLATE` | `cmagnc10m6m6zlh8u3r9hz0iv` | Referral group requests |

## 🚀 **How to Use EmailService**

### **1. Basic Email Methods**

```php
use App\Services\EmailService;

// Send OTP email
$emailService->sendOtpEmail($email, $otp, $username);

// Send welcome email
$emailService->sendWelcomeEmail($email, $username);

// Send password reset email
$emailService->sendPasswordResetEmail($email, $resetUrl, $username);

// Send login credentials from group
$emailService->sendLoginCredentialsFromGroupEmail($email, $loginUrl, $username);
```

### **2. Introduction Email Methods**

```php
// Send introduction email
$emailService->sendIntroEmail($email, $dataVariables);

// Send introduction decline emails
$emailService->sendIntroduceFromEmailDecline($introducerEmail, $dataVariables);
$emailService->sendIntroduceFromEmailDeclineIntroducedTo($introducedEmail, $dataVariables);

// Send introduction accept emails
$emailService->sendIntroduceFromEmailAccept($introducerEmail, $dataVariables);
$emailService->sendIntroduceFromEmailAcceptIntroducedTo($introducedEmail, $dataVariables);
```

### **3. Referral Email Methods**

```php
// Send referral reminder
$emailService->sendReminderEmail($email, $dataVariables);

// Send referral revoke
$emailService->sendRevokeEmail($email, $dataVariables);
```

### **4. Group Email Methods**

```php
// Send join group email to creator
$emailService->sendJoinGroupEmail($creatorEmail, $dataVariables);

// Send group invite
$emailService->sendJoinGroupInviteEmail($email, $dataVariables);

// Send swap group request
$emailService->sendSwapGroupRequestEmail($email, $dataVariables);

// Send swap group reject
$emailService->sendSwapGroupRequestRejectEmail($email, $dataVariables);

// Send referral group request
$emailService->sendReferralGroupRequestEmail($email, $dataVariables);
```

## 🧪 **Testing Email Templates**

### **1. Test OTP Email**
```bash
curl -X POST http://localhost:8001/api/auth/sign-up-email-validation \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "firstName": "John",
    "lastName": "Doe"
  }'
```

### **2. Test Welcome Email**
```bash
# Complete the sign-up flow to trigger welcome email
curl -X POST http://localhost:8001/api/auth/user-create \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Password123!",
    "confirmPassword": "Password123!"
  }'
```

### **3. Test Password Reset**
```bash
curl -X POST http://localhost:8001/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com"
  }'
```

## 📝 **Email Template Variables**

### **OTP Email Variables**
```json
{
  "username": "John Doe",
  "verificationCode": "1234"
}
```

### **Welcome Email Variables**
```json
{
  "username": "John Doe"
}
```

### **Password Reset Variables**
```json
{
  "username": "John Doe",
  "resetUrl": "http://localhost:3000/new-password?token=abc123"
}
```

### **Introduction Email Variables**
```json
{
  "introducerName": "Jane Smith",
  "introducedName": "John Doe",
  "introducerEmail": "jane@example.com",
  "introducedEmail": "john@example.com",
  "message": "I'd like to introduce you to John Doe",
  "acceptUrl": "http://localhost:3000/accept-intro?token=abc123",
  "declineUrl": "http://localhost:3000/decline-intro?token=abc123"
}
```

### **Group Email Variables**
```json
{
  "groupName": "Tech Professionals",
  "creatorName": "Jane Smith",
  "memberName": "John Doe",
  "groupUrl": "http://localhost:3000/group/123",
  "joinUrl": "http://localhost:3000/join-group?token=abc123"
}
```

## 🔧 **Configuration**

### **Environment Variables**
Make sure these are set in your `.env` file:

```env
# Loops API Key
LOOPS_API_KEY=13b7923407fa8e2ff839776c709d4f0f

# Email Templates
OTP_TEMPLATE_ID=cm38d4o0e0164pskc20sbsx0m
WELCOME_TEMPLATE_ID=cm3clt7va01ht11fgdeo1wzcw
PASSWORD_RESET_TEMPLATE_ID=cm3e4tctp00epl6waymsdpngr
INTRO_TEMPLATE_ID=cm38bbfnj01ejdu5e1mm671dh
LOGIN_CREDENTIALS_FROM_GROUP=cm6jdi1a600o2o5ij2a239r0n

# Introduction Templates
INTRODUCE_FROM_EMAIL_TEMPLATE_DECLINE=cm4l1da1604b3otmk0rz9ajvd
INTRODUCE_FROM_EMAIL_TEMPLATE_DECLINE_INTRODUCED_TO=cm6hn98wn018113waenf6d7rt
INTRODUCE_FROM_EMAIL_TEMPLATE_ACCEPT=cm4l11w6t03ylvnxet0qsc18q
INTRODUCE_FROM_EMAIL_TEMPLATE_ACCEPT_INTRODUCED_TO=cm6hn57ro01m3bbl3swkifu2k

# Referral Templates
REMINDER_TEMPLATE=cm6qis3w701gko3ybmzf1wjr0
REVOKE_TEMPLATE=cm6je6dqv030id8vazwh1gp9n

# Group Templates
JOIN_GROUP_EMAIL_TEMPLATE=cm5nleakh01h2ounp59rmxjdt
JOIN_GROUP_INVITE_TEMPLATE=cm5zcp2h1001yr0p8w6mgut8b
SWAP_GROUP_REQUEST_EMAIL_TEMPLATE=cm6jb6s5f00afqhjzza42u1ou
SWAP_GROUP_REQUEST_REJECT_EMAIL_TEMPLATE=cm6kfgoyc03t6p07hxokats0c
REFERRAL_GROUP_REQUEST_EMAIL_TEMPLATE=cmagnc10m6m6zlh8u3r9hz0iv
```

### **SMTP Configuration**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.loops.so
MAIL_PORT=587
MAIL_USERNAME=loops
MAIL_PASSWORD=13b7923407fa8e2ff839776c709d4f0f
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@netwrk.com"
MAIL_FROM_NAME="Netwrk"
```

## 🎯 **Ready for All Modules**

Your EmailService is now ready for:

1. ✅ **Authentication Module** - OTP, Welcome, Password Reset
2. ⏳ **Contacts Module** - Group invites, contact sharing
3. ⏳ **Groups Module** - Join notifications, swap requests
4. ⏳ **Referrals Module** - Introduction emails, reminders
5. ⏳ **Make-an-Intro Module** - Introduction workflows

## 🚀 **Next Steps**

When you create the other modules (Contacts, Groups, Referrals), you can use these email methods:

```php
// In ContactsController
$this->emailService->sendJoinGroupInviteEmail($email, $dataVariables);

// In GroupsController  
$this->emailService->sendJoinGroupEmail($creatorEmail, $dataVariables);

// In ReferralsController
$this->emailService->sendIntroEmail($email, $dataVariables);
```

**Your Laravel project now has complete email functionality matching your NestJS implementation! 🎉**
