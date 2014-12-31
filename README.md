Limber
======

A tool to load WordPress as light as possible. By default WordPress loads all plugins, themes and API's on each request. But sometimes you want to build an API on top of your WordPress project. But you only want a specific set of plugins or no plugins at all. 

Using Limber you can specify what you want to load and save time on each API request.

## Options
* leave out all plugins
* specify which plugins you want to include

## Initial benchmark

A standard wordpress load:

```
require 'wp-load.php';
```

Resulted in an avarage of around 200+ ms

```
A Limber load:
$limber = new Limber();
$limber::load();
```

Resulted in an avarage load of around 100 ms

So that's roughly a 100% improvement.

## Specify plugins to load

```
$limber = new Limber();
$limber::plugin('plugin_directory_name/plugin_file.php');
$limber::plugin('another_plugin_directory_name/another_plugin_file.php');
$limber::load();

``


## more to come
* optional loading of theme
* optional loading of APIs
* optional loading of template functions
* simplify the code to use Limber
* clean up and extract parts of the loading into a seperate methods



