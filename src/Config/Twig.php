<?php

namespace Frostbyte\Twig\Config;

use CodeIgniter\Config\BaseConfig;

class Twig extends BaseConfig
{
    public $functions_safe = [ 'form_hidden', 'json_decode' ];
    
    public $functions_asis = [ 'current_url' ];

    public $paths = [];

    public $useCloudCache = false;
    public $keyFilePath = '';
    public $projectId = '';
    public $bucket = [
        'name' => 'bucket-name',
        'directory' => 'bucket-directory'
    ];
}
