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
use twhiston\twLib\Str;
use Symfony\Component\Finder\Finder;


/**
 * Class CleanBackups
 * Clean up backups that the import process may have made
 * @package twhiston\DashXi\Commands
 */
class ListBackups extends Command {

  /**
   * Set up the console command dashxi:clean
   */
  protected function configure() {
    $this
      ->setName('backup:list')
      ->setDescription(
        'List backups'
      )
      ->addArgument(
        'dbpath',
        InputArgument::REQUIRED,
        'Path to backups. Usually /Users/x/Library/Application Support/Dash/'
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
      $output->writeln('<error>Cannot Find Backup path</error>');
      return;
    }

    $finder = new Finder();
    $iter = $finder->files()->depth('== 0')->in($dbpath)->getIterator();

    foreach ($iter as $file) {
      $fn = $file->getFilename();
      if(Str::startsWith($fn,'library.dash.backup')){
        $output->writeln($fn);
      }
    }
  }

}