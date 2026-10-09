# Kusina ng Barangay — MidtermMilestoneProj-Lastname

A recipe-sharing site for our barangay's home cooks, built with plain PHP 8.0+ (sessions, OOP, PDO),
HTML, CSS and JavaScript. No frameworks.

**Theme:** Filipino *lutong bahay* — banana-leaf green, palayok clay, and *banig* weave accents.

## Setup (XAMPP)
1. Copy the folder to `htdocs/MidtermMilestoneProj-Lastname`.
2. phpMyAdmin → **Import** → `sql/schema.sql` (creates `kusina_ng_barangay`, the 6 tables, and starting categories).
3. If your MySQL login isn't `root` with no password, edit the constants in `db.php`.
4. Open `http://localhost/MidtermMilestoneProj-Lastname/register.php`.

## Database design (exactly 6 tables)
| Table | Keys & constraints |
|---|---|
| `users` | PK `id`; `email` UNIQUE; only `password_hash` stored |
| `categories` | PK `id`; `name` UNIQUE; seeded with 9 Filipino categories |
| `recipes` | FK `user_id` → users (CASCADE); FK `category_id` → categories (RESTRICT, so a used category can't vanish); CHECKs on servings/cook time; `updated_at` NULL until edited |
| `ingredients` | FK `recipe_id` → recipes (CASCADE); UNIQUE(`recipe_id`, `position`) keeps the member's order |
| `comments` | FKs to recipes and users (CASCADE); `updated_at` NULL until edited |
| `favorites` | Composite PK (`user_id`, `recipe_id`) so a recipe can be saved only once; both FKs CASCADE |

Relationships: a user has many recipes, comments, and favorites; a category has many recipes;
a recipe has many ingredients and comments; users ↔ recipes is many-to-many through `favorites`.

## Classes (`src/`)
| Class | Job |
|---|---|
| `Database` (`db.php`) | The single PDO connection (exceptions on, real prepared statements) |
| `Session` | Session start/regenerate/destroy, flash messages, CSRF tokens |
| `Auth` | Login state; `requireLogin()` for member pages, `requireGuest()` for login/register |
| `Validator` | Chainable server-side rules, one message per field |
| `RecipeInput` | Reads + validates the recipe form (including the dynamic ingredient list) |
| `RecipeFilter` | Keyword / category / "my recipes" options for the listing |
| `UserRepository`, `CategoryRepository`, `RecipeRepository`, `CommentRepository`, `FavoriteRepository` | All SQL lives here, always with bound parameters |

## Transactions
- **Create recipe** — recipe row + every ingredient row commit together or roll back together.
- **Edit recipe** — row lock (`SELECT … FOR UPDATE`) + ownership check, update recipe, replace ingredients, commit.
- **Toggle favorite** — locked check-then-insert/delete + updated count in one transaction (safe against double-clicks).

## Security checklist
- Passwords: `password_hash()` / `password_verify()`; session ID regenerated on login.
- SQL injection: every query with user input uses prepared statements; LIMIT/OFFSET bound as integers; LIKE wildcards escaped.
- XSS: every user value printed through `e()` (`htmlspecialchars`, ENT_QUOTES, UTF-8).
- CSRF token on every POST form and on the AJAX request (`X-CSRF-Token` header).
- Ownership enforced twice: the page checks the owner, and the SQL `WHERE … AND user_id = ?` does too.

## Validation rules (browser + server)
| Field | Rule |
|---|---|
| Name | 2–60 chars; letters, spaces, `.` `'` `-` |
| Email | valid format, ≤120 chars, not already registered |
| Password | 8–72 chars, ≥1 letter and ≥1 number; confirmation must match |
| Recipe title | 3–120 chars |
| Description | 10–500 chars |
| Category | must be one of the listed categories |
| Servings / cooking time | whole numbers 1–50 / 1–1440 minutes |
| Ingredients | 1–40 rows; name ≤100 chars (required if an amount is given); amount ≤40 chars |
| Steps | 20–8000 chars, one step per line |
| Comment | 2–1000 chars |
| Search keyword | ≤80 chars |

## Features
- Register, log in, log out; guests are redirected to login.
- Share a recipe with title, description, category, servings, cooking time, dynamic ingredient rows, and steps.
- Latest-first listing with keyword search (title, description **or ingredient**), category filter, pagination.
- Recipe page with ingredients checklist, numbered steps, and comments ("Cook" badge on the author's replies).
- Favorites saved/removed with `fetch()` — no page reload.
- Edit/delete own recipes and comments; edited items show an **Edited** tag with the time.
- **Own feature — "Ano'ng ulam?"**: a random-recipe picker (respects the selected category) for when nobody can decide what to cook.
