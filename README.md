# Empy — Employee Performance Evaluation

A Laravel web application for employee performance evaluation. HRD manages the organization and evaluation setup, managers score their team on a 1–5 scale against weighted criteria, peers can contribute 360° feedback, and employees can sign in to see their own results. English is the default interface language, with an optional Indonesian translation.

## Features

- **Authentication & roles** — Super Admin (hidden system role), Administrator (HRD), Manager, and Employee, plus password reset, login rate limiting, and an admin user-management page.
- **Account settings** — Click your profile card to open a settings modal with avatar upload and cropping, name, email, and password changes.
- **User access (Hak Pengguna)** — A super-admin-only matrix that grants or blocks each page per role. Super admins always retain full access.
- **Brand settings** — A super-admin-only page for the application name, tagline, and logo.
- **Activity history** — Every recorded data change, with before/after values and one-click **Revert Change** or **Permanently Delete** for removed records.
- **Organization management** — departments (with a department head), positions, employees with photos, reporting lines, and optional employee login accounts.
- **Evaluation setup** — evaluation periods (monthly, quarterly, semester, annual) with one active period at a time, plus weighted criteria.
- **Scoring** — managers score each criterion on a 1–5 scale with per-criterion comments; the final score is a weighted average on a 0–100 scale with a live preview while scoring.
- **KPI targets** — per-employee, per-period targets with realization and automatic achievement percentage (reference data for the manager).
- **360° feedback** — managers invite peers/subordinates to rate the employee; the final score becomes 70% manager + 30% average peer score. Invitations and submissions are tracked.
- **Improvement plans (PIP)** — follow-up plans with actions, due dates, and statuses; low scores (< 60) are flagged.
- **Reminders** — scheduled daily email reminders to managers with unevaluated team members, plus a manual "Send Reminders" action on the periods page.
- **Dashboards** — role-aware statistics, period filter, average score per department, trend charts, top performers, recent evaluations, pending 360° feedback, and open improvement plans.
- **Reports** — ranking, averages per department and criterion, grade distribution, completion rate, unevaluated employees, and a rater calibration view.
- **Exports** — company report PDF/Excel and a signed single-evaluation PDF.
- **Employee import** — CSV upload with a downloadable template; rows with duplicate NIPs are skipped and invalid rows reported.
- **Audit log** — searchable trail of data changes and evaluation submissions.
- **Organization chart** — department heads and team composition.
- **Archiving** — employees and evaluation periods are soft-deleted and can be restored.
- **Localization** — Indonesian and English, switchable from the sidebar/topbar and login page.

## Roles

| Role | Can do |
| --- | --- |
| Super Admin | Everything, including User Access and Brand Settings. Hidden from every other role. |
| Administrator | Everything except the super-admin-only pages; super-admin accounts are invisible to them. |
| Manager | View and evaluate only their direct reports, manage their KPI targets, view team dashboards and reports |
| Employee | View their own profile, KPI targets, improvement plans, evaluation results, and submit 360° feedback when invited |

## Scoring

Each active criterion has a weight (recommended total 100%). Per criterion the score is `1–5`.

```
manager score = Σ (score ÷ 5 × weight) ÷ Σ weight × 100
final score   = 70% manager score + 30% peer average   (when 360° feedback exists)
```

Grades: **A** ≥ 90 (Sangat Baik), **B** ≥ 80 (Baik), **C** ≥ 70 (Cukup), **D** ≥ 60 (Kurang), **E** < 60 (Sangat Kurang).

## Tech stack

- Laravel 12 (PHP 8.2+), MySQL
- Blade + Tailwind CSS 4 + Chart.js (via Vite)
- barryvdh/laravel-dompdf, phpoffice/phpspreadsheet

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# configure the database and APP_URL in .env, then:
php artisan migrate --seed
php artisan storage:link
npm install
npm run build

php artisan serve
```

Open the URL configured in `APP_URL` (default `http://127.0.0.1:8000`).

### Demo accounts

All demo accounts use the password `kai250510`.

| Email | Role |
| --- | --- |
| `kai@example.com` | Super Admin (hidden) |
| `noah@example.com` | Administrator |
| `chips@example.com` | Manager (Operations) |
| `tweek@example.com` | Employee (Operations) |
| `craig@example.com` | Employee (Operations) |

The seeder creates 15 employees across 5 departments, department heads, 5 weighted criteria, four evaluation periods, KPI goals, one improvement plan, and evaluation history so dashboards and reports have data immediately. The active period intentionally leaves staff unevaluated to demonstrate pending work and reminders.

## Commands

```bash
php artisan evaluations:remind          # email managers with pending evaluations
php artisan evaluations:remind --period=4
php artisan schedule:list               # daily 08:00 reminder is scheduled
```

Email uses the `MAIL_MAILER` from `.env` (the default `log` driver writes messages to `storage/logs/laravel.log`).

## Tests

```bash
php artisan test
```

The suite covers role restrictions, weighted scoring, 360° recalculation, KPI goal permissions, CSV import, archiving/restoring, reminders, improvement plans, user management, login throttling, password reset, reports, exports, and locale switching.

## Project structure

```
app/Http/Controllers      Dashboard, employees, goals, organization, periods, criteria, evaluations, feedback, plans, reports, users, audit, auth,
                          profile, access matrix, brand settings
app/Models                Employee, Department, Position, EvaluationPeriod, Criterion, Evaluation, EvaluationScore,
                          EmployeeGoal, PeerFeedback, ImprovementPlan, ActivityLog, RolePermission, Setting
app/Support               ActivityLogger, Brand, PageAccess
app/Http/Middleware       EnsureRole, EnsurePageAccess, SetLocale
app/Notifications         EvaluationReminderNotification
app/Console/Commands      SendEvaluationReminders
database/migrations       Organization and evaluation schema, profile/branding/access schema
database/seeders          English demo data
lang/{id,en}/app.php      UI translations (English is the default)
resources/views           Blade views (dashboard, CRUD, scoring form, reports, settings, profile modal, PDF templates)
```
