# Contributing to Azshrtr

Thanks for helping improve Azshrtr.

Please read the [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to follow it.

## Development

1. Fork and clone the repo
2. Copy `.env.example` → `.env` and configure MariaDB (see README)
3. `composer install && npm install`
4. `php artisan azshrtr:setup`
5. `npm run dev` (or `npm run build` for production assets)

## Quality checks (required for PRs)

```bash
./vendor/bin/pint --test
npm run typecheck
npm run lint
composer test
```

Keep `VERSION` and `package.json` `"version"` in sync when bumping SemVer releases.

## Pull requests

- Open PRs against `main`
- Prefer small, focused PRs
- Include a short summary of *why*
- Prefer [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `docs:`, `chore:`)
- Do not commit `.env`, secrets, or production credentials

## Security

Report vulnerabilities privately — see [SECURITY.md](SECURITY.md).

## License

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE).
