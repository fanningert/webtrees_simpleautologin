<?php

use Composer\Autoload\ClassLoader;

$loader = new ClassLoader();
$loader->addPsr4('at\\fanninger\\WebtreesModules\\SimpleAutoLogin\\', __DIR__ . '/src');
$loader->register();
