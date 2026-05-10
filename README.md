# SportTimeWebsite

PHP and MySQL event registration website for sports organizers and participants.

## Features

- User registration and authentication.
- Event creation and participant signup.
- Organizer/admin roles.
- VK OAuth integration.
- Docker-based local development.

## Local Development

```bash
docker compose up
```

The application runs at `http://localhost:8080` and MySQL is exposed on local port `3307`.

Copy `.env.example` to `.env` or provide equivalent environment variables before enabling VK OAuth.

## Security

Production deployment scripts, historical site archives, real tokens, and client-specific production notes are intentionally excluded from this public version.
