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
class CleanBackups extends Command {

  /**
   * Set up the console command dashxi:clean
   */
  protected function configure() {
    $this
      ->setName('clean')
      ->setDescription(
        'Clean up backups'
      )
      ->addArgument(
        'dbpath',
        InputArgument::REQUIRED,
        'Path to library.dash. Usually /Users/x/Library/Application Support/Dash/'
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

    $finder = new Finder();
    $iter = $finder->files()->depth('== 0')->in($dbpath)->getIterator();

    $count = 0;
    foreach ($iter as $file) {
      $fn = $file->getFilename();
      if(Str::startsWith($fn,'library.dash.backup')){
        $count += 1;
        $p = $file->getPath(). $fn;
        unlink($p);
      }
    }
    $output->writeln('Deleted '.$count.' backups');
  }

}