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
APIDoc Tester scripted tool with a visual interface. This means that you will not have any action executed before you visual confirm and ackowledge what is going to be done. Here is an example of what the interface looks like:

### Command options/flags

#### api_dir
	[optional] The directory where API classes/methods are kept
	[default] /app/Http/Controlers
	If no files/APIDoc are found the script will raise an exception

#### output_dir
	[optional] The directory where APIDoc will store its files
	[default] /storage/framework/testing/apidoc
	APIDoc natively generates HTML, CSS and JS files for the documentation. The file this command needs is the api_data.json file that contains the entire structure of the APIDoc blocks.

#### keep_files
	[optional] This will stop this script from deleting all APIDoc files it has generated, including the api_data.json file.
	[default] false

## Output

### Visual Feedback

The tests will be run using the generated APIDoc .json file and will be shown on screen while these tests are happening

### APIDocs files

By default this script will delete everything it creates, including the APIDocs files unless the "keep_files" parameter is set to true
