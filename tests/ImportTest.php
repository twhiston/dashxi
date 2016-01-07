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

    //reset the import db
    $this->fs = new Filesystem();
    //if import db exists delete it
    $d = __DIR__.'/data/';
    $s = 'library.import.dash';
    if($this->fs->exists($d.$s)){
      $this->fs->remove($d.$s);
    }
    $d = __DIR__.'/data/';
    $s = 'orig.library.import.dash';
    $this->fs->copy($d.$s, $d.'library.import.dash');

  }


  /**
   * Check false import argument/option reject is correct
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
   *
   */
  public function testCmdExport(){

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testCmdExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test = '/Added Command `ssc to tag: devserver/';
    $this->assertRegExp($test,$disp);

  }

  /**
   */
  public function testCmdExportMultiple(){

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testCmdExportMultiple.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test =<<<EOH
/Command checksym' already exists
Command `smyr already exists
Added Command `sar to tag: devserver
Added Command `d8f to tag: d8/
EOH;
;
    $this->assertRegExp($test,$disp);

  }

  /**
   */
  public function testTagsExportSingle(){

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testTagsExportSingle.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test =<<<EOH
/Added Command `vs to tag: vagrant
Added Command `vu to tag: vagrant
Added Command `vr to tag: vagrant
Added Command `vh to tag: vagrant/
EOH;
    ;
    $this->assertRegExp($test,$disp);
  }

  /**
   * @group failing
   * Note that xxp will remain untagged in our new database as policy is not to mess with an existing snippet with the same body
   */
  public function testTagsExportMultiple(){

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testTagsExportMultiple.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test =<<<EOH
/Added Command `ssmbr to tag: devserver
Command `smyr already exists
Added Command `smyc to tag: devserver
Added Command `ssc to tag: devserver
Added Command `stgc to tag: devserver
Command `stc already exists
Added Command `sar to tag: devserver
Command `xxp already exists
Added Command `vs to tag: vagrant
Added Command `vu to tag: vagrant
Added Command `vr to tag: vagrant
Added Command `vh to tag: vagrant
/
EOH;
    ;
    $this->assertRegExp($test,$disp);
  }

  /**
   */
  public function testMixedExport(){

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testMixedExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test =<<<EOH
/Command `xxp already exists
Added Command `vs to tag: vagrant
Added Command `vu to tag: vagrant
Added Command `vr to tag: vagrant
Added Command `vh to tag: vagrant
Command checksym' already exists
Added Command `d8f to tag: d8/
EOH;
    ;
    $this->assertRegExp($test,$disp);
  }

  /**
   */
  public function testUntaggedAndTaggedCmdExport(){

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testUntaggedAndTaggedCmdExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test =<<<EOH
/Added Command `dmenu to tag: drupal
Command `dform already exists
Added Untagged Command `sym/
EOH;
    ;
    $this->assertRegExp($test,$disp);
  }

  /**
   */
  public function testUntaggedCmdExport(){

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testUntaggedCmdExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test =<<<EOH
/Command `dv already exists/
EOH;
    ;
    $this->assertRegExp($test,$disp);
  }

  /**
   *
   */
  public function testAllExport() {

    $p = __DIR__ . '/data/library.import.dash';
    $f = __DIR__ . '/data/run/testAllExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'  => $p,
      '--file'  => $f,
      '--backup' => FALSE
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $test = <<<EOH
/Added Command `dl to tag: drush
Added Command `dre to tag: drush
Added Command `dca to tag: drush
Added Command `dd to tag: drush
Added Command `du to tag: drush
Added Command `de to tag: drush
Added Command `dcr to tag: drush
Added Command `ssmbr to tag: devserver
Command `smyr already exists
Added Command `smyc to tag: devserver
Added Command `ssc to tag: devserver
Added Command `stgc to tag: devserver
Command `stc already exists
Added Command `sar to tag: devserver
Command `xxp already exists
Added Command `dmenu to tag: drupal
Command `dform already exists
Added Command `vs to tag: vagrant
Added Command `vu to tag: vagrant
Added Command `vr to tag: vagrant
Added Command `vh to tag: vagrant
Added Command `d8f to tag: d8
Command checksym' already exists
Added Untagged Command `rf
Command `dv already exists
Added Untagged Command `sym/
EOH;
    $this->assertRegExp($test,$disp);

  }


}
