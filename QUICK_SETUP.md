# QUICK FIX CHECKLIST - EMAIL NOTIFICATIONS

## ✅ What I Fixed For You
- [x] Created `.env` file (was missing - this was the root cause!)
- [x] Updated `mailer_helper.php` to support Gmail SMTP with fallback
- [x] Configured email system to work with payment, refunds, password resets, and verification

## 📋 What You Need To Do (5 minutes)

### 1. Enable 2-Factor Auth on Gmail (REQUIRED)
- [ ] Go to https://myaccount.google.com/security
- [ ] Enable "2-Step Verification"
- [ ] Takes 2 minutes

### 2. Generate Gmail App Password
- [ ] Go to https://myaccount.google.com/apppasswords
- [ ] Select "Mail" and "Windows Computer"
- [ ] Copy the 16-character password (example: `abcd efgh ijkl mnop`)
- [ ] Takes 1 minute

### 3. Update `.env` File
- [ ] Open `paddle-reservation/.env`
- [ ] Replace `your-email@gmail.com` with your Gmail address (2 places)
- [ ] Replace `your-16-char-app-password` with the password from Step 2
- [ ] Save the file
- [ ] Takes 2 minutes

## 🧪 Test It Works
1. Go to your application's **Registration page**
2. Register a new test account with an email you can access
3. Wait 5-10 seconds
4. Check your email inbox for verification code
5. If not there, check SPAM/PROMOTIONS folder

## 📧 Emails That Should Work Now
✅ Payment confirmations
✅ Password reset links
✅ Email verification codes
✅ Refund notifications
✅ Registration confirmations

## ⚠️ If Emails Still Don't Work
1. Open `paddle-reservation/PHPMailer/mail_error.log` to see errors
2. Check that `.env` has correct values (no typos, spaces in password)
3. Check Gmail SPAM folder (new apps sometimes flagged as spam)
4. Make sure 2-Factor Authentication is actually ON

## 🆘 Common Issues & Quick Fixes

| Problem | Solution |
|---------|----------|
| "Invalid credentials" error | App Password has typo. Get new one from apppasswords |
| Email goes to SPAM | Mark as "Not Spam" in Gmail. It's normal for first email |
| "SMTP Auth failed" | 2-Factor Auth not enabled. Enable it first |
| Port 587 error | Firewall blocking. Try on different network or contact IT |

## 📁 Important Files
- `.env` ← **Update this with your Gmail credentials**
- `PHPMailer/mailer_helper.php` ← Updated to support Gmail
- `EMAIL_SETUP_GUIDE.md` ← Full detailed guide

---
**Status:** Ready to go! Just need you to fill in your Gmail details in `.env`
