# Change Log
All notable changes to this project will be documented in this file.

## 0.3.0.0 - 2019-05-06
### Added
* Argument unpacking for `Structure::processSplObjects` to provide some additional data to callback

## 0.2.0.0 - 2019-04-14
### Added
* tests
* Event handling
* Some new functionalities
* Execute callbacks on file/dir list
### Changed
* PHP version upgraded to 7.1
* Code refactor and improvements
* Documentation update

## 0.1.1.0 - 2018-12-20
### Changed
* Fixed creating directory instead of copy file for `Fs::copy` when target is file

## 0.1.0.3 - 2018-12-08
### Added
* CHANGELOG.md
### Changed
* composer version

## 0.1.0.2 - 2018-09-05
### Added
* Readme description
### Changed
* readDirectory will return array instead null, to make data return more consistent
### Deleted
* composer.lock

## 0.1.0.1 - 2018-05-18
### Changed
* Fixed version in composer

## 0.1.0.0 - 2018-05-15
### Added
* Initial commit
* .gitignore & phpunit update & added license
* Added config for codeclimate, scrutinizer and travis
* Added some required libraries, changed some deprecated methods and some other minor changes
### Changed
* Fixed namespaces and changed some file structure
* Some code convention changes
* Modified structure and added some project files