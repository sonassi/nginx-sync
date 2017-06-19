<?php

ini_set('opcache.enable', 0);

require __DIR__ . '/vendor/autoload.php';

use \Sonassi\Di\Di;
use \Sonassi\Di\Bootstrap;

Bootstrap::run();

$csvProcessorCli = Di::get('\Sonassi\NginxSync\CsvProcessorCli');
$csvProcessorCli->process();
