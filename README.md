# Addon Composer Autoload Generator

Scans the enclosing vendor folder for other packages and generates an autoload
file containing an array of the package namespaces and source folders. Then an
external application can be powered by multiple vendor folders by registering
namespaces at runtime.

Require `"streams/addon-composer": "dev-master"`, any other dependencies and add
the scripts in the composer.json as shown below.

### composer.json

    {
        "require": {
            "streams/addon-composer": "dev-master",
            "league/flysystem" : "dev-master",
            "league/fractal" : "dev-master",
            "league/plates" : "dev-master"
        },
        "scripts": {
            "post-install-cmd": "Streams\\AddonComposer\\Generator::generate",
            "post-update-cmd": "Streams\\AddonComposer\\Generator::generate"
        }
    }

### Generated streams.addon.autoload.php

    <?php return [
        'psr-4' => [
            'League\\Flysystem\\' => 'league/flysystem/src/',
            'League\\Fractal\\' => 'league/fractal/src',
            'League\\Plates\\' => 'league/plates/src',
        ]
    ];

The main application can require the `streams.addon.autoload.php` file and register namespaces with the Composer Classloader.
This is an example of what it could look like.

    $loader = new Composer\Autoload\ClassLoader;

    // Loop through each addon and register the PSR paths

    if (is_file($addon['path'] . '/streams.addon.autoload.php')) {
        $autoload = require $addon['path'] . '/streams.addon.autoload.php';

        if (isset($autoload['psr-0'])) {
            foreach ($autoload['psr-0'] as $namespace => $src) {
                $loader->add($namespace, $vendorFolder.$src));
            }
        }

        if (isset($autoload['psr-4'])) {
            foreach ($autoload['psr-4'] as $namespace => $src) {
                $loader->addPsr4($namespace, $vendorFolder.$src));
            }
        }
    }

    // Register

    $loader->register();