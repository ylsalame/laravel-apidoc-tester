# Laravel APIDoc Tester

Artisan command for Laravel that will use the APIDoc notations to create and execute API tests

## Installation

1) Add `ylsalame/laravelapidoctester` to composer.json

```
"require": {
	"ylsalame/laravelapidoctester": "~0.1"
}
```

2) Update Composer from the CLI:

```
composer update
```

## Usage (Artisan)

```
php artisan apidoctester
```
APIDoc Tester scripted tool with a visual interface. This means that you will not have any action executed before you visualy confirm and ackowledge what is going to be done. Here is an example of what the interface looks like:

### Command options/flags

#### api_dir
	[optional] The directory where API classes/methods are kept (recursively scanned)
	[default] /app/Http/Controlers
	If no files with APIDoc blocks are found, the script will raise an exception

#### output_dir
	[optional] The directory where APIDoc/Codeception files will be stored
	[default] /storage/app/testing/apidoc
	APIDoc and Codeception generates multiple files and this arg will define where these will be stored

#### keep_files
	[optional] This stops the script from deleting all APIDoc/Codeception files it has generated, including the api_data.json file.
	[default] false

## Output

### Visual Feedback

The tests will be run using the generated APIDoc .json file and will be shown on screen while they are happening

### APIDocs files

By default this script deletes everything it creates, including the APIDocs/Codeception files unless the "keep_files" parameter is set to true
