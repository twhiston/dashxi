#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: Thomas Whiston
 * Date: 06/01/2016
 * Time: 12:20
 */


require __DIR__.'/vendor/autoload.php';

use twhiston\DashXi\Commands;
use Symfony\Component\Console\Application;

$application = new Application('DashXi', '@package_version@');
$application->add(new Commands\Export());
$application->add(new Commands\Import());
$application->add(new Commands\CleanBackups());
$application->run();