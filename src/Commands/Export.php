<?php
/**
 * Created by PhpStorm.
 * User: Thomas Whiston
 * Date: 06/01/2016
 * Time: 12:23
 */

namespace twhiston\DashXi\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use twhiston\twLib\Str;


/**
 * Class Export
 * Exports Dash commands based on some input params. Uses PDO for the db connection because its easy and we dont need extensive doctrine style object creation
 * @package twhiston\DashXi\Commands
 */
class Export extends Command {

  /**
   * Ratabase Connection
   * @var \PDO
   */
  private $db;

  /**
   * Set up the console command dashxi:export
   */
  protected function configure() {
    $this
      ->setName('export')
      ->setDescription(
        'Export Dash Commands by optional snippet abbreviation or tag'
      )
      ->addArgument(
        'dbpath',
        InputArgument::REQUIRED,
        'Path to library.dash. Usually /Users/x/Library/Application Support/Dash/library.dash'
      )
      ->addOption(
        'tag',
        NULL,
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'tag groups to output'
      )
      ->addOption(
        'snip',
        NULL,
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'snippet abbreviations to include in output. Be aware that you might need to close dash to run this or it will try to expand your commands'
      )
      ->addOption(
        'savepath',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'yml output save path including filename.yml, if non specified output is printed to the console'
      );
  }


  /**
   * Run them jewels
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /**
     * Test our input arguments
     */
    $name = $input->getArgument('dbpath');

    //Test db file exists
    $fs = new Filesystem();
    if (!$fs->exists($name)) {
      $output->writeln('<error>Cannot Find DB</error>');
      return;
    }

    $save = $input->getOption('savepath');//null or path
    if ($save !== NULL) {
      if (!Str::endsWith($save, '.yml')) {
        $output->writeln(
          '<error>Save path must end with the .yml filename</error>'
        );
        return;
      }
    }
    //Try to open the db or die
    $this->db = new \PDO('sqlite:' . $name) or die("cannot open the database");

    /**
     * Start Processing
     */
    $results = [];//Holds all our results, to be made into yaml

    $tags = $input->getOption('tag');
    $cmds = $input->getOption('snip');

    //If there are no tags or commands we need to export everything
    if (empty($tags) && empty($cmds)) {
      //Get all tags
      $tquery = $this->db->prepare("SELECT * FROM tags");
      $tquery->execute($tags);
      $tres = $tquery->fetchAll(\PDO::FETCH_ASSOC);
      if (!empty($tres)) {
        $results['tags'] = $tres;
      }
      else {
        //ERROR
        return;
      }
      $results['snippets'] = $this->getSnippetsFromTagSet($tres);

      //We need to get untagged snippets as well
      $tquery = $this->db->prepare("SELECT * FROM snippets s WHERE NOT EXISTS (SELECT s.sid from tagsIndex t WHERE s.sid = t.sid)");
      $tquery->execute();
      $tres = $tquery->fetchAll(\PDO::FETCH_ASSOC);
      $results['snippets']['untagged'] = $tres;

      $this->makeOutput($results, $save, $output);
      return;
    }

    /**
     * Deal with tags
     */
    if (!empty($tags)) {
      //Get our requested tags into the form of a query and execute it
      $tres = $this->query($tags, 'tags', 'tag');
      if (!empty($tres)) {
        $results['tags'] = $tres;
        $results['snippets'] = $this->getSnippetsFromTagSet($tres);
      }
    }

    /**
     * Deal with commands
     * Commands are a bit more tricky as the lookup is a bit backwards compared to by tag or all
     * Also we have to content with untagged snippets
     */
    if (!empty($cmds)) {
      //Get our requested commands into the form of a query and execute it
      $cres = $this->query($cmds, 'snippets', 'title');
      $sids = [];
      $untagged = [];
      foreach ($cres as $key => $val) {
        $sids[] = $val['sid'];
        $sres = $this->query(array($val['sid']), 'tagsIndex', 'sid');
        if(!empty($sres)){
          //check if it exists already in the snippet table
          $insert = TRUE;
          if(array_key_exists('snippets',$results) && array_key_exists($sres[0]['tid'],$results['snippets'])){
            foreach ($results['snippets'][$sres[0]['tid']] as $ekey => $existant) {
              if(in_array($val['sid'],$existant)){
                $insert = FALSE;
              }
            }
          }
          if($insert === TRUE){
            $results['snippets'][$sres[0]['tid']][]=$val;
          }

        } else {
          //If this is empty we have an untagged snippet
          $results['snippets']['untagged'][]=$val;
        }
      }

      //Look up tagged snippets
      $sres = $this->query($sids, 'tagsIndex', 'sid');
      $tids = [];
      foreach ($sres as $key => $val) {
        $tids[] = $val['tid'];
      }
      if(!empty($tids)){
        //Get the tags and merge them into the existing results
        $tres = $this->query($tids, 'tags', 'tid');
        if(isset($results['tags'])){
          $results['tags'] = $this->mergeResults($results['tags'], $tres, 'tid');
        } else {
          $results['tags'] = $tres;
        }
      }
    }
    $this->makeOutput($results, $save, $output);
  }

  /**
   * Do a simple pdo query and return some results. All db commands bar the all tag lookup command go through this
   * @param $cmds
   * @param $table
   * @param $field
   * @return mixed
   */
  private function query($cmds, $table, $field) {
    $qMarks = str_repeat('?,', count($cmds) - 1) . '?';
    $cquery = $this->db->prepare(
      "SELECT * FROM ($table) WHERE ($field) IN ($qMarks)"
    );
    $cquery->execute($cmds);
    return $cquery->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Merge 2 sets of results, used for the tags
   * @param $set1
   * @param $set2
   * @param $matchstring
   * @return array
   */
  private function  mergeResults(&$set1, &$set2, $matchstring) {
    //Merge our new tags with our old, disguard any repetition
    $newtags = [];
    foreach ($set2 as $id => $tag) {
      //otherwise use the string
      $add = TRUE;
      foreach ($set1 as $ikey => $ival) {
        if (in_array($tag[$matchstring], $ival)) {
          $add = FALSE;
        }
      }
      if ($add) {
        $newtags[] = $tag;
      }
    }
    if (!empty($newtags)) {
      return array_merge($set1, $newtags);
    }
    else {
      return $set1;
    }
  }

  /**
   * From an array of tag data get the snippets that match it
   * @param $tagSet
   * @return array
   */
  private function getSnippetsFromTagSet(&$tagSet) {
    //If we have some results then we need to get all the commands for this tag
    $q = [];
    foreach ($tagSet as $t) {
      $q[] = intval($t['tid']);
    }

    $tdres = $this->query($q, 'tagsIndex', 'tid');
    //Now, and finally we can get the actual commands for these tags
    $f = [];
    foreach ($tdres as $t) {
      $f[$t['tid']][] = intval($t['sid']);
    }
    //We need to query for each tid, and then set some things up for the output
    $output = [];
    foreach ($f as $tid => $values) {
      $sres = $this->query($values, 'snippets', 'sid');
      if (!empty($sres)) {
        //Save our results under the right tid
        $output[$tid] = $sres;
      }
    }
    return $output;
  }


  /**
   * Either save to a file or output to console depending on savepath option value
   * @param $results
   * @param $savepath
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  private function makeOutput(&$results, $savepath, OutputInterface $output) {
    $yaml = Yaml::dump($results);
    if ($savepath != NULL) {
      file_put_contents($savepath, $yaml);
      $output->writeln('Output saved to ' . $savepath);
    }
    else {
      $output->write($yaml);
    }
  }

}