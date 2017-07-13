# Pug Installer
[![Latest Stable Version](https://poser.pugx.org/pug/installer/v/stable.png)](https://packagist.org/packages/pug/installer)
[![Build Status](https://travis-ci.org/pug-php/installer.svg?branch=master)](https://travis-ci.org/pug-php/installer)
[![StyleCI](https://styleci.io/repos/97131673/shield?style=flat)](https://styleci.io/repos/97131673)
[![Test Coverage](https://codeclimate.com/github/pug-php/installer/badges/coverage.svg)](https://codecov.io/github/pug-php/installer?branch=master)
[![Code Climate](https://codeclimate.com/github/pug-php/installer/badges/gpa.svg)](https://codeclimate.com/github/pug-php/installer)

Allow you to call scripts and sub-installations after package installed.

## Usage

Edit **composer.json** like this:

```json
...
"require": {
    "pug/installer": "*"
},
"extra": {
    "installer": "MyClass::install"
},
"scripts": {
    "post-install-cmd": [
        "Pug\\Installer\\Installer::onAutoloadDump"
    ],
    "post-update-cmd": [
        "Pug\\Installer\\Installer::onAutoloadDump"
    ]
},
...
```

Then in your MyClass::install method (MyClass must be available via some PSR autoload you defined in composer.json).

```php
<?php

use Composer\Script\Event;
use Pug\Installer\Installer;

class MyClass
{
    public static install(Event $event, Installer, $installer)
    {
        $installer->install('pug/pug');
        $event->getIO()->write('pug/pug has been installed');
    }
}
```

The following will install **pug/pug** after your own package.

You can pass multiple installers like this:

```json
"extra": {
    "installer": [
        "Foo::install",
        "Bar::install"
    ]
}
```