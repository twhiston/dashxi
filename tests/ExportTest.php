<?php
/**
 * Created by PhpStorm.
 * User: Thomas Whiston
 * Date: 06/01/2016
 * Time: 16:55
 */

namespace twhiston\DashXi\tests;

use twhiston\DashXi\Commands;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;


class ExportTest extends \PHPUnit_Framework_TestCase {

  //Test that we get all the data on expost. We should really provide a test db to do this with so we can closely test the actual results
  public function testAllExport(){

    $application = new Application();
    $application->add(new Commands\Export());
    $command = $application->find('dashxi:export');
    $commandTester = new CommandTester($command);
    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'    => '/Users/webdev/Library/Application Support/Dash/library.dash',
    );
    $commandTester->execute($arguments);
    $disp = $commandTester->getDisplay();
    $this->assertRegExp('/.../', $disp);

  }
}
