<?php

namespace Pug\Installer;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\Json\JsonFile;

class Installer
{
    /**
     * @var Event
     */
    protected $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function install($repository, $version = null)
    {
        $vendorDirectory = static::fallbackVendorDir(static::getComposerVendorDir($this->event->getComposer()));
        $composer = $vendorDirectory . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer';

        return shell_exec($composer . ' require --no-interaction ' . $repository . ($version ? ' ' . $version : '') . ' 2>&1');
    }

    protected static function looksVendorDir($vendorDirectory)
    {
        return is_dir($vendorDirectory . DIRECTORY_SEPARATOR . 'bin') ||
            file_exists($vendorDirectory . DIRECTORY_SEPARATOR . 'autoload.php');
    }

    public static function fallbackVendorDir($vendorDirectory)
    {
        if (!static::looksVendorDir($vendorDirectory)) {
            $vendorDirectory = __DIR__;
            for ($i = 0; $i < 10; $i++) {
                $vendorDirectory = dirname($vendorDirectory);
                if (static::looksVendorDir($vendorDirectory)) {
                    break;
                }
                if (static::looksVendorDir($vendorDirectory . DIRECTORY_SEPARATOR . 'vendor')) {
                    $vendorDirectory = $vendorDirectory . DIRECTORY_SEPARATOR . 'vendor';
                    break;
                }
            }
        }

        return $vendorDirectory;
    }

    protected static function appendConfig(&$installers, $directory)
    {
        $json = new JsonFile($directory . DIRECTORY_SEPARATOR . 'composer.json');

        try {
            $dependencyConfig = $json->read();
        } catch (\RuntimeException $e) {
            $dependencyConfig = null;
        }
        if (is_array($dependencyConfig) && isset($dependencyConfig['extra'], $dependencyConfig['extra']['installer'])) {
            $installers = array_merge($installers, (array) $dependencyConfig['extra']['installer']);
        }
    }

    protected static function getComposerVendorDir(Composer $composer)
    {
        return realpath($composer->getConfig()->get('vendor-dir'));
    }

    protected static function getInstallerConfig(Composer $composer)
    {
        $vendorDir = static::getComposerVendorDir($composer);

        $installers = array();

        foreach (scandir($vendorDir) as $namespace) {
            if ($namespace === '.' || $namespace === '..' || !is_dir($directory = $vendorDir . DIRECTORY_SEPARATOR . $namespace)) {
                continue;
            }
            foreach (scandir($directory) as $dependency) {
                if ($dependency === '.' || $dependency === '..' || !is_dir($subDirectory = $directory . DIRECTORY_SEPARATOR . $dependency)) {
                    continue;
                }
                static::appendConfig($installers, $subDirectory);
            }
        }
        static::appendConfig($installers, dirname($vendorDir));

        return $installers;
    }

    public static function onAutoloadDump(Event $event)
    {
        $composer = $event->getComposer();
        $installers = static::getInstallerConfig($composer);

        if (!count($installers)) {
            $event->getIO()->write(
                "Warning: in order to use Pug\\Installer, you should add an \"extra\": {\"installer\": \"YourInstallerClass\"}' setting in your composer.json"
            );

            return;
        }

        include_once static::fallbackVendorDir(static::getComposerVendorDir($composer)) . DIRECTORY_SEPARATOR . 'autoload.php';

        foreach ($installers as $installer) {
            call_user_func($installer, $event, new static($event));
        }
    }

    public function __toString()
    {
        return get_class();
    }
}
