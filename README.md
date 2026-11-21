[![Deploy on Railway](https://railway.com/button.svg)](https://railway.com/new/template/grocy-1?utm_medium=integration&utm_source=button&utm_campaign=grocy)

# Grocy on Railway

[Grocy](https://grocy.info) (MIT) as a Railway template: a self-hosted web app for groceries and household management (stock, best-before dates, shopping lists, recipes, meal plans, chores, tasks and batteries). The service builds from this repository, so when `main` moves to a newer Grocy release, Railway offers the update to everyone who deployed the template.

Version: **Grocy 4.7.1**, image `linuxserver/grocy:v4.7.1-ls352@sha256:ef5ea5094aa2ed32df74528c4bdd94713679038c97add02045d881550c2a59a8` (the image Grocy's own README points to; the official `grocy/grocy-docker` images are archived).

## What it deploys

| Railway service | Source | Role |
|---|---|---|
| `Grocy` | this repo (`Dockerfile`) | nginx + PHP-FPM + Grocy, public domain, SQLite database |

One volume at `/config` holds everything: the SQLite database, `config.php`, uploaded pictures and the nginx/PHP settings. Grocy uses SQLite only, so there is no separate database service.

## Variables

| Variable | Default | Meaning |
|---|---|---|
| `GROCY_ADMIN_PASSWORD` | `${{secret(24)}}` | Password of the first Grocy user, generated per deployment. Find it in the service's Variables tab |
| `GROCY_ADMIN_USERNAME` | `admin` | User name of the first Grocy user |
| `PORT` | `8080` | Port nginx listens on (the public domain targets it) |
| `TZ` | `Etc/UTC` | Time zone for due dates and the PHP clock (optional) |

The deploy form asks for nothing. Any Grocy setting from [`config-dist.php`](https://github.com/grocy/grocy/blob/master/config-dist.php) can also be set as a variable named `GROCY_<SETTING>`, for example `GROCY_CURRENCY=EUR`, `GROCY_DEFAULT_LOCALE=de`, `GROCY_ENERGY_UNIT=kJ` or `GROCY_FEATURE_FLAG_CHORES=false`.

## First steps

1. Open the service's public domain. The first boot creates the database in a few seconds.
2. Sign in with `GROCY_ADMIN_USERNAME` and `GROCY_ADMIN_PASSWORD`. Change the password under the user menu → Manage users if you like: later deploys don't overwrite it.
3. Add locations, products and quantity units, or go through Grocy's settings first (currency, language).

## What this repo changes

Grocy's stock behaviour is unchanged. The wrapper (`root/`) adds one s6 boot step, `init-railway`, that runs before nginx and PHP-FPM start:

- **No `admin`/`admin` login.** Grocy creates its database on the first web request and seeds the user `admin` with the password `admin`. The boot step runs Grocy's own migrations first, with the first user's name and password taken from the variables, so the default password never exists on the public URL. On every boot, any user whose password is still `admin` gets `GROCY_ADMIN_PASSWORD` instead.
- **nginx on `$PORT`** over plain HTTP (Railway terminates TLS), with `X-Forwarded-Proto` passed to PHP so Grocy builds `https://` links.
- **`/healthz`**: answers 200 without a login only when the database exists, is migrated and has a user. Railway's health check uses it.
- **nginx workers and DNS resolver** set for Railway (the image would size nginx from the host's core count, and fall back to a resolver that doesn't exist here).

## Updates

This repo pins the image by version and digest. A new Grocy release lands here as a commit on `main` with a `CHANGELOG.md` entry, and Railway shows deployers an "update available" notice. Read the changelog first. Grocy migrates its database on the next boot; the volume keeps your data. Taking a backup first is wise: Grocy's own backup is the `grocy.db` file in `/config/data`.

## Local test

`docker compose up -d --build`, then open `http://localhost:8080` and sign in as `admin` / `local-test-password`.

Grocy is MIT-licensed; the LinuxServer.io image is GPL-3.0. This template is not affiliated with the Grocy project or LinuxServer.io.
