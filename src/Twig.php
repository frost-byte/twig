<?php

namespace Frostbyte\Twig;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Exceptions\PageNotFoundException;
use Frostbyte\Twig\Cache\GoogleCloudCache;
use Twig\Environment;
use Twig\Template;
use Twig\TwigFunction as SimpleFunction;
use Twig\Loader\FilesystemLoader;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;

/**
 * Class General
 *
 * @package App\Libraries
 */
class Twig
{
    /**
     * @var array Paths to Twig templates
     */
    private $paths = [APPPATH . 'Views'];

    /**
     * @var array Functions to add to Twig
     */
    private $functions_asis = [
        'base_url',
        'site_url',
    ];

    /**
     * @var array Functions with `is_safe` option
     * @see http://twig.sensiolabs.org/doc/advanced.html#automatic-escaping
     */
    private $functions_safe = [
        'form_open',
        'form_close',
        'form_error',
        'form_hidden',
        'set_value',
    ];

    /**
     * @var array Twig Environment Options
     * @see http://twig.sensiolabs.org/doc/api.html#environment-options
     */
    private $config = [];

    /**
     * @var bool Whether functions are added or not
     */
    private $functions_added = false;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var FilesystemLoader
     */
    private $loader;

    /**
     * @var string
     */
    private $ext = '.twig';

    public function __construct(BaseConfig $config = null)
    {
        if (empty($config)) {
            $config = config('Twig');
        }

        if (isset($config->functions_asis)) {
            $this->functions_asis = array_unique(
                array_merge(
                    $this->functions_asis,
                    $config->functions_asis
                )
            );
        }

        if (isset($config->functions_safe)) {
            $this->functions_safe = array_unique(
                array_merge(
                    $this->functions_safe,
                    $config->functions_safe
                )
            );
        }

        if (isset($config->paths)) {
            $this->paths = array_unique(
                array_merge(
                    $this->paths,
                    $config->paths
                )
            );
        }

        if ($config->useCloudCache) {
            $cache = new GoogleCloudCache(
                $config->bucket['name'],
                $config->bucket['directory']
            );
        } else {
            $cache = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'twig';
        }

        // default Twig config
        $this->config = [
            'cache'      => $cache,
            'debug'      => ENVIRONMENT !== 'production',
            'autoescape' => 'html',
        ];
    }

    protected function resetTwig()
    {
        $this->twig = null;
        $this->createTwig();
    }

    protected function createTwig()
    {
        // $this->twig is singleton
        if ($this->twig !== null) {
            return;
        }

        if ($this->loader === null) {
            $this->loader = new FilesystemLoader($this->paths);
        }

        $twig = new Environment($this->loader, $this->config);

        if ($this->config['debug']) {
            $twig->addExtension(new DebugExtension());
        }

        $this->twig = $twig;
    }

    protected function setLoader($loader)
    {
        $this->loader = $loader;
    }

    /**
     * Registers a Global
     *
     * @param string $name  The global name
     * @param mixed  $value The global value
     */
    public function addGlobal($name, $value)
    {
        $this->createTwig();
        $this->twig->addGlobal($name, $value);
    }

    protected function addFunctions()
    {
        // Runs only once
        if ($this->functions_added) {
            return;
        }

        // as is functions
        foreach ($this->functions_asis as $function) {
            if (function_exists($function)) {
                $this->twig->addFunction(
                    new SimpleFunction(
                        $function,
                        $function
                    )
                );
            }
        }

        // safe functions
        foreach ($this->functions_safe as $function) {
            if (function_exists($function)) {
                $this->twig->addFunction(
                    new SimpleFunction(
                        $function,
                        $function,
                        ['is_safe' => ['html']]
                    )
                );
            }
        }

        // customized functions
        if (function_exists('anchor')) {
            $this->twig->addFunction(
                new SimpleFunction(
                    'anchor',
                    [
                        $this,
                        'safe_anchor',
                    ],
                    ['is_safe' => ['html']]
                )
            );
        }

        $this->functions_added = true;
    }

    /**
     * @param string $uri
     * @param string $title
     * @param array  $attributes [changed] only array is acceptable
     * @return string
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function safe_anchor(
        string $uri = '',
        string $title = '',
        array $attributes = []
    ) {
        $uri = esc($uri);
        $title = esc($title);

        $new_attr = [];
        foreach ($attributes as $key => $val) {
            $new_attr[esc($key)] = esc($val);
        }

        return anchor($uri, $title, $new_attr);
    }

    /**
     * @return Environment
     */
    public function getTwig()
    {
        $this->createTwig();

        return $this->twig;
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Renders Twig Template and Set Output
     *
     * @param string $view   Template filename without `.twig`
     * @param array  $params Array of parameters to pass to the template
     */
    public function display(string $view, array $params = [])
    {
        echo $this->render($view, $params);
    }

    /**
     * Renders Twig Template and Returns as String
     *
     * @param string $view   Template filename without `.twig`
     * @param array  $params Array of parameters to pass to the template
     * @return string
     */
    public function render(string $view, array $params = []): string
    {
        try {
            $this->createTwig();
            // We call addFunctions() here, because we must call addFunctions()
            // after loading CodeIgniter functions in a controller.
            $this->addFunctions();

            $view = $view . '.twig';

            return $this->twig->render($view, $params);
        } catch (LoaderError $errorLoader) {
            throw new PageNotFoundException($errorLoader);
        }
    }

    /**
     * Renders a Macro from a Twig Template
     *
     * @param string $view   Template filename without `.twig`
     * @param string $macro  Template macro name as it appears in the template
     * @param mixed  $params Array of parameters to pass to the template's macro
     * @return string The output of the rendered macro
     */
    public function renderTemplateMacro($view, $macro, $params = [])
    {
        $this->createTwig();
        // We call addFunctions() here, because we must call addFunctions()
        // after loading CodeIgniter functions in a controller.
        $this->addFunctions();

        // Load the Template
        $view = $view . '.twig';

        /**
         * @var Template $template
         */
        $template = $this->twig->loadTemplate(
            $this->twig->getTemplateClass($view),
            $view
        );

        // Render the Template's macro
        $macroMethod = "macro_{$macro}";

        // Return the rendered macro
        $result = call_user_func([$template, $macroMethod], $params);
        return "{$result}";
    }
}
