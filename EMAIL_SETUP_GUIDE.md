# EMAIL SETUP GUIDE - PADDLE RESERVATION

## What Was Wrong?
Your `.env` file was missing, so the email service couldn't connect to Gmail. The application tries to send emails for:
- ✉️ Payment confirmations
- ✉️ Password reset links  
- ✉️ Email verification codes
- ✉️ Refund notifications
- ✉️ Registration confirmations

## How to Fix It (Gmail Setup)

### Step 1: Enable 2-Factor Authentication on Gmail
1. Go to https://myaccount.google.com/security
2. Look for "2-Step Verification" - click it
3. Follow the prompts to enable it
4. **You MUST do this before creating an App Password**

### Step 2: Create a Gmail App Password
1. Go to https://myaccount.google.com/apppasswords
2. Select **"Mail"** as the app
3. Select **"Windows Computer"** (or your device)
4. Google will generate a **16-character password** (example: `abcd efgh ijkl mnop`)
5. **Copy the entire password** (including spaces)

### Step 3: Update Your .env File
Open `paddle-reservation/.env` and fill in:

```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=abcd efgh ijkl mnop
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=your-email@gmail.com
MAIL_FROM_NAME=PaddleGround
```

**Replace:**
- `your-email@gmail.com` with your actual Gmail address
- `abcd efgh ijkl mnop` with the 16-character App Password you just created

### Step 4: Test if Emails Work
1. Go to **Registration page** and register a test account
2. You should receive a **verification code email** within seconds
3. If you don't see it, check your **Spam folder** in Gmail

## Troubleshooting

### "Email not received"
1. ✅ Check SPAM/PROMOTIONS folder in Gmail
2. ✅ Verify the 16-char App Password is correct (no typos)
3. ✅ Make sure 2-Factor Authentication is enabled
4. ✅ Check if the email address in `.env` matches your Gmail account
5. ✅ Wait 10 seconds (sometimes slower on first attempts)

### "Still not working?"
Check error log to see what went wrong:
- Look at browser console (F12 → Console tab)
- Check `paddle-reservation/PHPMailer/mail_error.log` for detailed errors

### Common Errors & Fixes:

**Error: "Invalid credentials"**
- Your App Password is wrong
- Copy it again from https://myaccount.google.com/apppasswords
- Make sure you copied the ENTIRE 16 characters

**Error: "SMTP Auth failed"**
- 2-Factor Authentication is NOT enabled
- Go to https://myaccount.google.com/security and enable it first
- Then create a new App Password

**Error: "Connection timeout"**
- You're behind a firewall blocking port 587
- Ask your IT department or switch to a different network
- Alternative: Contact Brevo for enterprise SMTP

## Alternative: Brevo API (Production Setup)

If you want to use Brevo instead (better for production):

1. Sign up at https://www.brevo.com (free account)
2. Go to Settings → SMTP & API → API Keys
3. Create a new API key
4. In `.env`, add:
```
BREVO_API_KEY=your-brevo-api-key
MAIL_FROM_ADDRESS=your-email@gmail.com
```

The application will automatically try Brevo first, then fall back to Gmail.

## How the Email System Works Now

```
send_smtp_mail() called
    ↓
Try Brevo API (if BREVO_API_KEY is set)
    ↓ If Brevo fails or not configured
Fall back to Gmail SMTP
    ↓
Send email successfully ✅
```

Every email in the system uses the same function, so:
- ✅ Payment confirmations → uses this system
- ✅ Password resets → uses this system  
- ✅ Email verification → uses this system
- ✅ Refund notifications → uses this system

## Files Modified
- Created: `.env` - Your email configuration
- Updated: `PHPMailer/mailer_helper.php` - Added Gmail SMTP support

## Need Help?

If emails still aren't working after following these steps:
1. Open `.env` and verify all values are correct
2. Check `PHPMailer/mail_error.log` for error messages
3. Test with a simple registration to see the exact error
4. Contact your Gmail account support if you're unsure about App Passwords

---
**Last Updated:** 2026-08-12
