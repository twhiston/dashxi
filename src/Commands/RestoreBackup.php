<?php
/**
 * Created by PhpStorm.
 * User: Thomas Whiston
 * Date: 06/01/2016
 * Time: 20:46
 */

namespace twhiston\DashXi\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use twhiston\twLib\Str\Str;
use Symfony\Component\Finder\Finder;


/**
 * Class CleanBackups
 * Clean up backups that the import process may have made
 * @package twhiston\DashXi\Commands
 */
class RestoreBackup extends Command {

  /**
   * Set up the console command dashxi:clean
   */
  protected function configure() {
    $this
      ->setName('backup:restore')
      ->setDescription(
        'Clean up backups'
      )
      ->addArgument(
        'dbpath',
        InputArgument::REQUIRED,
        'Path to library.dash. Usually /Users/x/Library/Application Support/Dash/'
      )
    ->addArgument(
      'backup',
      InputArgument::REQUIRED,
      'Path to backup library.dash.backup.timestamp. Export script saves these in the default /Users/x/Library/Application Support/Dash/'
    );
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $dbpath = $input->getArgument('dbpath');

    if (!Str::endsWith($dbpath, '/')) {
      $output->writeln(
        '<error>Library path must end with /</error>'
      );
      return;
    }

    //Test db file exists
    $fs = new Filesystem();
    if (!$fs->exists($dbpath)) {
      $output->writeln('<error>Cannot Find DB path</error>');
      return;
    }

    $bpath = $input->getArgument('backup');

    if(!$fs->exists($bpath)){
      $output->writeln('<error>Cannot Find Backup</error>');
      return;
    }

    $parts = explode('/',$bpath);
    $l = array_pop($parts);
    if (!Str::startsWith($l, 'library.dash.backup')) {
      $output->writeln(
        '<error>backup file name expected to start with library.dash.backup</error>'
      );
      return;
    }

    //If we got here we need to just do some file operation
    $fs = new Filesystem();
    $d = __DIR__.'/data/restore/';
    $s = $dbpath.'library.dash';
    $fs->remove($s);
    $fs->copy($bpath, $s);
    $output->writeln('Restored backup '.$l);




  }

}