# URL Shortener Service

A production-ready URL shortening service built with Laravel, featuring URL shortening, redirection, and analytics tracking. This service uses a single-table design with Redis for caching and queuing, and Spatie Sluggable for generating human-readable aliases.

## Features

- **URL Shortening**: Shorten long URLs via `POST /shorten` with an optional custom alias.
- **Redirection**: Redirect users to the original URL via `GET /{alias}`.
- **Analytics**: Retrieve redirect statistics (total and daily breakdown) via `GET /analytics/{alias}`.
- **Rate Limiting**: Throttles `shorten` and `analytics` endpoints to 60 requests per minute.
- **Caching**: Uses Redis to cache redirect URLs (24-hour TTL) and analytics data (1-hour TTL).
- **Asynchronous Logging**: Redirect logs are queued using Redis for performance.


## System Architecture

### Components
- **Framework**: Laravel (MVC pattern) with RESTful APIs.
- **Database**: MySQL with a single `urls` table storing URL mappings and redirect logs in a JSON column.
- **Caching & Queueing**: Redis for fast URL lookups and asynchronous redirect logging.
- **Slug Generation**: Spatie Sluggable generates unique, slug-style aliases from the domain (e.g., `twitteabcd` from `twitter.com`).
- **Job Queue**: A `LogRedirect` job handles logging redirects asynchronously.

### Database Schema
- **Table**: `urls`
    - `id`: Primary key.
    - `alias`: Unique, 10-character slug (e.g., `twitteabcd`).
    - `original_url`: The full URL to redirect to.
    - `status`: URL status (`active` by default).
    - `redirect_logs`: JSON array of redirect events (e.g., `[{"accessed_at": "2025-03-07 12:00:00", "ip_address": "127.0.0.1"}]`.
    - `created_at`, `updated_at`: Timestamps.
    - **Index**: `alias` for fast lookups.

### Caching Strategy
- **Redirects**: `original_url` cached in Redis for 24 hours (`86400` seconds) using key `url:{alias}`.
- **Analytics**: Statistics cached for 1 hour (`3600` seconds) using key `analytics:{alias}`.
