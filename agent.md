# MASTER SYSTEM CONTEXT & DEVELOPER GUIDELINES: E-PROCUREMENT SYSTEM BNI (E-PROC-BNI)

---

## 1. PROJECT OVERVIEW & METADATA
* **System Name:** E-Procurement BNI (Corporate Procurement & Budget Control System).
* **Development Methodology:** Rapid Application Development (RAD) method.
* **Academic & Institutional Context:** Developed as a final project and thesis by a final-year Information Systems student at UPN Veteran Jakarta.
* **Core Objective:** To build an automated corporate procurement platform featuring strict budget enforcement, smart categorization, and document audit trails.
* **Strict Scope Boundary:** User-Centered Design (UCD) is completely no longer relevant and has been intentionally eliminated from the system's narrative, design focus, and codebase documentation.

---

## 2. SYSTEM ARCHITECTURE & TECHNICAL STACK
* **Backend Framework:** Laravel (PHP Engine) handling full-stack enterprise operations, routing, data models, and business controllers.
* **Frontend Implementation:** Built using Laravel Breeze Starter Kit utilizing standard Blade templates and Alpine.js for lightweight, reactive frontend interactivity.
* **Database Infrastructure:** Migrated from local execution to cloud-managed PostgreSQL powered by Supabase, provisioned specifically in the Tokyo Region (`ap-northeast-1`).
* **Database Connection Protocol:** Configured strictly via the Session Pooler interface on Port `5432` to guarantee complete compatibility with Laravel's Prepared Statements and asynchronous PDO operations, eliminating connection drop-offs.
* **Cloud Storage Architecture:** Integrated with Supabase Storage via the standard AWS S3 Driver filesystem extension (`league/flysystem-aws-s3-v3`), targeting a dedicated public bucket named `eprocurement-storage`.
* **Automated Testing Suite:** Powered by the Pest Testing Framework, using clean functional syntax (`it()` and `test()` wrappers) to enforce validation logic stability without traditional PHPUnit class overhead.

---

## 3. MASTER ENVIRONMENT VARIABLES (`.ENV` REFERENCE STRUCTURE)
The AI Agent must always reference and expect the following environment configurations across local development machines and GitHub Actions pipelines:

