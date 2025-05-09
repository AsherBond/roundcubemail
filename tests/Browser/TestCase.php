<?php

namespace Roundcube\Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Chrome\SupportsChrome;
use Laravel\Dusk\Concerns\ProvidesBrowser;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

abstract class TestCase extends PHPUnitTestCase
{
    use ProvidesBrowser;
    use SupportsChrome;

    protected $app;
    protected static $phpProcess;

    /**
     * Replace Dusk's Browser with our (extended) Browser
     */
    protected function newBrowser($driver)
    {
        return new Browser($driver);
    }

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        static::startWebServer();
        if (!getenv('WEBDRIVER_CONNECT_URL')) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return RemoteWebDriver
     */
    #[\Override]
    protected function driver()
    {
        $options = (new ChromeOptions())->addArguments([
            '--lang=en_US',
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--disable-features=InsecureDownloadWarnings',
            '--unsafely-treat-insecure-origin-as-secure=' . self::getServerUrl(),
        ]);

        // For file download handling
        $prefs = [
            'profile.default_content_settings.popups' => 0,
            'download.prompt_for_download' => false,
            'download.default_directory' => getenv('BROWSER_DOWNLOADS_DIR') ?: TESTS_DIR . 'downloads',
            'downloadPath' => getenv('BROWSER_DOWNLOADS_DIR') ?: TESTS_DIR . 'downloads',
        ];

        $options->setExperimentalOption('prefs', $prefs);

        if (getenv('TESTS_MODE') == 'phone') {
            // Fake User-Agent string for mobile mode
            $ua = 'Mozilla/5.0 (Linux; Android 4.0.4; Galaxy Nexus Build/IMM76B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Mobile Safari/537.36';
            $options->setExperimentalOption('mobileEmulation', ['userAgent' => $ua]);
            $options->addArguments(['--window-size=375,667']);
        } elseif (getenv('TESTS_MODE') == 'tablet') {
            // Fake User-Agent string for mobile mode
            $ua = 'Mozilla/5.0 (Linux; Android 6.0.1; vivo 1603 Build/MMB29M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.83 Mobile Safari/537.36';
            $options->setExperimentalOption('mobileEmulation', ['userAgent' => $ua]);
            $options->addArguments(['--window-size=1024,768']);
        } else {
            $options->addArguments(['--window-size=1280,720']);
        }

        // Make sure downloads dir exists and is empty
        if (!file_exists(TESTS_DIR . 'downloads')) {
            mkdir(TESTS_DIR . 'downloads', 0777, true);
        } else {
            foreach (glob(TESTS_DIR . 'downloads/*') as $file) {
                @unlink($file);
            }
        }

        return RemoteWebDriver::create(
            getenv('WEBDRIVER_CONNECT_URL') ?: 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    /**
     * Set up the test run
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = \rcmail::get_instance();

        Browser::$baseUrl = self::getServerUrl();
        Browser::$storeScreenshotsAt = TESTS_DIR . 'screenshots';
        Browser::$storeConsoleLogAt = TESTS_DIR . 'console';

        // This folder must exist in case Browser will try to write logs to it
        if (!is_dir(Browser::$storeConsoleLogAt)) {
            mkdir(Browser::$storeConsoleLogAt, 0777, true);
        }

        // Purge screenshots from the last test run
        $pattern = sprintf('failure-%s_%s-*', str_replace('\\', '_', static::class), $this->name()); // @phpstan-ignore-line

        try {
            $files = Finder::create()->files()->in(Browser::$storeScreenshotsAt)->name($pattern);
            foreach ($files as $file) {
                @unlink($file->getRealPath());
            }
        } catch (DirectoryNotFoundException $e) {
            // ignore missing screenshots directory
        }

        // Purge console logs from the last test run
        $pattern = sprintf('%s_%s-*', str_replace('\\', '_', static::class), $this->name()); // @phpstan-ignore-line

        try {
            $files = Finder::create()->files()->in(Browser::$storeConsoleLogAt)->name($pattern);
            foreach ($files as $file) {
                @unlink($file->getRealPath());
            }
        } catch (DirectoryNotFoundException $e) {
            // ignore missing screenshots directory
        }
    }

    /**
     * Starts PHP server.
     */
    protected static function startWebServer()
    {
        $path = realpath(__DIR__ . '/../../public_html');
        $cmd = ['php', '-S', getenv('SERVER_BIND') ?: 'localhost:8000'];
        $env = [];

        static::$phpProcess = new Process($cmd, null, $env);
        static::$phpProcess->setWorkingDirectory($path);
        static::$phpProcess->start();

        static::afterClass(static function () {
            static::$phpProcess->stop();
        });
    }

    protected static function getServerUrl()
    {
        return getenv('SERVER_URL') ?: 'http://localhost:8000';
    }
}
