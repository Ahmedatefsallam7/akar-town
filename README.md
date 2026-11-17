# Akar Town

A real-estate website (properties, projects, developers, locations) with its admin panel. English and Arabic.

**Stack:** Laravel 13 · Filament 5 (admin) · Livewire 4 + Alpine + Tailwind v4 (website, TALL only) · nwidart modules ·
spatie medialibrary and laravel-settings · MySQL.

## Run it locally

```sh
docker compose up -d                      # app (PHP), web (nginx), db, redis, phpmyadmin
docker exec akar-town-app php artisan migrate
npm install && npm run build              # website and admin CSS/JS (run on your machine)
```

| URL | What |
| --- | --- |
| <http://localhost:8090> | Website |
| <http://localhost:8090/admin> | Admin panel |
| <http://localhost:8091> | phpMyAdmin (database on port 3307) |

- Run artisan, composer and the tests **inside the container** (`docker exec akar-town-app …`). The container and your
  machine have their own `vendor/`: a new Composer package goes in both.
- Tests: `docker exec akar-town-app php artisan test --compact`

## Where things live

The **admin** is split into modules (one per business area). The **website** is one layer in `app/` and `resources/`,
reading the modules' models.

| Folder | What's in it |
| --- | --- |
| `Modules/<Name>/` | One business area: its model, migrations, seeders, admin resource (`app/Filament`), tests. Modules: Units (properties), Projects, Developers, Locations, Galleries, Lookups, Users, WebsiteUsers, UnitRequests (sell requests), Inquiry (contact + meetings), Careers, PossibleLeads, Sliders, GeneralSettings (the settings pages) |
| `app/Http/Controllers/Website/` | Website pages that need a controller (the others are `Route::view` in `routes/web.php`) |
| `app/Livewire/Website/` | The website's interactive parts (search, forms, sign-in window); shared pieces in `Concerns/` |
| `app/Support/Website/` | What a website page shows, ready for the view: `XCards` (lists), `XDetails` (one record), `XSearch` (filters), `XPdf` (brochures) |
| `resources/views/website/`, `components/website/`, `livewire/website/` | The website's views |
| `app/Support/Images/` | Every image's sizes (Settings → Image Sizes), how they're written, how an upload is checked |
| `app/Support/Crm/` | Sending leads to the CRM |
| `app/Filament/` | What admin modules share: `Concerns/` (traits), `Support/` (helpers), `Forms/` (field builders), `Contracts/` |
| `app/Models/Concerns/` | Traits shared by models (slug, translations, search name) |
| `app/Rules/` | Validation rules |
| `lang/` | `ar.json`: website texts (`__('English text')`). `en/admin.php`, `ar/admin.php`: admin texts |

Naming: traits go in a `Concerns/` folder, helpers in a `Support/` folder.

## How to add…

- **An admin area:** `php artisan module:make Name` (keep the whole scaffold, even empty folders). Its Filament resource
  in `Modules/Name/app/Filament/Resources` is found by itself. Use `HasResourceTranslations` for its labels and
  permission: it then appears in the roles form, and the Admin role gets it after `migrate`.
- **A website page:** a route in `routes/web.php`, the data in `app/Support/Website/`, the view in
  `resources/views/website/`, interactive parts as Livewire components in `app/Livewire/Website/`.
- **An image upload field:** add the field (and the places that show it) to `App\Support\Images\ImageSizes`, then wrap
  the admin field in `ImageRules::apply($field, 'key')` (a test fails if you forget). A website form validates with
  `ImageValidation::rules('key')`. Its sizes then show on Settings → Image Sizes, where an admin can change them.
- **Website images are protected** (AKAR-51): uploads live on the private `media` disk and the website shows them
  through short-lived signed links (`App\Support\Images\ProtectedImages`), never an original. The watermark on the
  photos is switched on or off at once with `IMAGES_WATERMARK=true|false` in `.env`.
- **Text:** never hard-code it. Admin: `__('admin.…')` in `lang/{en,ar}/admin.php`. Website: `__('English text')` plus
  the Arabic in `lang/ar.json`.

## Rules of the project

- **Website = TALL only:** Tailwind utilities, Alpine, Livewire. No hand-written CSS, no inline `style=` values, no
  JavaScript outside the Vite entries.
- **Every change ships with tests** (`Modules/*/tests`, `tests/Feature`). Pages have query budgets
  (`tests/Feature/Performance`): a change that adds queries fails them.
- **Fast with big data:** no N+1 queries, cache what every page reads, paginate.
- **Going live (cPanel):** no Node, Redis or Supervisor on the server, so build the assets locally, and use the
  database for the cache and the queue.
