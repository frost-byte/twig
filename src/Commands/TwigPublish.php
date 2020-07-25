<?php

namespace Frostbyte\Twig\Commands;

use Config\Autoload;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\BaseCommand;

class TwigPublish extends BaseCommand
{

    protected $group       = 'frost-byte';
    protected $name        = 'twig:install';
    protected $description = 'Twig config file installer.';

    /**
     * The path to Frostbyte\Twig\src directory.
     *
     * @var string
     */
    protected $sourcePath;

    /**
     * Run the command, which will install the config file
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $this->discoverSourcePath();
        $this->installConfig();
        CLI::write(
            'Success! The config file was generated.',
            'green'
        );
    }

    /**
     * Discover the application's current source path
     */
    protected function discoverSourcePath()
    {
        $this->sourcePath = realpath(__DIR__ . '/../');
        if ($this->sourcePath == '/' || empty($this->sourcePath)) {
            CLI::error('Unable to determine the correct source directory.');
            exit();
        }
    }

    /**
     * Install the config file
     */
    protected function installConfig()
    {
        $path = "{$this->sourcePath}/Config/Twig.php";
        $content = file_get_contents($path);

        $content = str_replace(
            'namespace Frostbyte\Twig\Config',
            "namespace Config",
            $content
        );

        $content = str_replace(
            'extends BaseConfig',
            "extends \Frostbyte\Twig\Config\Twig",
            $content
        );

        $this->createFile('Config/Twig.php', $content);
    }

    /**
     * Create the config file at the specified path
     *
     * @param string $path  The directory where the config file will be created.
     * @param string $content The contents of the config file that will be written.
     */
    protected function createFile(string $path, string $content)
    {
        $config = new Autoload();
        $appPath = $config->psr4[APP_NAMESPACE];
        $directory = dirname($appPath . $path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (
            file_exists($appPath . $path) &&
            CLI::prompt(
                'Do you want to replace the existing Config file?',
                ['y', 'n']
            ) == 'n'
        ) {
            CLI::error('Cancelled');
            exit();
        }

        try {
            write_file($appPath . $path, $content);
        } catch (\Exception $e) {
            $this->showError($e);
            exit();
        }

        $path = str_replace($appPath, '', $path);
        CLI::write(CLI::color('Created: ', 'yellow') . $path);
    }
}
