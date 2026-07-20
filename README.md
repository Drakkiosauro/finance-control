<div align="center">

# 💰 Personal Finance Tracker

### Manage debts, credit cards, recurring bills and income — built with vanilla PHP + SQLite

[![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![SQLite](https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](#license)
[![Zero Dependencies](https://img.shields.io/badge/Dependencies-Zero-important?style=for-the-badge)](#)

**Created by [Drakkiosauro](https://github.com/Drakkiosauro)**

</div>

---

A **simple yet complete** web app to organize your personal finances. Built with **vanilla PHP (no framework)** and **SQLite**, featuring a responsive black & white theme, charts and report exports.

> 🎯 Study/portfolio project — focused on **clean code** and **security best practices** (CSRF, hashed passwords, rate limiting, security headers) with **zero external dependencies**.

---

## ✨ Features

| 🔐 | **Secure Auth** | `password_hash` (bcrypt), CSRF protection and login rate limiting |
|----|-----------------|--------------------------------------------------------------------------------|
| 📊 | **Dashboard** | Financial summary, income commitment % and upcoming due dates |
| 💳 | **Debts** | Create, edit, delete and make **partial or full** payments |
| 🔁 | **Recurring Bills** | Auto-repeat every month once paid |
| 🏦 | **Credit Cards** | Limit, closing/due dates and installment purchases |
| 📅 | **Calendar** | Monthly view of due dates |
| 💵 | **Income** | Salary vs debt comparison with charts |
| 📈 | **Reports** | By category and monthly evolution (Chart.js) |
| 🏷️ | **Categories** | Customizable organization |
| 📤 | **Export** | Reports to **CSV** and **PDF** (custom generator, no libs) |
| 💾 | **Backup** | Database backup and restore |

---

## 🛠️ Tech Stack

<div align="center">

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8+ (no framework) |
| **Database** | SQLite (PDO) |
| **Frontend** | Plain HTML + CSS (responsive B&W theme) |
| **Charts** | Chart.js (CDN) |
| **PDF** | Custom class (`lib/PDF.php`) |

</div>

---

## 🚀 Getting Started

### ⚡ Option 1 — Built-in PHP server (easiest)

```bash
# 1. Make sure PHP 8+ is in your PATH
php -v

# 2. From the project folder:
php -S localhost:8080
#    (or just run iniciar.bat on Windows)

# 3. Open and create your user:
#    http://localhost:8080/setup.php
```

### 🪟 Option 2 — XAMPP / Apache

```bash
# 1. Copy the folder into htdocs/
# 2. Open:
#    http://localhost/PROJECT_FOLDER/setup.php
```

> 🔒 **Important:** after setup, **delete `setup.php`** for security.

---

## 📂 Project Structure

```
📦 personal-finance-tracker
├── 📄 index.php           Login
├── 📄 dashboard.php       Financial summary
├── 📄 dividas.php         Debt management
├── 📄 divida_form.php     Create / Edit
├── 📄 pagamento_form.php  Register payment
├── 📄 contas_fixas.php   Recurring bills
├── 📄 cartoes.php         Credit cards
├── 📄 compra_form.php     Card purchases
├── 📄 calendario.php      Due date calendar
├── 📄 renda.php           Salary / income
├── 📄 relatorios.php      Reports & charts
├── 📄 categorias.php      Categories
├── 📄 backup.php          Backup / restore
├── 📄 exportar_csv.php    CSV export
├── 📄 exportar_pdf.php    PDF export
├── 📄 setup.php           Initial setup (remove after use)
├── 📄 logout.php
├── 📁 config/
│   └── 📄 database.php    SQLite connection + schema
├── 📁 includes/
│   ├── 📄 functions.php   Business logic
│   ├── 📄 security.php    CSRF, rate limit, 2FA, headers
│   ├── 📄 header.php / footer.php
│   └── 📄 header_simple.php / footer_simple.php
├── 📁 lib/
│   └── 📄 PDF.php         Custom PDF generator
├── 📁 sql/
│   └── 📄 schema.sql      Database schema
├── 📁 assets/
│   ├── 📄 css/style.css
│   └── 📄 js/script.js
├── 📁 data/               SQLite DB (gitignored)
└── 📄 iniciar.bat         PHP server shortcut (Windows)
```

---

## 🛡️ Security

- 🔑 Passwords hashed with `password_hash` (bcrypt)
- 🧾 **CSRF** protection on all forms
- ⏱️ Login **rate limiting** (5 attempts / 15 min)
- 📋 Security headers (`X-Frame-Options`, `X-Content-Type-Options`, ...)
- 🗄️ Database kept **outside web root** (`data/`), blocked via `.htaccess`/`web.config`
- 🧹 Output escaping (`htmlspecialchars` / `json_encode`)
- 🔐 **2FA (TOTP)** support prepared in the security module

---

## 📜 License

Free to use for study and portfolio purposes.

<div align="center">

**⭐ If you like this project, leave a star on GitHub!**

Made with 💙 by **[Drakkiosauro](https://github.com/Drakkiosauro)**

</div>
