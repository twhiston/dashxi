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
use Symfony\Component\Finder\Finder;


/**
 * Class ExportTest
 * @package twhiston\DashXi\tests
 */
class ExportTest extends \PHPUnit_Framework_TestCase {

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

  /**
   * Delete any old results that we might have
   */
  public static function setUpBeforeClass() {
    $p = __DIR__ . '/data/run';

    $finder = new Finder();
    $iter = $finder->files()->name('*.yml')->depth('== 0')->in($p)->getIterator();

    //Clear our existing outputs if they exist
    foreach ($iter as $file) {
      $fn = $file->getFilename();
      $p = $file->getPath(). "/". $fn;
      unlink($p);
    }

  }

  /**
   * set up a test, create the db connection
   */
  protected function setUp(){

    $this->application = new Application();
    $this->application->add(new Commands\Export());
    $this->command = $this->application->find('export');
    $this->commandTester = new CommandTester($this->command);

  }

  /**
   * Test incorrect input arguments to the command
   */
  public function testAllExportArguments(){

    //Cannot find db error
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => 'shunhsnuasnua/hhsaas',
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Cannot Find DB/', $disp);


    $p = __DIR__ . '/data/library.export.dash';
    //test no yml on savepath failure
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--savepath' => __DIR__ . '/data/run/exportAll'
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Save path must end with the .yml filename/', $disp);

  }

  /**
   * Test exporting all Tags and Snippets
   */
  public function testAllExport(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testAllExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--savepath' => $s
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    //todo do some random tests on the results
  }

  /**
   * Export a single tag group
   */
  public function testTagsExportSingle(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testTagsExportSingle.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--savepath' => $s,
      '--tag' => ['vagrant'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

  }

  /**
   * Export multiple tag groups
   */
  public function testTagsExportMultiple(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testTagsExportMultiple.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--savepath' => $s,
      '--tag' => ['vagrant','xdebug','devserver'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);


  }

  /**
   * export a single command
   */
  public function testCmdExport(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testCmdExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--savepath' => $s,
      '--cmd' => ['`ssc'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    //Check that the yml is correct somehow


  }

  /**
   * export multiple commands
   */
  public function testCmdExportMultiple(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testCmdExportMultiple.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--savepath' => $s,
      '--cmd' => ['`sar','`smyr',"checksym'","`d8f"],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);


  }

  /**
   * export some tags and commands
   */
  public function testMixedExport(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testCmdExportMultiple.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--savepath' => $s,
      '--cmd' => ["checksym'","`d8f",'`vu'],
      '--tag' => ['vagrant','devserver'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

  }

}
