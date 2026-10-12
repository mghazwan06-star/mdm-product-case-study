# WorkWize MDM Device Sync

A Laravel + React app that imports assigned laptops and their owners from Jamf (a mock API response) into MySQL, and shows them in a website.

- The assignment brief from WorkWize: [BRIEF.md](BRIEF.md)
- Our decisions and assumptions: [docs/DECISIONS.md](docs/DECISIONS.md)

## Start the app

You only need [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
docker compose up
```

The first start takes a few minutes (Docker downloads MySQL, PHP and Node and installs the libraries). After that it starts in under a minute.

When it's ready, open:

| What | Address |
|---|---|
| Website | http://localhost:5173 |
| Backend health check | http://localhost:8000/api/health |

## Stop or reset

```bash
docker compose down        # stop
docker compose down -v     # stop and delete the database (fresh start next time)
```

## Run the tests

With the app running:

```bash
docker compose exec backend php artisan test
```

## Project layout

| Folder | What's in it |
|---|---|
| `backend/` | Laravel API (PHP 8.4, Laravel 13.35) |
| `frontend/` | React website (React 19, Vite) |
| `files/` | WorkWize's original files: the Jamf mock response, the reference sync code, the wireframe |
| `docs/` | Logbook: changes, decisions, mistakes, ideas and limitations, tests |

## Notes

- Local development setup only. The passwords in `docker-compose.yml` are local-only values.
- WorkWize's template link returned a 404, so this repo was created fresh and the template's files were copied in unchanged.
