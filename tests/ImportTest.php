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


/**
 * Class ImportTest
 * @package twhiston\DashXi\tests
 */
class ImportTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var Symfony\Component\Console\Application
   */
  private $application;
  /**
   * @var
   */
  private $command;
  /**
   * @var
   */
  private $commandTester;

  public function setUp() {

    $this->application = new Application();
    $this->application->add(new Commands\Import());
    $this->command = $this->application->find('import');
    $this->commandTester = new CommandTester($this->command);

    //todo - reset the import db


  }

  //Test that we get all the data on expost. We should really provide a test db to do this with so we can closely test the actual results
  /**
   *
   */
  public function testAllImportArguments(){



    //Test that no arguments fails out
    //The import DB has some of the same codes, and some different ones, and tags
    //The command `smyr is the same command but definitely a different ID
    //So this needs special attention
    $p = '//asaaass.s8ths92.9hsay-aa';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Cannot Find DB/', $disp);

    $p = __DIR__ . '/data/library.import.dash';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();

    $this->assertRegExp('/Must specify file/', $disp);

    //test that no yml extension fails
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--file' => '/this/is/a/file/path',
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Save path must end with the .yml filename/', $disp);

    //test that invalid file fails
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--file' => '/this/is/a/file/path/file.yml',
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Cannot Find Import File/', $disp);

  }

  /**
   * @group failing
   */
  public function testUntaggedImports(){

  }

  /**
   * @group failing
   */
  public function testFileImport() {

    $p = __DIR__ . '/data/library.import.dash';
    $pd = __DIR__ . '/data/';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => '/Users/webdev/Sites/_MyCode/PHP/DashSnippetExtractor/data/output.yml'
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();

  }


}
