<?php

/**
 * Dependency-free smoke test for simon/php-chardet-modern.
 *
 * Verifies the installed package (path validation, end-to-end chardet
 * detection) without needing PHPUnit or dev dependencies.
 *
 * Usage:
 *   php smoke.php [path/to/autoloader.php]
 *
 * Autoloader resolution order:
 *   1. the optional CLI argument
 *   2. ./vendor/autoload.php  (development checkout of this fork)
 *   3. ../autoload.php        (package installed under a project's vendor/)
 *
 * Exit codes: 0 = all checks passed (skips allowed), 1 = at least one failure.
 */

error_reporting(E_ALL);

$autoloaderPath = null;
$candidates = array();
if (isset($argv[1])) {
    $candidates[] = $argv[1];
}
$candidates[] = __DIR__ . '/vendor/autoload.php';
$candidates[] = __DIR__ . '/../autoload.php';

foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloaderPath = $candidate;
        break;
    }
}

if ($autoloaderPath === null) {
    fwrite(STDERR, "No autoloader found. Tried:\n  " . implode("\n  ", $candidates) . "\n");
    fwrite(STDERR, "Pass one explicitly: php smoke.php /path/to/project/vendor/autoload.php\n");
    exit(1);
}

require $autoloaderPath;

$failures = 0;
$skipped = 0;

function check($name, $closure)
{
    global $failures;
    try {
        $result = $closure();
        if ($result === true) {
            echo "PASS: {$name}\n";
            return;
        }
        echo "FAIL: {$name}\n";
        $failures++;
    } catch (\Throwable $e) {
        echo "FAIL: {$name} — " . get_class($e) . ": " . $e->getMessage() . "\n";
        $failures++;
    }
}

function expectException($expectedClass, $expectedMessage, $closure)
{
    try {
        $closure();
    } catch (\Exception $e) {
        if ($e instanceof $expectedClass && strpos($e->getMessage(), $expectedMessage) !== false) {
            echo "PASS: throws {$expectedClass}('{$expectedMessage}')\n";
            return;
        }
        echo "FAIL: wrong exception " . get_class($e) . ": " . $e->getMessage() . "\n";
        $GLOBALS['failures']++;
        return;
    }
    echo "FAIL: expected {$expectedClass}('{$expectedMessage}'), none thrown\n";
    $GLOBALS['failures']++;
}

echo "Autoloader: {$autoloaderPath}\n\n";

check('class Yupmin\PHPChardet\Chardet is loadable', function () {
    if (!class_exists('Yupmin\PHPChardet\Chardet')) {
        echo "class Yupmin\\PHPChardet\\Chardet not found\n";
        return false;
    }
    return true;
});

if (!class_exists('Yupmin\PHPChardet\Chardet')) {
    fwrite(STDERR, "\nCannot continue without the class.\n");
    exit(1);
}

expectException('Exception', "File doesn't exist", function () {
    $chardet = new \Yupmin\PHPChardet\Chardet();
    $chardet->analyze('/nonexistent/path/for/php-chardet-modern-smoke.txt');
});

expectException('Exception', 'You must specify a filename, not a directory name', function () {
    $chardet = new \Yupmin\PHPChardet\Chardet();
    $chardet->analyze(__DIR__);
});

$binaryAvailable = false;
exec('command -v chardet 2>/dev/null', $commandOutput, $exitCode);
$binaryAvailable = $exitCode === 0;

if (!$binaryAvailable) {
    $skipped++;
    echo "SKIP: chardet CLI not found on PATH — end-to-end checks skipped\n";
}

if ($binaryAvailable) {
    $utf8Fixture = __DIR__ . '/tests/fixtures/subtitle-utf8.srt';
    $euckrFixture = __DIR__ . '/tests/fixtures/subtitle-euckr.srt';

    if (!file_exists($utf8Fixture) || !file_exists($euckrFixture)) {
        $skipped++;
        echo "SKIP: package fixtures not found — end-to-end checks skipped\n";
    } else {
        check('analyze() UTF-8 fixture → utf-8', function () use ($utf8Fixture) {
            $container = (new \Yupmin\PHPChardet\Chardet())->analyze($utf8Fixture);
            if ($container->getCharset() !== 'utf-8') {
                echo "charset was '{$container->getCharset()}'\n";
                return false;
            }
            if (!is_float($container->getConfidence()) || $container->getConfidence() <= 0) {
                echo "confidence was '" . var_export($container->getConfidence(), true) . "'\n";
                return false;
            }
            return true;
        });

        check('analyze() EUC-KR fixture → euc-kr', function () use ($euckrFixture) {
            $container = (new \Yupmin\PHPChardet\Chardet())->analyze($euckrFixture);
            if ($container->getCharset() !== 'euc-kr') {
                echo "charset was '{$container->getCharset()}'\n";
                return false;
            }
            return true;
        });

        check('container exposes file path unchanged', function () use ($utf8Fixture) {
            $container = (new \Yupmin\PHPChardet\Chardet())->analyze($utf8Fixture);
            return $container->getFilePath() === $utf8Fixture;
        });
    }
}

echo "\nResult: ";
if ($failures > 0) {
    echo "{$failures} failure(s), {$skipped} skipped\n";
    exit(1);
}
echo "all checks passed, {$skipped} skipped\n";
exit(0);
