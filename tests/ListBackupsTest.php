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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


/**
 * Class CleanBackupsTest
 * @package twhiston\DashXi\tests
 */
class ListBackupsTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var
   */
  private $fs;


  /**
   * Test that the check returns something
   */
  public function testListBackups(){

    $application = new Application();
    $application->add(new Commands\ListBackups());
    $command = $application->find('backup:list');
    $commandTester = new CommandTester($command);
    $s = __DIR__ . '/data/list/';
    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'    => $s,
    );

    $commandTester->execute($arguments);
    $this->assertRegExp('/library.dash.backup/',$commandTester->getDisplay());

  }

}
