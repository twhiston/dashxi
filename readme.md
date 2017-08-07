# DashXi
Dash(e)X(port)i(mport)
---
[![Build Status](https://drone.twhiston.cloud/api/badges/twhiston/dashxi/status.svg)](https://drone.twhiston.cloud/twhiston/dashxi)

A php command line tool to import and export Dash snippets.
#### Why PHP?
I work for a web company and it makes more sense to make it in a language that everyone there uses, so it stands a better chance of receiving maintenance and upgrades.

## Install

The easiest way to install is to download the .phar
```
curl -L -O -sS https://github.com/twhiston/dashxi/releases/download/1.0.0/dashxi.zip
unzip dashxi.zip
sudo chmod 700 dashxi.phar
```

If you want to use the command globally you can move it to your bin (if this fails try adding sudo in front) and remove the .phar extension. Examples below will follow this usage pattern.
```
mv dashxi.phar /usr/local/bin/dashxi
```

Otherwise you can download the repo and run composer update (requires composer)
```
git clone git@github.com:twhiston/dashxi.git
composer install
```

Use application.php to access the commands
```
./application.php import '/Users/x/library/Application Support/Dash/library.dash' --file=''
```

or build the phar yourself (requires box)
```
git clone git@github.com:twhiston/dashxi.git
composer update
box build -v
```


# Commands

## help

For more information use
```
dashxi help
```

or for individual commands append the name to help
```
dashxi help export
```

## export
Export a set of all snippets, snippets by tag, or individual snippets by name.
Note that currently there is no way to export only the set of untagged commands. You could specify each command individually, but it would be easier to simply create a tag for them in Dash
```
dashxi export '/Users/x/Library/Application Support/Dash/library.dash' --tag='drupal' --tag='symfony' --snip='`cstm' --snip='doit' --file='/Users/x/DashBackups/backup.yml'
```

#### Arguments
- database path. The location of your Dash library, this is usually /Users/x/Library/Application Support/Dash/

#### Options
- tag tags to include in the export, multiple values allowed
- snip individual snippets to add, multiple values allowed
- file Optional file output location and name. must end with .yml. If non is specified output will be printed to the console


## import
Import a previously generated export
``` 
dashxi import '/Users/x/library/Application Support/Dash/library.dash' --file='/path/to/backup/data.yml' 
```

#### Arguments
- database path. The location of your Dash library, this is usually /Users/x/Library/Application Support/Dash/

#### Options
- file full path to file you want to import with extension (currently required to run)
- backup TRUE/FALSE backup the existing db before doing the import, defaults to TRUE


## backup:list
List any backups created by DashXi
```
dashxi backup:list '/Users/x/library/Application Support/Dash/'
```

#### Arguments
- database path. The location of your Dash library, this is usually /Users/x/Library/Application Support/Dash/

## backup:restore
Restore any backups created by DashXi
```
dashxi backup:restore '/Users/x/library/Application Support/Dash/' '/Users/x/library/Application Support/Dash/library.dash.backup.123456'
```

#### Arguments
- database path. The location of your Dash library, this is usually /Users/x/Library/Application Support/Dash/
- backup path. Path to backup library.dash.backup.timestamp file to restore. Export script saves these in the default /Users/x/Library/Application Support/Dash/ folder

## backup:clean
Delete any backups created by DashXi
```
dashxi backup:clean '/Users/x/library/Application Support/Dash/'
```

#### Arguments
- database backup path. The location of your Dash backup library, this is usually /Users/x/Library/Application Support/Dash/
