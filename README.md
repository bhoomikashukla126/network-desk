# Network Desk

Standalone Laravel extension for ISP cable and fiber network mapping — plot routers, splitters, junction boxes, and cable segments on an interactive map with reference photos and dashboard filters.

## Local setup

```bash
createdb network_desk   # PostgreSQL
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
composer dev            # http://127.0.0.1:3009
```

Optional local dev without central SSO:

```env
LOCAL_DEV_AUTH=true
CLIENT_ID=
CLIENT_SECRET=
DB_CONNECTION=sqlite
```

Register on central:

```bash
cd ../Laravel-project
php artisan extensions:configure network-desk \
  --base-url=http://127.0.0.1:3009 \
  --name="Network Desk" \
  --description="ISP cable and fiber network mapping" \
  --icon="🗺️"
php artisan extensions:show-credentials network-desk
```

## Laravel Cloud bucket

Attach a **public** bucket named **`public`** for cable and point photos. Same setup as Menu Card — see `../../Laravel-project/DEPLOYMENT.md`.
