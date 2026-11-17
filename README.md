# SecureCell v3 — Prison Management System
### Now with Shift Management · Inventory · Rehabilitation Programs

---

## Project Structure

```
securecell/
│
├── index.php                  ← Full frontend (HTML + JS + PHP entry)
├── securecell.sql             ← MySQL schema + all seed data
│
├── api/
│   ├── auth.php               ← Login / logout / session status
│   └── data.php               ← CRUD API for ALL modules
│
├── includes/
│   ├── config.php             ← DB credentials (edit if needed)
│   └── helpers.php            ← Sessions, PDO, audit, JSON helpers
│
└── .vscode/
    ├── settings.json          ← PHP + HTML editor settings
    ├── launch.json            ← Debug config (Xdebug + built-in server)
    └── extensions.json        ← Recommended VSCode extensions
```

---

## Modules

| Module | Description |
|--------|-------------|
| Dashboard | Live stats, recent admissions, incidents, activity log |
| Prisoners | Full inmate registry with security levels |
| Visitations | Schedule and track in-person / legal / virtual visits |
| Incidents | Severity-graded incident reports with resolution tracking |
| Log In/Out | Movement logging (check-in, check-out, court, medical) |
| Cell Management | Block occupancy and cell status |
| **Shift Management** *(NEW)* | Schedule staff duty shifts, swap requests, supervisor tracking |
| Court Cases | Case numbers, verdicts, hearing dates, fines |
| Reports | Generate typed reports; statistics dashboard |
| Medical Records | Diagnoses, treatments, doctors, status |
| **Rehabilitation** *(NEW)* | Programs (Education, Vocational, Counseling etc.), prisoner enrollments with progress tracking |
| **Inventory** *(NEW)* | Item register, issue/restock/return transactions, low-stock alerts |
| Staff / Users | Staff accounts with bcrypt authentication |
| Audit Log | Full trail of all system actions |

---

## Running with XAMPP (recommended)

### Step 1 — Start XAMPP
Open **XAMPP Control Panel** → Start **Apache** and **MySQL**.

### Step 2 — Copy files
```
C:\xampp\htdocs\securecell\
```
Paste the entire `securecell` folder here.

### Step 3 — Import database
1. Open `http://localhost/phpmyadmin`
2. Click **New** → name it `securecell` → Create
3. Click the `securecell` database → **Import** tab
4. Choose `securecell.sql` → **Go**

### Step 4 — Open in browser
```
http://localhost/securecell/
```

**Default login:** `admin` / `admin123` (re-import the SQL, or update the existing `admin` row, if the database was imported before this fix)

---

## Running with VSCode (built-in PHP server — no XAMPP needed for quick preview)

> ⚠ The built-in PHP server does NOT include MySQL. You still need MySQL running (from XAMPP or a standalone install) for data to work. Use this for quick UI checks only.

### Prerequisites
- **PHP 8.1+** installed and on your PATH
  - Windows: Download from https://windows.php.net/download/ — add to System PATH
  - macOS: `brew install php`
  - Ubuntu/Linux: `sudo apt install php php-mysql`
- **MySQL** running (from XAMPP, or `sudo service mysql start`)

### Step 1 — Open folder in VSCode
```
File → Open Folder → select the securecell folder
```

### Step 2 — Install recommended extensions
VSCode will prompt: "Install recommended extensions?" → Click **Install All**
Or press `Ctrl+Shift+P` → "Extensions: Show Recommended Extensions"

### Step 3 — Configure DB connection
Open `includes/config.php` and set your MySQL credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Add your password if set
define('DB_NAME', 'securecell');
```

### Step 4 — Start PHP built-in server
Open the VSCode **Terminal** (`Ctrl+`` `):
```bash
php -S localhost:8080 -t .
```
Then open: **http://localhost:8080**

### Step 5 — Debug with Xdebug (optional)
1. Install Xdebug: https://xdebug.org/wizard (paste phpinfo output)
2. Add to your `php.ini`:
   ```ini
   zend_extension=xdebug
   xdebug.mode=debug
   xdebug.start_with_request=yes
   xdebug.client_port=9003
   ```
3. Press `F5` in VSCode → select **"Listen for Xdebug (XAMPP)"**
4. Set breakpoints in any `.php` file and reload the browser

---

## New Module Details

### Shift Management
- **Shift Roster tab** — Create and manage shifts per staff member
  - Fields: Staff, Shift Type (Morning / Afternoon / Night / Emergency), Date, Start/End Time, Post/Location, Supervisor, Status
  - Filter by shift type; search by staff name or post
- **Swap Requests tab** — Staff can request shift swaps
  - Fields: Original Shift ID, Requested By, Swap With, Reason, Status (Pending / Approved / Rejected), Approved By
- Shift stats: Today's shifts, currently active, pending swaps

### Inventory
- **Items Register tab** — Full stock catalog
  - Fields: Name, Category (Security / Medical / Food / Uniform / Cleaning / Equipment / Office), Unit, Quantity, Min/Max Qty, Unit Cost (KSH), Supplier, Storage Location
  - Auto-status: Available → Low Stock → Out of Stock based on min quantity
  - Quantity shown in red when at or below reorder point
- **Transactions tab** — Full movement log
  - Types: Issue · Return · Restock · Damaged · Disposed · Transfer
  - Auto-updates item quantity and recalculates status on every transaction
  - Running balance tracked per transaction
- **Low Stock tab** — Quick view of items needing attention
  - One-click Restock button opens transaction form
- ⚠ Orange badge on sidebar nav when items need restocking
- Dashboard shows low-stock count

### Rehabilitation Programs
- **Programs tab** — Manage rehabilitation offerings
  - Types: Education · Vocational · Counseling · Drug Rehab · Religious · Sports
  - Fields: Name, Type, Description, Facilitator, Schedule, Venue, Capacity, Start/End dates, Status
  - Enrollment count tracked automatically; shows red when at capacity
- **Enrollments tab** — Track prisoner participation
  - Fields: Program, Prisoner, Enroll Date, Expected Completion, Progress (%), Grade, Status
  - Visual progress bar per enrollment
  - Status: Active · Completed · Dropped · Suspended
- Enrollment count shown as badge on sidebar nav
- Dashboard shows active rehabilitation enrollments

---

## Default Accounts (password = admin123 for all)

| Username | Role |
|----------|------|
| admin | Administrator |
| warden | Warden |
| officer | Officer |
| medical | Medical |
| faithn | Officer |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Blank page / PHP errors | Check PHP version ≥ 8.0. Add `error_reporting(E_ALL); ini_set('display_errors',1);` to config.php temporarily |
| "Unknown store" API error | Make sure `securecell.sql` was imported — new tables must exist |
| "Not authenticated" | Session expired — log in again |
| Inventory quantities not updating | Check `inventory_transactions` table exists in DB |
| Rehab enrollment count wrong | Run `UPDATE rehab_programs p SET enrolled = (SELECT COUNT(*) FROM rehab_enrollments e WHERE e.programId=p.id AND e.status='Active')` |
| PHP not found in terminal | Add PHP to system PATH; restart VSCode after |
| Port 8080 in use | Use `php -S localhost:9090 -t .` and open :9090 |