```env
APP_NAME="E-Procurement BNI"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# SUPABASE CLOUD POSTGRESQL (VIA SESSION POOLER)
DB_CONNECTION=pgsql
DB_HOST=aws-1-ap-northeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.cripnthgmzrrpkowumza
DB_PASSWORD=hidden_production_credential

# SUPABASE STORAGE VIA AWS S3 DRIVER PROTOCOL
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=hidden_cryptographic_access_key
AWS_SECRET_ACCESS_KEY=hidden_cryptographic_secret_key
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=eprocurement-storage
AWS_ENDPOINT=https://cripnthgmzrrpkowumza.supabase.co/storage/v1/s3
AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

## 4. CORE BUSINESS LOGIC: THE 4-GATE SMART VALIDATION ENGINE
Every procurement application or ticket handled by the platform must successfully clear the four distinct verification gates before changing state to an approved or actionable corporate workflow:

### Gate 1: Budget Checking & Smart Locking (Pagu Divisi)
* The platform evaluates the requesting officer's division assignment and cross-references the proposed transaction value against the division's remaining yearly budget limit.
* If the requested `budget_estimated` value exceeds the available divisional balance, the transaction must be blocked instantly, throwing an explicit validation error exception.
* If the budget check passes, the system applies a smart lock mechanism, transitioning the record status to `budget_locked` and isolating the requested amount to protect against concurrent double-spending race conditions.

### Gate 2: Automated CAPEX / OPEX Expenditure Classification
* The system intercepts the item categorization and cost data during the ingestion payload phase to automatically determine accounting classification.
* **CAPEX (Capital Expenditure):** Auto-assigned if the item falls under major infrastructure, permanent asset procurement, or hardware acquisition, or if the estimated budget passes a set enterprise valuation threshold.
* **OPEX (Operational Expenditure):** Auto-assigned for routine software licensing, minor office utility acquisitions, temporary service provisioning, and maintenance workloads below the enterprise monetary threshold.
* The classification result is automatically written directly to the database field without prompting manual user designation or input selection.

### Gate 3: Vendor & Requester Eligibility Verification
* The system runs compliance checks ensuring the designated vendor company exists within BNI's active verified partner registry.
* It concurrently ensures that the requesting user maintains active operational clearance credentials within the system lifecycle.

### Gate 4: Document Completeness Validation (Izin Prinsip Upload)
* The submitting officer is required to upload a mandatory administrative compliance document known as the "Izin Prinsip" strictly formatted as a PDF.
* The uploaded document stream is automatically dispatched to the cloud-managed Supabase S3 bucket (`eprocurement-storage`).
* The generated absolute secure public URL must be written directly to the `document_path` database column; requests missing a valid path are permanently held in a `draft` state.

---

## 5. DATABASE SCHEMA BLUEPRINT & FIELD MAPS
The application models and migration maps must reflect the following architectural configurations:

### A. Model Definition: `Ticket`
* `id`: BigInteger, Primary Key, Auto-Increment.
* `user_id`: ForeignKey linked to `users.id` with a cascade-on-delete constraint.
* `division_id`: ForeignKey linked to corporate divisional ledger structures.
* `title`: String (max 255), identifying the core procurement item or service title.
* `description`: Text fields, housing technical specifications and requirement breakdowns.
* `budget_estimated`: Decimal (15, 2), managing highly precise corporate monetary parameters.
* `expenditure_type`: Enum string values (`CAPEX`, `OPEX`), managed by the automated classification engine.
* `vendor_name`: String, identifying the targeted external enterprise supplier.
* `document_path`: String, recording the secure S3 cloud storage public link to the uploaded "Izin Prinsip" PDF.
* `status`: Enum string layout (`draft`, `pending_validation`, `budget_locked`, `approved`, `rejected`), defaulting to a `draft` status code.
* `timestamps`: Standard Eloquent `created_at` and `updated_at` tracking parameters.

### B. Relational Mapping
* **User Relationship:** A `User` model possesses a `hasMany` relationship to the `Ticket` model; a `Ticket` inherently `belongsTo` a specific `User`.
* **Division Relationship:** A corporate `Division` model possesses a `hasMany` relationship to `Ticket` references; each `Ticket` explicitly `belongsTo` its originating `Division`.

---

## 6. MULTI-DEVELOPER COLLABORATION & ENVIRONMENT SYNC PROTOCOL
To maintain complete development synchronization across separated local environments and continuous integration pipelines without leaking cryptographic keys:
* **Secrets Isolation:** The local `.env` configuration file is strictly isolated via `.gitignore` and must never be committed to the remote repository.
* **Blueprint Standardization:** The `.env.example` file serves as the singular structural template for all team members. Any framework package addition that introduces configuration variables must immediately be reflected in `.env.example` using blank placeholders.
* **Team Initialization Sequence:**
  1. Fetch the absolute clean state via `git pull origin main`.
  2. Duplicate the structural environment schema using `cp .env.example .env`.
  3. Securely reference and insert the explicit database passwords and S3 cryptographic access credentials shared via direct encrypted communication channels.
  4. Execute package tracking updates via `composer install` and `npm install`.
  5. Generate the application cryptographic signature string using `php artisan key:generate`.
  6. Under no circumstances should destructive commands like `php artisan migrate:fresh` be run against the active cloud server without multi-developer alignment.

---

## 7. SYSTEM CODING & AUTOMATED TESTING GUIDELINES
When writing controllers, validating requests, or compiling testing architectures, the following structures must be enforced:

### A. Pest Testing Blueprint
All functional testing vectors must be developed within the `tests/` directory utilizing the Pest functional chain API. Avoid traditional class declarations.

```php
<?php

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gate 1 smart validation halts submission when division budget limit is breached', function () {
    // Arrange: Seed user context bound to a division with restricted allocation
    $user = User::factory()->create(['division_id' => 1]);
    
    // Act: Execute post request attempting to break the budget ceiling
    $response = $this->actingAs($user)
                     ->post('/tickets', [
                         'title' => 'Core Infrastructure Mainframe Asset',
                         'budget_estimated' => 950000000.00,
                         'vendor_name' => 'PT Enterprise Vendor Utama',
                     ]);

    // Assert: Verify validation session errors are caught and database remains clean
    $response->assertSessionHasErrors(['budget_estimated']);
    expect(Ticket::count())->toBe(0);
});
```

### B. Controller & Layer Separation Design
* **Slim Controllers:** HTTP controllers must remain streamlined, offloading high-complexity validation rules or multi-step database procedures to standalone request maps or corporate services.
* **Dedicated Service Layer:** The 4-Gate Smart Validation Engine procedures should be written inside a specialized class file located at `app/Services/ProcurementValidationService.php` to ensure the core logic remains decoupled and completely testable via Pest.
* **Data Validation:** Route input filtering must be handled via decoupled Form Request implementations (e.g., `app/Http/Requests/StoreTicketRequest.php`).