<?php

namespace Leantime\Core\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class LoadEnvironmentVariables extends \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables
{
    public function bootstrap(Application $app): void
    {
        // Keep Laravel's default env bootstrapping behavior first.
        parent::bootstrap($app);

        // Compatibility fallback: if deployments place .env at app root (common on shared hosts),
        // load missing keys from that file too. Existing keys are never overwritten.
        $rootEnvPath = $app->basePath('.env');

        if (! is_file($rootEnvPath)) {
            return;
        }

        foreach ($this->parseEnvFile($rootEnvPath) as $key => $value) {
            if ($key === '' || getenv($key) !== false || isset($_ENV[$key])) {
                continue;
            }

            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Lightweight .env parser for simple KEY=VALUE lines.
     *
     * @return array<string, string>
     */
    private function parseEnvFile(string $path): array
    {
        $vars = [];
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! is_array($lines)) {
            return $vars;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $delimiterPos = strpos($trimmed, '=');
            if ($delimiterPos === false) {
                continue;
            }

            $key = trim(substr($trimmed, 0, $delimiterPos));
            $value = trim(substr($trimmed, $delimiterPos + 1));
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($key !== '') {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }
}
