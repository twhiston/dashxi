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


class CleanBackupsTest extends \PHPUnit_Framework_TestCase {

  private $fs;

  protected function setUp(){

    //Copy some files into our cleaning directory
    $f = 'library.export.dash';
    $p = __DIR__ . '/data/';
    $this->fs = new Filesystem();
    if (!$this->fs->exists($p.$f)) {
      die();
    }
    $this->fs->copy($p.$f, $p.'cleanme/library.dash.backup.'.time());
    $this->fs->copy($p.$f, $p.'cleanme/library.dash.backup.'.(time()+184327));
  }

  //Test that we get all the data on expost. We should really provide a test db to do this with so we can closely test the actual results
  /**
   *  @group failing
   */
  public function testClean(){

    $application = new Application();
    $application->add(new Commands\CleanBackups());
    $command = $application->find('clean');
    $commandTester = new CommandTester($command);
    $s = __DIR__ . '/data/cleanme/';
    $arguments = array(
      'command' =>  $command->getName(),
      'dbpath'    => $s,
    );

    $commandTester->execute($arguments);

    $p = __DIR__ . '/data/cleanme';
    $finder = new Finder();
    $iter = $finder->files()->name('library.dash.backup.*')->depth('== 0')->in($p)->getIterator();

    $count = 0;
    foreach ($iter as $file) {
      $count += 1;
    }
    //Should have found no library backups
    $this->assertEquals(0,$count);

  }

}
