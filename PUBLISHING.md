# Publishing To Packagist

Laravel packages are published through Packagist:

[https://packagist.org](https://packagist.org)

Packagist is the Composer package registry. It is the PHP/Laravel equivalent of pub.dev for Flutter packages and npmjs.com for Node packages.

## GitHub Repository

Use this public repository:

[https://github.com/Dream-Technologies-PLC/telebirr-laravel-plus](https://github.com/Dream-Technologies-PLC/telebirr-laravel-plus)

Package name:

```text
dream-technologies/telebirr-laravel-plus
```

Install command for users:

```bash
composer require dream-technologies/telebirr-laravel-plus
```

## First Publish

1. Log in to [Packagist](https://packagist.org).
2. Click **Submit**.
3. Paste the GitHub repository URL:

```text
https://github.com/Dream-Technologies-PLC/telebirr-laravel-plus
```

4. Submit the package.
5. Packagist reads `composer.json` and creates the package page.

## Auto Updates

Packagist can auto-update when GitHub tags are pushed.

Recommended setup:

1. Open the package page on Packagist.
2. Go to package settings.
3. Enable GitHub hook or connect GitHub integration.
4. Push release tags from this repository.

## Release A New Version

Composer package versions come from git tags.

For version `0.1.0`:

```bash
git tag v0.1.0
git push origin main
git push origin v0.1.0
```

For a future release:

```bash
git add .
git commit -m "Release 0.1.1"
git tag v0.1.1
git push origin main
git push origin v0.1.1
```

## CI/CD

This repository includes GitHub Actions CI:

```text
.github/workflows/ci.yml
```

CI runs on push and pull requests. It validates Composer metadata, installs dependencies, checks PHP syntax, and runs tests across supported PHP/Laravel versions.

Packagist publishing itself does not require uploading a build artifact. Packagist reads this public GitHub repository and release tags.
