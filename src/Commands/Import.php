<?php
/**
 * Created by PhpStorm.
 * User: Thomas Whiston
 * Date: 06/01/2016
 * Time: 17:09
 */

namespace twhiston\DashXi\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;
use twhiston\twLib\Str;


/**
 * Class Import
 * @package twhiston\DashXi\Commands
 */
class Import extends Command {


  /**
   * @var
   */
  private $db;

  /**
   * Set up the console command dashxi:export
   */
  protected function configure() {
    $this
      ->setName('import')
      ->setDescription(
        'Import Dash Commands from yml'
      )
      ->addArgument(
        'dbpath',
        InputArgument::REQUIRED,
        'Path to library.dash. Usually /Users/x/Library/Application Support/Dash/library.dash'
      )
      ->addOption(
        'file',
        NULL,
        InputOption::VALUE_REQUIRED,
        'full path to file you want to import with extension'
      )
      ->addOption(
        'backup',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'backup your existing dash database before doing the import',
        TRUE
      );
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $dbpath = $input->getArgument('dbpath');

    //Test db file exists
    $fs = new Filesystem();
    if (!$fs->exists($dbpath)) {
      $output->writeln('<error>Cannot Find DB</error>');
      return;
    }

    $fpath = $input->getOption('file');//null or path

    //if both are null error out
    if ($fpath === NULL) {
      $output->writeln('<error>Must specify file</error>');
      return;
    }

    //If there is a path then try to get the file
    if (!Str::endsWith($fpath, '.yml')) {
      $output->writeln(
        '<error>Save path must end with the .yml filename</error>'
      );
      return;
    }
    if (!$fs->exists($fpath)) {
      $output->writeln('<error>Cannot Find Import File</error>');
      return;
    }

    //Try to open the db or die
    $this->db = new \PDO(
      'sqlite:' . $dbpath
    ) or die("cannot open the database");

    //Get the input data
    $yaml = new Parser();
    $import = $yaml->parse(file_get_contents($fpath));

    //backup the db if backup is set, defaults to TRUE
    if ($input->getOption('backup') === TRUE) {
      $bpath = $dbpath . '.backup.' . time();
      if ($fs->exists($bpath)) {
        $fs->remove($bpath);
      }
      $fs->copy($dbpath, $bpath);
    }

    /**
     * Do the import.
     * Firstly import the tags
     */

    //We need to check if the tag exists, if it does then we need to record what id it has
    $tidActual = [];
    $tids = [];
    foreach ($import['tags'] as $key => $tag) {
      $tids[] = $tag['tag'];
    }
    $existing = $this->query($tids, 'tags', 'tag');
    foreach ($import['tags'] as $ikey => $ivalue) {
      $found = FALSE;
      foreach ($existing as $ekey => $evalue) {
        if ($ivalue['tag'] == $evalue['tag']) {
          //if they have the same tag
          $found = TRUE;
          $evalue['import_tid'] = $ivalue['tid'];
          $tidActual[$ivalue['tid']] = $evalue;
        }
      }
      if($found === FALSE){
        //Create a new tid and capture its new and old value
        $insert = $this->db->prepare('INSERT INTO tags (tag) VALUES (:tag) ');
        $insert->bindParam(':tag',$ivalue['tag']);
        $insert->execute();
        $existing = $this->query(array($ivalue['tag']), 'tags', 'tag');
        $data = array_shift($existing);
        $data['import_tid'] = $ivalue['tid'];
        $tidActual[$ivalue['tid']] = $data;
      }
    }

    //We now have an array of tid names keyend by their import key and containing their differing ID's where appropriate. So we can create the snippets
    foreach ($import['snippets'] as $otid => $snippets) {
      $tagdata = $tidActual[$otid];
      foreach ($snippets as $snippet) {

        //We need to see if the snippet exists already, if so ignore it
        $cquery = $this->db->prepare(
          "SELECT * FROM snippets WHERE title = :title AND body = :body AND syntax = :syntax"
        );
        $cquery->bindParam(':title',$snippet['title']);
        $cquery->bindParam(':body',$snippet['body']);
        $cquery->bindParam(':syntax',$snippet['syntax']);
        $cquery->execute();
        $temp = $cquery->fetch(\PDO::FETCH_ASSOC);
        if($temp == false){
          //If the snippet does not exist make it
          $insert = $this->db->prepare('INSERT INTO snippets (title, body, syntax, usageCount) VALUES (:title, :body, :syntax, :usageCount) ');
          $insert->bindParam(':title',$snippet['title']);
          $insert->bindParam(':body',$snippet['body']);
          $insert->bindParam(':syntax',$snippet['syntax']);
          $insert->bindParam(':usageCount',$snippet['usageCount']);
          //TODO - bailout if failed
          $insert->execute();
          $newsid = $this->db->lastInsertId();

          //Link it to a tag
          //TODO - bailout if snippet untagged
          $insert = $this->db->prepare('INSERT INTO tagsIndex (tid, sid) VALUES (:tid, :sid) ');
          $insert->bindParam(':tid',$tidActual[$otid]['tid']);
          $insert->bindParam(':sid',$newsid);
          //TODO - bailout if failed
          $insert->execute();

          $output->writeln('Added Command '.$snippet['title'].'to tag: '.$tidActual[$otid]['tag']);
        }
      }
    }

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

}