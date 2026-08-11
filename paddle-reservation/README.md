# Paddle Ground Reservation

Quick instructions to prepare and push this project to GitHub, and to run it locally.

Setup

1. Copy the example env and fill values:

```bash
cp .env.example .env
# edit .env and set DB and MAIL values
```

2. Install PHP dependencies (Composer required):

```bash
composer install
```

3. Start a local PHP server (or use your preferred environment):

```bash
php -S localhost:8000 -t .
```

Push to GitHub

```bash
git init
git add .
git commit -m "Initial commit"
# create repo on GitHub, then add remote and push
git remote add origin git@github.com:YOUR_USERNAME/YOUR_REPO.git
git branch -M main
git push -u origin main
```

Notes
- The repository includes an example `.env.example`; do NOT commit real secrets. `.env` is listed in `.gitignore`.
- Optionally run `composer require vlucas/phpdotenv` to ensure `.env` support; `config/env.php` already supports both phpdotenv and a simple fallback parser.
