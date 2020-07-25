<?php

namespace Frostbyte\Twig\Test;

use Frostbyte\Twig\Twig;
use CodeIgniter\Test\{
    CIUnitTestCase
};

class TwigTest extends CIUnitTestCase
{
    protected $twig;
    protected $resourcesPath = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourcesPath = realpath(__DIR__ . '/../_resources/') . DIRECTORY_SEPARATOR;

        $config = new \Frostbyte\Twig\Config\Twig();

        $config->paths = [$this->resourcesPath . 'Views'];

        $this->twig = new Twig($config);
    }

    public function testLoadTwig()
    {
        $result = $this->twig->render('test.html');

        $this->assertEquals('<h1>frost-byte</h1>', $result);
    }

    public function testMacro()
    {
        $result = $this->twig->renderTemplateMacro(
            'test-macro.html',
            'test',
            ['name' => 'frost-byte']
        );

        $this->assertEquals('<h1>frost-byte</h1>', $result);
    }

    public function testAddGlobal()
    {
        $session = ['name' => 'frost-byte'];

        $this->twig->addGlobal('session', $session);
        $result = $this->twig->render('test-global.html');

        $this->assertEquals('<h1>frost-byte</h1>', $result);
    }
}
