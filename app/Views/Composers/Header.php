<?php

namespace Leantime\Views\Composers;

use Leantime\Core\Configuration\AppSettings;
use Leantime\Core\Configuration\Environment;
use Leantime\Core\UI\Composer;
use Leantime\Core\UI\Theme;
use Leantime\Domain\Setting\Repositories\Setting;

class Header extends Composer
{
    public static array $views = [
        'global::sections.header',
    ];

    private Environment $config;

    private Theme $themeCore;

    private AppSettings $appSettings;

    private Setting $settingsRepo;

    public function init(
        Setting $settingsRepo,
        Environment $config,
        AppSettings $appSettings,
        Theme $themeCore
    ): void {
        $this->settingsRepo = $settingsRepo;
        $this->config = $config;
        $this->appSettings = $appSettings;
        $this->themeCore = $themeCore;
    }

    public function with(): array
    {
        // Batch-preload all theme settings in a single query on first load.
        // This populates SettingCache's in-memory tier so all subsequent
        // getSetting() calls from getActive(), getColorMode(), etc. are instant.
        $this->themeCore->preloadUserSettings();

        $theme = $this->themeCore->getActive();
        $colorMode = $this->themeCore->getColorMode();
        $colorScheme = $this->themeCore->getColorScheme();
        $themeFont = $this->themeCore->getFont();

        // Set colors to use
        if (! session()->exists('companysettings.sitename')) {
            $sitename = $this->settingsRepo->getSetting('companysettings.sitename');
            if ($sitename !== false) {
                session(['companysettings.sitename' => $sitename]);
            } else {
                session(['companysettings.sitename' => $this->config->sitename]);
            }
        }

        $backgroundOpacity = 0.1;
        if ($this->themeCore->getBackgroundType() == 'image') {
            $backgroundOpacity = 1;
        }

        $assetVersion = $this->resolveAssetVersion(
            (string) ($this->appSettings->assetVersion ?? ($this->appSettings->appVersion ?? ''))
        );

        return [
            'sitename' => session('companysettings.sitename') ?? '',
            'primaryColor' => $this->themeCore->getPrimaryColor(),
            'theme' => $theme,
            'version' => $this->appSettings->appVersion ?? '',
            'assetVersion' => $assetVersion,
            'themeScripts' => [
                $this->themeCore->getJsUrl() ?? '',
                $this->themeCore->getCustomJsUrl() ?? '',
            ],
            'themeColorMode' => $colorMode,
            'themeColorScheme' => $colorScheme,
            'themeFont' => $themeFont,
            'themeStyles' => [
                [
                    'id' => 'themeStyleSheet',
                    'url' => $this->themeCore->getStyleUrl() ?? '',
                ],
                [
                    'url' => $this->themeCore->getCustomStyleUrl() ?? '',
                ],
            ],
            'accents' => [
                $this->themeCore->getPrimaryColor(),
                $this->themeCore->getSecondaryColor(),
            ],
            'themeBg' => $this->themeCore->getBackgroundImage(),
            'themeOpacity' => $backgroundOpacity,
            'themeType' => $this->themeCore->getBackgroundType(),
        ];
    }

    /**
     * Resolve an asset version that actually exists on disk.
     * Prevents global CSS/JS breakage when configured version mismatches deployed bundles.
     */
    private function resolveAssetVersion(string $preferredVersion): string
    {
        $candidates = array_values(array_unique(array_filter([
            $preferredVersion,
            (string) ($this->appSettings->appVersion ?? ''),
        ])));

        foreach ($candidates as $candidateVersion) {
            if ($this->assetBundleExists($candidateVersion)) {
                return $candidateVersion;
            }
        }

        $detectedVersion = $this->detectLatestAssetVersionFromDisk();
        if ($detectedVersion !== '') {
            return $detectedVersion;
        }

        return $preferredVersion;
    }

    private function assetBundleExists(string $version): bool
    {
        if ($version === '') {
            return false;
        }

        $mainCss = ROOT.'/dist/css/main.'.$version.'.min.css';
        $appJs = ROOT.'/dist/js/compiled-app.'.$version.'.min.js';

        return is_file($mainCss) && is_file($appJs);
    }

    private function detectLatestAssetVersionFromDisk(): string
    {
        $matches = glob(ROOT.'/dist/css/main.*.min.css');
        if (! is_array($matches) || count($matches) === 0) {
            return '';
        }

        $versions = [];
        foreach ($matches as $file) {
            if (! is_string($file) || $file === '') {
                continue;
            }

            if (preg_match('/main\.(.+)\.min\.css$/', $file, $groups) !== 1) {
                continue;
            }

            $candidate = (string) ($groups[1] ?? '');
            if ($candidate === '' || ! $this->assetBundleExists($candidate)) {
                continue;
            }

            $versions[] = $candidate;
        }

        if (count($versions) === 0) {
            return '';
        }

        usort($versions, static function (string $a, string $b): int {
            return version_compare($b, $a);
        });

        return $versions[0] ?? '';
    }
}
