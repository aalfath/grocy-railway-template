# Changelog

Breaking changes are listed first in each entry. Read the entry before you apply an update on Railway.

## 4.7.1 (2026-11-21): first release

- Grocy v4.7.1 from `linuxserver/grocy:v4.7.1-ls352`, pinned by digest `sha256:ef5ea5094aa2ed32df74528c4bdd94713679038c97add02045d881550c2a59a8`.
- First boot creates the database and the first user from `GROCY_ADMIN_USERNAME` / `GROCY_ADMIN_PASSWORD` (no `admin`/`admin`).
- nginx on `$PORT`, health check at `/healthz`, all state on one volume at `/config`.
