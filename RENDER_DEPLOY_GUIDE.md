# 🚀 EspoCRM Render.com Deployment Guide

Yeh guide aapko **EspoCRM 10.0.9** ko **Render.com** par aasani se deploy karne ke liye banai gayi hai.

---

## 📁 Tayyar kiye gaye Files

1. **[`Dockerfile`](file:///d:/EspoCRM-10.0.9/Dockerfile)**:
   - Base image: `php:8.3-apache`
   - Required extensions: `pdo_pgsql`, `pdo_mysql`, `gd`, `zip`, `intl`, `bcmath`, `exif`, `opcache`, `mbstring`.
   - Apache modules: `mod_rewrite`, `headers`.
   - Render ke dynamic `$PORT` aur EspoCRM ke routing structure ko handle karta hai.

2. **[`docker-entrypoint.sh`](file:///d:/EspoCRM-10.0.9/docker-entrypoint.sh)**:
   - Render ke `DATABASE_URL` (PostgreSQL / MySQL) ko automatically detect aur parse karta hai.
   - Initial deployment par database schema aur admin user automatically create karta hai.
   - EspoCRM ke zaroori background **Cron jobs (`cron.php`)** ko background me chalata hai.

3. **[`render.yaml`](file:///d:/EspoCRM-10.0.9/render.yaml)**:
   - Render ka Infrastructure Blueprint file. Yeh 1-click me **PostgreSQL Database** aur **Web Service** dono setup kar deta hai.

4. **[`docker/apache.conf`](file:///d:/EspoCRM-10.0.9/docker/apache.conf)** & **[`docker/php.ini`](file:///d:/EspoCRM-10.0.9/docker/php.ini)**:
   - Memory limit (512M), Upload limit (50M), Execution time (180s) aur OPcache optimized settings.

---

## 🛠️ Deployment Steps (Step-by-Step)

### Step 1: Code ko GitHub par Push karein
Apne terminal ya Git GUI me yeh commands run karein:

```bash
git init
git add .
git commit -m "feat: EspoCRM Render deployment setup"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git push -u origin main
```

---

### Step 2: Render par 1-Click Deploy karein (Blueprint Method - Recommended)

1. [Render Dashboard](https://dashboard.render.com/) par login karein.
2. Top right par **New +** par click karein aur **Blueprint** select karein.
3. Apna GitHub repository connect karein.
4. Render automatically `render.yaml` file ko read karega aur dikhayega:
   - **espocrm-app** (Docker Web Service)
   - **espocrm-db** (Managed PostgreSQL Database)
5. **Apply** button par click karein.
6. Render database aur Docker container build karke deploy kar dega!

---

### Step 3: Manual Deploy Method (Alternative)

Agar aap Blueprint use nahi karna chahte aur manually setup karna chahte hain:

1. **PostgreSQL Database banayein:**
   - Render me **New +** -> **PostgreSQL** select karein.
   - Name: `espocrm-db`, Database: `espocrm`, User: `espocrm`.
   - Create karein aur **Internal Database URL** copy karein.

2. **Web Service banayein:**
   - Render me **New +** -> **Web Service** select karein.
   - Apna GitHub repo select karein.
   - **Runtime:** `Docker`.
   - **Environment Variables** add karein:
     - `DATABASE_URL` = *(Aapka PostgreSQL Internal Connection URL)*
     - `ESPOCRM_DATABASE_PLATFORM` = `Postgresql`
     - `ESPOCRM_ADMIN_USERNAME` = `admin`
     - `ESPOCRM_ADMIN_PASSWORD` = `Admin@12345` (ya koi strong password)
   - **Create Web Service** par click karein.

---

## 🔑 Login Credentials

Deployment poora hone ke baad Render aapko ek URL dega (jaise `https://espocrm-app.onrender.com`):

- **Login URL:** `https://your-app-name.onrender.com`
- **Username:** `admin` (ya jo aapne env me set kiya)
- **Password:** `Admin@12345` (ya jo aapne env me set kiya)

> **Tip:** Login karne ke baad Administration -> Users me jaakar turant apna password change kar lein.

---

## 💾 Persistent Storage Note (Data Persistence)
- EspoCRM attachments, uploaded files, aur cache `data/` folder me save karta hai.
- Render ke **Starter Plan** par `render.yaml` automatically 10GB Persistent Disk attach karta hai taaki redeploy hone par bhi uploaded files delete na hon.
- Database (leads, contacts, accounts, settings) Render PostgreSQL me hamesha safe aur persistent rahega.
