<?php

namespace Pug\Tests;

use Composer\Script\Event;
use Pug\Installer\ComposerPlugin;
use Pug\Installer\Installer;

class ComposerPluginTest extends TestCase
{
    protected static $deleteAfterTest = array();

    private static $event;
    private static $installer;
    private static $output;

    public static function install(Event $event, Installer $installer)
    {
        self::$event = $event;
        self::$installer = $installer;
        self::$output = $installer->install('pug/this-does-not-exists');
    }

    public function testPluginActivate()
    {
        static::$event = null;
        static::$installer = null;
        static::$output = null;
        $composer = $this->emulateComposer(array(
            'toto/toto' => '{"extra":{"installer":"\\\\Pug\\\\Tests\\\\ComposerPluginTest::install"}}',
        ));
        $io = new CaptureIO();
        $event = new Event('install', $composer, $io);
        $plugin = new ComposerPlugin();
        $plugin->activate($composer, $io);
        $events = ComposerPlugin::getSubscribedEvents();
        $this->assertTrue(is_array($events));
        $this->assertTrue(is_array($events['post-autoload-dump']));
        $this->assertTrue(is_array($events['post-autoload-dump'][0]));
        $method = $events['post-autoload-dump'][0][0];
        $plugin->$method($event);

        self::assertInstanceOf('Composer\\Script\\Event', self::$event);
        self::assertInstanceOf('Pug\\Installer\\Installer', self::$installer);
        self::assertSame('Pug\\Installer\\Installer', '' . self::$installer);
        self::assertRegExp('`Could not find( a matching version of)? package pug/this-does-not-exists`', self::$output);
        static::removeTestDirectories();
    }

    public function testNoInstaller()
    {
        $composer = $this->emulateComposer(array(
            'toto/toto' => '{"extra":{}}',
        ));
        $io = new CaptureIO();
        $event = new Event('install', $composer, $io);
        $plugin = new ComposerPlugin();
        $plugin->activate($composer, $io);
        $events = ComposerPlugin::getSubscribedEvents();
        $this->assertTrue(is_array($events));
        $this->assertTrue(is_array($events['post-autoload-dump']));
        $this->assertTrue(is_array($events['post-autoload-dump'][0]));
        $method = $events['post-autoload-dump'][0][0];
        $plugin->$method($event);

        self::assertSame("Warning: in order to use Pug\\Installer, you should add an \"extra\": {\"installer\": \"YourInstallerClass\"}' setting in your composer.json", $io->getLastOutput());
    }
}
