# Twig

Twig 3.0+ integration for CodeIgniter 4

## Installation
### Composer
    composer require frost-byte/twig
### Manual
Download the repository into your app's ThirdParty directory and update your CI4 Autoload Config.

**app/Config/Autoload.php**
```php
$psr4 = [
    'Config'            => APPPATH . 'Config',
    APP_NAMESPACE       => APPPATH,
    'App'               => APPPATH,
    'frost-byte\Twig'   => APPPATH . 'ThirdParty/twig/src'
];
```

## Configuration
Install the Configuration file for the integration to `app/Config/Twig.php` with the following command:

    ./spark twig:install

## Usage
### Library
```php
$twig = new \FrostByte\Twig\Twig();
$twig->display('view.html', []);
```
### Service
```php
$twig = new Config\Services::twig();
$twig->display('view.html', []);
```
### Helper
Add the helper to your `BaseController`...

```php
    protected $helpers = ['twig_helper'];
```

Then use it
```php
$twig = twig_instance();
$twig->display('view.html', []);
```

## Features
### Globals

#### Source
```php
$twig = new \FrostByte\Twig\Twig();
$session = Config\Services::session();
$session->set(['name' => 'frost-byte']);
$twig->addGlobal('session', $session);
$twig->display('view.html', []);
```

#### Template
```html
<body>
    <h1>Greetings, {{session.get('name')}}!</h1>
</body>
```

### Macros
    If organize a collection of macros into in one file, this method allows you to call an individual macro and
    use the result within your app.


#### Template - **macros.html.twig**
```twig
{% macro greet(params) %}
<h1>Hello, {{params.name}}!</h1>
{% endmacro %}
```

#### Source
```php
$twig = new \FrostByte\Twig\Twig();
$result = $twig->renderTemplateMacro(
    'macros.html',
    'greet',
    ['name' => 'frost-byte']
);
```

### Caching in Google Cloud Storage

    When running your application as a flexible or standard app in Google App Engine, one option for caching files is to use a Google Cloud Storage Bucket. (You would typically have limited access/resources for altering local files, once the app is deployed.

    Another option would be to pre-generate your template cache and deploy it with your app.)

    This integration provides an implementation of a Twig CacheInterface.

    Set the following values in the config to enable the Cloud Storage cache:
```php
public $useCloudCache = true;

public $bucket = [
    'name' => 'your-gcs-bucket-name',
    'directory' => 'directory-in-your-bucket'
];
```

### Service Account Authentication

If you are hosting your app using Google Compute Engine or Google App Engine, then the authentication is already performed, and configuring the path to the key file is unnecessary.

However, when using the GoogleCloudCache outside of GCE or GAE, you can set your configuration to point to a key file containing your service account credentials and by setting your Google Cloud **projectId** in **app/Config/Twig.php**.


```php
public $keyFilePath = 'path/to/your/keyfile.json';
public $projectId = 'your-gcloud-project-id';
```

## Tests
If you used composer to add the integration to your project, then you can run its tests...
`vendor\bin\phpunit vendor\frost-byte\twig\tests`

## Notes

You can learn more about authenticating your service with Google Cloud Services [here](auth).

[auth]: https://github.com/googleapis/google-cloud-php/blob/master/AUTHENTICATION.md