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


class CleanBackupsTest extends \PHPUnit_Framework_TestCase {

  //Test that we get all the data on expost. We should really provide a test db to do this with so we can closely test the actual results
  public function testClean(){

    $application = new Application();
    $application->add(new Commands\CleanBackups());
    $command = $application->find('dashxi:clean');
    $commandTester = new CommandTester($command);
    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'    => '/Users/webdev/Library/Application Support/Dash/',
    );
    $commandTester->execute($arguments);


  }

}
