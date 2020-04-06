Second PHP-project
====================
[![Maintainability](https://api.codeclimate.com/v1/badges/e33249b4d24a1cd9f0f1/maintainability)](https://codeclimate.com/github/Colonizator1/php-project-lvl2/maintainability) [![Build Status](https://travis-ci.com/Colonizator1/php-project-lvl2.svg?branch=master)](https://travis-ci.com/Colonizator1/php-project-lvl2) [![Test Coverage](https://api.codeclimate.com/v1/badges/e33249b4d24a1cd9f0f1/test_coverage)](https://codeclimate.com/github/Colonizator1/php-project-lvl2/test_coverage) ![CI](https://github.com/Colonizator1/php-project-lvl2/workflows/CI/badge.svg)

## Installing
```bash
composer require colonizator/getdiff
```
or global install
```bash
composer global require colonizator/getdiff
```

## Usage
```bash
getdiff --format pretty /path/to/file/before.json /path/to/file/after.json
```
getdiff works with .json, .yaml types.
--format options: pretty, plain, json
[![asciicast](https://asciinema.org/a/fjLtL62f5ULtOJseGYAqDW9ju.svg)](https://asciinema.org/a/fjLtL62f5ULtOJseGYAqDW9ju)