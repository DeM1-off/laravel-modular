# Private package distribution

Promotion does **not** require the public Packagist. The most common use case is
moving a mature module into a **private package for internal reuse** across your
own applications. The mechanism is identical to
[promoting a package](/promotion/promote-to-package) — only the *source* and
*authentication* change. The module's namespace and code are untouched, so churn
stays at zero.

## Option A — private VCS repository (simplest)

Move the module to a private git repo and require it directly:

```jsonc
// composer.json of the consuming app
"repositories": [
  { "type": "vcs", "url": "git@gitlab.com:acme/blog-module.git" }
],
"require": { "acme/blog-module": "^1.0" }
```

Access is granted with an SSH key or a GitLab **Deploy Token**. In CI, supply the
credentials through `auth.json` (see [Authentication](#authentication)).

## Option B — GitLab Composer package registry (scales better)

GitLab ships a built-in Composer registry at the group level. Publish many
internal modules there and let each app reference a single registry:

```jsonc
"repositories": [
  {
    "type": "composer",
    "url": "https://gitlab.com/api/v4/group/<group-id>/-/packages/composer/packages.json"
  }
],
"require": { "acme/blog-module": "^1.0" }
```

Publishing a version is a git tag plus a CI step that registers the package:

```bash
git tag v1.0.0 && git push origin v1.0.0
# CI then publishes to the group's Composer registry
```

## Authentication

Keep credentials out of `composer.json`. Use `auth.json` (git-ignored locally,
injected from CI variables in pipelines):

```jsonc
// auth.json
{
  "gitlab-token": {
    "gitlab.com": { "username": "<deploy-token-user>", "token": "<deploy-token>" }
  }
}
```

For plain SSH-based VCS access no `auth.json` is needed — the SSH key handles it.

## Develop locally, ship privately

Use a path repository with symlinks during development so the in-app module and
the private package are the same code:

```jsonc
"repositories": [
  { "type": "path", "url": "Modules/*", "options": { "symlink": true } }
]
```

The class `Modules\Blog\...` is identical whether it is symlinked from `Modules/`
or installed from the private registry — nothing to rewrite when you switch.

## Why this is the point

- **Reuse across projects** — one `blog-module` installs into several internal
  apps, no copy-paste.
- **Independent release cadence** — app A pins `^1.0`, app B pins `^2.0`; each
  upgrades when ready. SemVer governs compatibility.
- **Same code in dev and prod** — develop in the monorepo, deploy as a private
  package.

## A module's lifecycle

```
1. Born        → Modules/Blog (path repo, symlink) — fast in-repo iteration
2. Matured     → move to a private git repo
3. Released    → git tag v1.0.0 (+ registry, optional)
4. Reused      → other internal apps: composer require acme/blog-module
```