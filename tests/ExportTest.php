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
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Filesystem;


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
   * @var
   */
  private $fs;

  /**
   * @var
   */
  private $parser;

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

    $this->fs = new Filesystem();
    $this->parser = new Parser();

    //reset the import db
    $d = __DIR__.'/data/';
    $s = 'library.export.dash';
    if($this->fs->exists($d.$s)){
      $this->fs->remove($d.$s);
    }
    $d = __DIR__.'/data/';
    $s = 'orig.library.export.dash';
    $this->fs->copy($d.$s, $d.'library.export.dash');

  }



  /**
   * @param $filepath
   * @return mixed
   */
  private function getFile($filepath){
    $this->assertTrue($this->fs->exists($filepath));
    $reload = $this->parser ->parse(file_get_contents($filepath));
    return $reload;
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
      '--file' => __DIR__ . '/data/run/exportAll'
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Save path must end with the .yml filename/', $disp);

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
      '--file' => $s,
      '--tag' => ['vagrant'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    $output = $this->getFile($s);
    $this->assertArrayHasKey('snippets',$output);
    $this->assertArrayHasKey('tags',$output);
    $this->assertArrayHasKey(14,$output['snippets']);
    $this->assertCount(4,$output['snippets'][14]);

    $correct = [14,'vagrant'];
    $this->internalTestTag($output['tags'][0],$correct);

    $correct = [66,'`vs','vagrant ssh','PHP','0'];
    $this->internalTestSnippet($output['snippets'][14][0],$correct);
    $correct = [67,'`vu','vagrant up','PHP','0'];
    $this->internalTestSnippet($output['snippets'][14][1],$correct);
    $correct = [68,'`vr','vagrant reload','PHP','0'];
    $this->internalTestSnippet($output['snippets'][14][2],$correct);
    $correct = [69,'`vh','vagrant halt','PHP','0'];
    $this->internalTestSnippet($output['snippets'][14][3],$correct);

  }

  /**
   * Export multiple tag groups
   *
   */
  public function testTagsExportMultiple(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testTagsExportMultiple.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--file' => $s,
      '--tag' => ['vagrant','xdebug','devserver'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    $output = $this->getFile($s);
    $this->assertArrayHasKey('snippets',$output);
    $this->assertCount(3,$output['snippets']);

    $this->assertArrayHasKey('tags',$output);
    $this->assertCount(3,$output['tags']);

    $this->assertArrayHasKey(3,$output['snippets']);
    $this->assertCount(7,$output['snippets'][3]);

    $this->assertArrayHasKey(9,$output['snippets']);
    $this->assertCount(1,$output['snippets'][9]);

    $this->assertArrayHasKey(14,$output['snippets']);
    $this->assertCount(4,$output['snippets'][14]);

    $correct = [3,'devserver'];
    $this->internalTestTag($output['tags'][0],$correct);

    //Test a couple of snippets
    $correct = [33,'`smyc','sudo nano \/etc\/mysql\/my.cnf','PHP','0'];
    $this->internalTestSnippet($output['snippets'][3][2],$correct);

    $correct = [52,'`xxp','export XDEBUG_CONFIG="idekey=PHPSTORM"','PHP','0'];
    $this->internalTestSnippet($output['snippets'][9][0],$correct);

  }

  /**
   * export a single command
   *
   */
  public function testCmdExport(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testCmdExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--file' => $s,
      '--snip' => ['`ssc'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    //Check that the yml is correct somehow
    $output = $this->getFile($s);

    $this->assertArrayHasKey('snippets',$output);
    $this->assertCount(1,$output['snippets']);

    $this->assertArrayHasKey('tags',$output);
    $this->assertCount(1,$output['tags']);

    $correct = [3,'devserver'];
    $this->internalTestTag($output['tags'][0],$correct);

    $this->assertArrayHasKey(3,$output['snippets']);
    $this->assertCount(1,$output['snippets'][3]);
    $correct = [34,'`ssc','sudo dd if=\/dev\/null of=\/var\/log\/syslog','PHP','0'];
    $this->internalTestSnippet($output['snippets'][3][0],$correct);

  }

  /**
   * export an untagged command
   *
   */
  public function testUntaggedCmdExport(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testUntaggedCmdExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--file' => $s,
      '--snip' => ['`dv'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    //Check that the yml is correct somehow
    //Get the file that was output and test it
    $output = $this->getFile($s);

    $this->assertArrayHasKey('snippets',$output);
    $this->assertArrayHasKey('untagged',$output['snippets']);
    $this->assertCount(1,$output['snippets']['untagged']);

    $correct = [61,'`dv','drush ves __ENV__','PHP','0'];
    $this->internalTestSnippet($output['snippets']['untagged'][0],$correct);

  }

  /**
   * export an untagged and a tagged single command
   *
   */
  public function testUntaggedAndTaggedCmdExport(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testUntaggedAndTaggedCmdExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--file' => $s,
      '--snip' => ['`sym','`dform','`dmenu'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    //Check that the yml is correct somehow
    //Get the file that was output and test it
    $output = $this->getFile($s);

    $this->assertArrayHasKey('snippets',$output);
    $this->assertArrayHasKey('tags',$output);
    $this->assertArrayHasKey('untagged',$output['snippets']);
    $this->assertArrayHasKey(10,$output['snippets']);
    $this->assertCount(1,$output['snippets']['untagged']);
    $this->assertCount(2,$output['snippets'][10]);

    $correct = [72,'`sym','ln -s __ORIGINAL__ __SYMLINK__','PHP','0'];
    $this->internalTestSnippet($output['snippets']['untagged'][0],$correct);

    //we cannot test the data because of all the potential characters. Well we could, but a lot of trouble
    $correct = [63,'`dmenu',null,'PHP','0'];
    $this->internalTestSnippet($output['snippets'][10][0],$correct);

    $correct = [64,'`dform',null,'PHP','0'];
    $this->internalTestSnippet($output['snippets'][10][1],$correct);

    $correct = [10,'drupal'];
    $this->internalTestTag($output['tags'][0],$correct);

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
      '--file' => $s,
      '--snip' => ['`sar','`smyr',"checksym'","`d8f"],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    $output = $this->getFile($s);

    $this->assertArrayHasKey('snippets',$output);
    $this->assertCount(3,$output['snippets']);

    $this->assertArrayHasKey('untagged',$output['snippets']);
    $this->assertCount(1,$output['snippets']['untagged']);

    $this->assertArrayHasKey(15,$output['snippets']);
    $this->assertCount(1,$output['snippets'][15]);

    $this->assertArrayHasKey(3,$output['snippets']);
    $this->assertCount(2,$output['snippets'][3]);

    $this->assertArrayHasKey('tags',$output);
    $this->assertCount(2,$output['tags']);

    $correct = [3,'devserver'];
    $this->internalTestTag($output['tags'][0],$correct);

    $correct = [15,'d8'];
    $this->internalTestTag($output['tags'][1],$correct);

    $correct = [3,'checksym\'','php app\/check.php','PHP','0'];
    $this->internalTestSnippet($output['snippets']['untagged'][0],$correct);

    $correct = [32,'`smyr','sudo service mysql restart','PHP','0'];
    $this->internalTestSnippet($output['snippets'][3][0],$correct);

    $correct = [37,'`sar','sudo \/usr\/sbin\/apachectl restart','PHP','0'];
    $this->internalTestSnippet($output['snippets'][3][1],$correct);

    $correct = [70,'`d8f',null,'PHP','0'];
    $this->internalTestSnippet($output['snippets'][15][0],$correct);

  }

  /**
   * export some tags and commands
   * Importantly vu is in the commands and the tags groups.
   * It should NOT be imported twice in the snippets group for vagrant
   */
  public function testMixedExport(){

    $p = __DIR__ . '/data/library.export.dash';
    $s = __DIR__ . '/data/run/testMixedExport.yml';
    $arguments = array(
      'command' =>  $this->command->getName(),
      'dbpath'    => $p,
      '--file' => $s,
      '--snip' => ["checksym'","`d8f",'`vu'],
      '--tag' => ['vagrant','xdebug'],
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    $output = $this->getFile($s);

    $this->assertArrayHasKey('snippets',$output);
    $this->assertCount(4,$output['snippets']);

    $this->assertArrayHasKey('tags',$output);
    $this->assertCount(3,$output['tags']);

    $correct = [14,'vagrant'];
    $this->internalTestTag($output['tags'][0],$correct);
    $correct = [9,'xdebug'];
    $this->internalTestTag($output['tags'][1],$correct);
    $correct = [15,'d8'];
    $this->internalTestTag($output['tags'][2],$correct);

    $this->assertArrayHasKey('untagged',$output['snippets']);
    $this->assertCount(1,$output['snippets']['untagged']);


    $this->assertArrayHasKey(9,$output['snippets']);
    $this->assertCount(1,$output['snippets'][9]);

    //Assert that the vu command does not appear twice
    $this->assertArrayHasKey(14,$output['snippets']);
    $this->assertCount(4,$output['snippets'][14]);

    $this->assertArrayHasKey(15,$output['snippets']);
    $this->assertCount(1,$output['snippets'][15]);

    //Test a couple of values
    $correct = [67,'`vu','vagrant up','PHP','0'];
    $this->internalTestSnippet($output['snippets'][14][1],$correct);

    $correct = [52,'`xxp','export XDEBUG_CONFIG="idekey=PHPSTORM"','PHP','0'];
    $this->internalTestSnippet($output['snippets'][9][0],$correct);

    $correct = [3,'checksym\'','php app\/check.php','PHP','0'];
    $this->internalTestSnippet($output['snippets']['untagged'][0],$correct);

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
      '--file' => $s
    );
    $this->commandTester->execute($arguments);
    $disp = $this->commandTester->getDisplay();
    $this->assertRegExp('/Output saved to/', $disp);

    //Get the file that was output and test it
    $reload = $this->getFile($s);

    //assert we have the right amount of data
    $this->assertCount(6,$reload['tags']);
    $this->assertCount(7,$reload['snippets']);

    /**
     * General Data integrity checks
     */
    //test that each tag has the correct fields
    foreach ($reload['tags'] as $tag) {
      $this->assertArrayHasKey('tid',$tag);
      $this->assertArrayHasKey('tag',$tag);
    }

    //test that each snippet has the correct fields
    foreach ($reload['snippets'] as $snips) {
      foreach ($snips as $snip) {
        $this->assertArrayHasKey('sid',$snip);
        $this->assertArrayHasKey('title',$snip);
        $this->assertArrayHasKey('body',$snip);
        $this->assertArrayHasKey('syntax',$snip);
        $this->assertArrayHasKey('usageCount',$snip);
      }
    }

    /**
     * Test some untagged snippets here, as tagged snippets are received in the same way that all the other exports do and they are tested in every other unit test
     * Untagged snippets in all are a special case though as they use their own query
     */
    $this->assertCount(4,$reload['snippets']['untagged']);

    $correct = [3,"checksym'",'php app\/check.php','PHP','0'];
    $this->internalTestSnippet($reload['snippets']['untagged'][0],$correct);
    $correct = [72,'`sym','ln -s __ORIGINAL__ __SYMLINK__','PHP','0'];
    $this->internalTestSnippet($reload['snippets']['untagged'][3],$correct);

    //Test one tagged snippet for good measure
    $correct = [68,'`vr','vagrant reload','PHP','0'];
    $this->internalTestSnippet($reload['snippets'][14][2],$correct);

  }

  /**
   * @param $tag
   * @param $correct
   */
  private function internalTestTag(&$tag,$correct){
    if($correct[0] !== NULL){
      $this->assertArrayHasKey('tid',$tag);
      $this->assertRegExp('/'.$correct[0].'/',$tag['tid']);
    }
    if($correct[1] !== NULL){
      $this->assertArrayHasKey('tag',$tag);
      $this->assertRegExp('/'.$correct[1].'/',$tag['tag']);
    }
  }

  /**
   * @param $snippet
   * @param $correct
   */
  private function internalTestSnippet(&$snippet,$correct){

    if($correct[0] !== NULL){
      $this->assertArrayHasKey('sid',$snippet);
      $this->assertRegExp('/'.$correct[0].'/',$snippet['sid']);
    }
    if($correct[1] !== NULL){
      $this->assertArrayHasKey('title',$snippet);
      $this->assertRegExp('/'.$correct[1].'/',$snippet['title']);
    }
    if($correct[2] !== NULL){
      $this->assertArrayHasKey('body',$snippet);
      $this->assertRegExp('/'.$correct[2].'/',$snippet['body']);
    }
    if($correct[3] !== NULL){
      $this->assertArrayHasKey('syntax',$snippet);
      $this->assertRegExp('/'.$correct[3].'/',$snippet['syntax']);
    }
    if($correct[4] !== NULL){
      $this->assertArrayHasKey('usageCount',$snippet);
      $this->assertRegExp('/'.$correct[4].'/',$snippet['usageCount']);
    }
  }

}
