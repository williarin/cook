# Cook

Baking recipes for any PHP package.


[![Github Workflow](https://github.com/williarin/cook/workflows/Test/badge.svg)](https://github.com/williarin/cook/actions)

<!-- TOC -->
* [Cook](#cook)
  * [Introduction](#introduction)
    * [Features](#features)
  * [Installation](#installation)
  * [Usage](#usage)
  * [Documentation](#documentation)
    * [Creating a recipe](#creating-a-recipe)
      * [Files](#files)
      * [Directories](#directories)
      * [Post install output](#post-install-output)
    * [Mergers](#mergers)
      * [Text](#text)
      * [PHP array](#php-array)
      * [JSON](#json)
      * [YAML](#yaml)
      * [Docker Compose](#docker-compose)
    * [Placeholders](#placeholders)
    * [CLI](#cli)
  * [License](#license)
<!-- TOC -->

## Introduction

Cook is a Composer plugin that executes recipes embedded in packages, in a similar fashion to [Symfony Flex](https://github.com/symfony/flex).
It can be used alongside with Flex, or in any other PHP project, as long as Composer is installed.

### Features

* Add new entries to arrays or export new arrays, filter how you want to output it
* Add content to existing files or create them (.env, Makefile, or anything else)
* Copy entire directories from your repository to the project
* Keep existing data by default or overwrite it with a CLI command
* Supports PHP arrays, JSON, YAML, text files
* Output post install instructions
* Process only required packages in the root project

## Installation

```
composer require williarin/cook
```

Make sure to allow the plugin to run. If it's not added automatically, add this in your `composer.json` file:

```json
    "config": {
        "allow-plugins": {
            "williarin/cook": true
        }
    },
```

## Usage


## Documentation

### Creating a recipe

Take a look at [williarin/cook-example](https://github.com/williarin/cook-example) for a working example of a Cook recipe.

To make your package Cook-compatible, you just have to create a valid `cook.yaml` or `cook.json` at the root directory.

The recipe schema must follow this structure:

| Top level parameter     | Type   | Comments                                                                   |
|-------------------------|--------|----------------------------------------------------------------------------|
| **files**               | array  | Individual files to be created or merged.                                  |
| **directories**         | array  | List of directories to be entirely copied from the package to the project. |
| **post_install_output** | string | A text to display after installation or update of a package.               |

#### Files

Files are a described as key-value pairs.

* Key is the path to the destination file
* Value is either an array or a string

If a string is given, it must be a path to the source file.

| Parameter            | Type                                           | Comments                                                                                                                                                                                                                                                                                                                                                                                                                  |
|----------------------|------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **type**             | string                                         | Type of file.<br/><br/>**Choices:**<ul><li>`text`</li><li>`php_array`</li><li>`json`</li><li>`yaml`</li><li>`docker_compose`</li></ul>**Default:** `text`<br/>**Optional**                                                                                                                                                                                                                                                |
| **destination**      | string                                         | Path of the destination file in the project that will be created or merged.<br/><br/>**Required**                                                                                                                                                                                                                                                                                                                         |
| **source**           | string                                         | Path of the source file in the package which content will be used to create or merge in the destination file.<br/><br/>**Required** if **content** isn't defined                                                                                                                                                                                                                                                          |
| **content**          | string                                         | Text to merge in the destination file.<br/><br/>**Required** if **source** isn't defined                                                                                                                                                                                                                                                                                                                                  |
| **entries**          | array<string, mixed>                           | Key-value pairs used to fill a PHP or JSON array.<br/><br/>**Required** if **type** is of type `php_array` or `json`                                                                                                                                                                                                                                                                                                      |
| **filters**          | {keys: array\<string>, values: array\<string>} | Filters for **entries** when **type** is `php_array`.<br/><br/>**Choices:**<ul><li>`keys`<ul><li>`class_constant` Convert the given string to a class constant. As an example, `'Williarin\Cook'` becomes `Williarin\Cook::class`</li></ul></li><li>`values`<ul><li>`class_constant` See above</li><li>`single_line_array` If the value is an array, it will be exported on a single line</li></ul></li></ul>**Optional** |
| **valid_sections**   | array\<string>                                 | Used if **type** is `yaml` or `json` in order to restrict which top-level parameters need to be merged.<br/><br/>Example: `[parameters, services]`<br/><br/>**Optional**                                                                                                                                                                                                                                                  |
| **blank_line_after** | array\<string>                                 | Used if **type** is `yaml` in order to add a blank line under the merged section.<br/><br/>Example: `[services]`<br/><br/>**Optional**                                                                                                                                                                                                                                                                                    |

#### Directories

Directories are a described as key-value pairs.

* Key is the path to the destination directory that will receive the files
* Value is the path of the source directory in the package that contains the files

#### Post install output

Maybe you want to display some text to the user after installation.  
You can use colors using [Symfony Console](https://symfony.com/doc/current/console/coloring.html) syntax.

### Mergers

#### Text

The text merger can be used to extend any text-based file such as:
* .gitignore
* .env
* Makefile

As it's the default merger, you can simply use the `destination: source` format in the recipe.

**Example 1:** merge or create a `.env` file with a given source file

Given `yourrepo/recipe/.env` with this content:
```dotenv
SOME_ENV_VARIABLE='hello'
ANOTHER_ENV_VARIABLE='world'
```
With this recipe:
```yaml
files:
    .env: recipe/.env
```
The created `.env` file will look like this:
```dotenv
###> yourname/yourrepo ###
SOME_ENV_VARIABLE='hello'
ANOTHER_ENV_VARIABLE='world'
###< yourname/yourrepo ###
```

The `###> yourname/yourrepo ###` opening comment and `###< yourname/yourrepo ###` closing comment are used by Cook to identify the recipe in the file.
If you're familiar with Symfony Flex, the syntax is the same.

**Example 2:** merge or create a `.env` file with a string input

Alternatively, you can use `content` instead of `source`, to avoid creating a file in your repository.
```yaml
files:
    .env:
        content: |-
            SOME_ENV_VARIABLE='hello'
            ANOTHER_ENV_VARIABLE='world'
```

#### PHP array

The PHP array merger adds new entries to existing arrays or creates a file if it doesn't exist.

**Example 1:** without filters

This recipe will create or merge the file `config/bundles.php` in the project.
```yaml
files:
    config/bundles.php:
        type: php_array
        entries:
            Williarin\CookExample\CookExampleBundle:
                dev: true
                test: true
```
The output will look like this:
```php
<?php

return [
    'Williarin\CookExample\CookExampleBundle' => [
        'dev' => true,
        'test' => true,
    ],
];
```

**Example 2:** with filters

Let's add some filters to our entries.
```yaml
files:
    config/bundles.php:
        # ...
        filters:
            keys: [class_constant]
            values: [single_line_array]
```
The output will look like this:
```php
<?php

return [
    Williarin\CookExample\CookExampleBundle::class => ['dev' => true, 'test' => true],
];
```

#### JSON

The JSON merger adds new entries to an existing JSON file or creates a file if needed.

**Note:** Only top-level keys are merged.

**Example:**

This recipe will add a script in the `composer.json` file of the project.
```yaml
files:
    composer.json:
        type: json
        entries:
            scripts:
                post-create-project-cmd: php -r "copy('config/local-example.php', 'config/local.php');"
```
The output will look like this:
```json5
{
    // ... existing config
    "scripts": {
        // ... other scripts
        "post-create-project-cmd": "php -r \"copy('config/local-example.php', 'config/local.php');\""
    }
}
```

#### YAML

The YAML merger adds new parameters to top-level parameters in an existing file or creates a file if needed.

Although a YAML file represents arrays like JSON or PHP arrays, the specificity of this merger is to allow YAML comments.
Therefore, instead of using `entries` which restricts content as key-value pairs, you need to describe the content to merge as a string, or a YAML file.

**Example 1:** default config

Given this existing file in `config/services.yaml`:
```yaml
parameters:
    database_url: postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8

services:
    _defaults:
        autowire: true
        autoconfigure: true
```
With this recipe:
```yaml
files:
    config/services.yaml:
        type: yaml
        content: |
            parameters:
                locale: fr

            services:
                Some\Service: ~
```
The output will look like this:
```yaml
parameters:
###> williarin/cook-example ###
    locale: fr
###< williarin/cook-example ###
    database_url: postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8

services:
###> williarin/cook-example ###
    Some\Service: ~
###< williarin/cook-example ###
    _defaults:
        autowire: true
        autoconfigure: true
```

**Example 2:** with blank lines

To make things a bit prettier, let's add a blank line below our `services` merge:
```yaml
files:
    config/services.yaml:
        # ...
        blank_line_after: [services]
```
The output will look like this:
```yaml
parameters:
###> williarin/cook-example ###
    locale: fr
###< williarin/cook-example ###
    database_url: postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8

services:
###> williarin/cook-example ###
    Some\Service: ~
###< williarin/cook-example ###
    
    _defaults:
        autowire: true
        autoconfigure: true
```

**Note:** the YAML merger is only able to prepend existing content, not append.

#### Docker Compose

The Docker Compose merger is similar to the YAML merger with only specific sections that would be merged.

Only `services`, `volumes`, `configs`, `secrets` and `networks` top-level sections will be merged.

### Placeholders

You can use several placeholders in your destination and source paths:
* `%BIN_DIR%`: defaults to `bin`
* `%CONFIG_DIR%`: defaults to `config`
* `%SRC_DIR%`: defaults to `src`
* `%VAR_DIR%`: defaults to `var`
* `%PUBLIC_DIR%`: defaults to `public`
* `%ROOT_DIR%`: defaults to `.` or, if defined, to `extra.symfony.root-dir` defined in `composer.json`

You can override any of these placeholders by defining them in your `composer.json` file.

```json
    "extra": {
        "bin-dir": "bin",
        "config-dir": "config",
        "src-dir": "src",
        "var-dir": "var",
        "public-dir": "public"
    }
```

Any other variable defined in `extra` is available with `%YOUR_VARIABLE%` in your recipe.

```json
    "extra": {
        "your-variable": "..."
    }
```

### CLI

You may want to execute your recipes after installation.
Cook provides you this command to execute all available recipes:

```bash
composer cook
```

It won't overwrite your configuration if it already exists. To overwrite everything, run:

```bash
composer cook --overwrite
```

## License

[MIT](LICENSE)

Copyright (c) 2023, William Arin
