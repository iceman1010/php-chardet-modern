# php-chardet-modern

Namespace-identical fork of [`yupmin/php-chardet`](https://github.com/yupmin/php-chardet)
(1.0.1, abandoned 2015) on modern dependencies. Same PHP namespace
(`Yupmin\PHPChardet`), same API, same behavior — it is a drop-in replacement:

```diff
- "yupmin/php-chardet": "^1.0"
+ "simon/php-chardet-modern": "^1.1"
```

No consumer code changes are required; the `Yupmin\PHPChardet` classes resolve
exactly as before.

## What changed vs. upstream

- `symfony/process` requirement modernized: `^5.4 || ^6.4 || ^7.0`
  (upstream pinned `~2.3`, which Composer >= 2.9 refuses for security
  advisories). The runner now uses the `Process` class directly instead of the
  removed `ProcessBuilder`.
- `symfony/filesystem` dependency dropped (upstream used it for a single
  `file_exists()` check).
- PHP floor raised to `>= 7.4`.
- API surface, namespace, class names, and observable behavior are unchanged.

## Requirements

- PHP >= 7.4
- Composer
- The [`chardet`](https://pypi.org/project/chardet/) command-line tool on your
  PATH (runtime requirement inherited from upstream, unchanged)

Install the CLI, e.g.:

```bash
sudo apt-get install chardet
# or
pip install chardet
```

## Installation

```bash
composer require simon/php-chardet-modern
```

## Usage

```php
use Yupmin\PHPChardet\Chardet;

$chardet = new Chardet();
$chardetContainer = $chardet->analyze('test.txt');

$filePath    = $chardetContainer->getFilePath();
$charset     = $chardetContainer->getCharset();    // e.g. 'utf-8' (lowercased)
$confidence  = $chardetContainer->getConfidence(); // e.g. 0.99 (float)
```

Failure modes (all inherited from upstream):

- Non-existent path or directory passed to `analyze()`: `\Exception`.
- `chardet` CLI exits non-zero: `\RuntimeException` carrying the error output.
- Unanalyzable input (chardet reports `None`): `\Exception`.

## Tests

```bash
composer install
vendor/bin/phpunit
```

Tests that need the `chardet` CLI skip automatically when it is absent.

`smoke.php` is a dependency-free check (no PHPUnit needed) for verifying the
installed package on a target host:

```bash
php smoke.php            # in this repo (uses ./vendor/autoload.php)
php smoke.php /path/to/project/vendor/autoload.php   # against a project autoloader
```

## Credits & license

Fork of [`yupmin/php-chardet`](https://github.com/yupmin/php-chardet) by
yun young-jin (MIT). See `LICENSE` — original copyright preserved, fork
copyright added.
