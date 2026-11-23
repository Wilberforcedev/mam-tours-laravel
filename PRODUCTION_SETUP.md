# Production setup guide retired

This checklist is obsolete. It contains environment, payment, scheduler, backup, and storage instructions that do not match the current deployment configuration. Do not use its sample credentials, old webhook paths, or local MySQL backup commands for a production deployment.

The supported deployment guide in this repository is Render:

- [Render quick start](RENDER_QUICK_START.md)
- [Render deployment guide](RENDER_DEPLOYMENT.md)
- [Production deployment checklist](PRE_DEPLOYMENT_CHECKLIST.md)
- [Production readiness remediation and known limitations](docs/PRODUCTION_READINESS_REMEDIATION.md)

The deployment uses Docker, PostgreSQL, a persistent disk for uploads, and Supervisor for Apache, Laravel's scheduler, and the database queue worker. The application uses Laravel 8, so upgrade it to a supported Laravel release and pass CI and staging acceptance before launch.
