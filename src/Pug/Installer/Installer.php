<?php

namespace Pug\Installer;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Script\Event;

class Installer
{
    /**
     * @var Event
     */
    protected $event;

    function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function install($repository, $version = null)
    {
        $vendorDirectory = $this->event->getComposer()->getConfig()->get('vendor-dir');
        if (!is_dir($vendorDirectory . DIRECTORY_SEPARATOR . 'bin')) {
            $vendorDirectory = __DIR__;
            for ($i = 0; $i < 10; $i++) {
                $vendorDirectory = dirname($vendorDirectory);
                if (is_dir($vendorDirectory . DIRECTORY_SEPARATOR . 'bin')) {
                    break;
                }
                if (is_dir($vendorDirectory . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin')) {
                    $vendorDirectory = $vendorDirectory . DIRECTORY_SEPARATOR . 'vendor';
                    break;
                }
            }
        }
        $composer = $vendorDirectory . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer';

        return shell_exec($composer . ' require ' . $repository . ($version ? ' ' . $version : '') . ' 2>&1');
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

    protected static function getInstallerConfig(Composer $composer)
    {
        $vendorDir = $composer->getConfig()->get('vendor-dir');

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
            $config = $composer->getPackage()->getExtra();
            $event->getIO()->write(isset($config['installer'])
                ? 'No installer found.'
                : "Warning: in order to use Pug\\Installer, you should add an \"extra\": {\"installer\": \"YourInstallerClass\"}' setting in your composer.json"
            );

            return;
        }

        foreach ($installers as $installer) {
            call_user_func($installer, $event, new static($event));
        }
    }
}
