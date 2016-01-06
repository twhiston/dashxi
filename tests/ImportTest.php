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


class ImportTest extends \PHPUnit_Framework_TestCase {

  //Test that we get all the data on expost. We should really provide a test db to do this with so we can closely test the actual results
  public function testAllImportArguments(){

    $application = new Application();
    $application->add(new Commands\Import());
    $command = $application->find('dashxi:import');
    $commandTester = new CommandTester($command);

    //Test that no arguments fails out
    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'    => '/Users/webdev/Library/Application Support/Dash/library.dash',
    );
    $commandTester->execute($arguments);
    $disp = $commandTester->getDisplay();

    $this->assertRegExp('/Must specify file/', $disp);

    //test that no yml extension fails
    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'    => '/Users/webdev/Library/Application Support/Dash/library.dash',
      '--file' => '/this/is/a/file/path',
    );
    $commandTester->execute($arguments);
    $disp = $commandTester->getDisplay();
    $this->assertRegExp('/Save path must end with the .yml filename/', $disp);

    //test that invalid file fails
    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'    => '/Users/webdev/Library/Application Support/Dash/library.dash',
      '--file' => '/this/is/a/file/path/file.yml',
    );
    $commandTester->execute($arguments);
    $disp = $commandTester->getDisplay();
    $this->assertRegExp('/Cannot Find Import File/', $disp);

  }

  public function testFileImport() {

    $application = new Application();
    $application->add(new Commands\Import());
    $command = $application->find('dashxi:import');
    $commandTester = new CommandTester($command);

    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'  => '/Users/webdev/Library/Application Support/Dash/library.dash',
      '--file'  => '/Users/webdev/Sites/_MyCode/PHP/DashSnippetExtractor/data/output.yml'
    );
    $commandTester->execute($arguments);
    $disp = $commandTester->getDisplay();

  }

}
