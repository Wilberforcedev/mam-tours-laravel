# Deployment guide retired

This guide is obsolete and must not be used to deploy the current application. Its Railway, MySQL, PHP built-in server, and `migrate:fresh` instructions do not match the repository's current Docker and PostgreSQL setup. In particular, `migrate:fresh` deletes all database tables and data.

Use the current guides instead:

- [Render quick start](RENDER_QUICK_START.md)
- [Render deployment guide](RENDER_DEPLOYMENT.md)
- [Production readiness remediation and known limitations](docs/PRODUCTION_READINESS_REMEDIATION.md)

The current Render Blueprint is [render.yaml](render.yaml). Review that file before creating or changing hosting resources.
