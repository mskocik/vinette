<?php declare(strict_types=1);

namespace Mskocik\Vinnete\Bridges\NetteDI;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\PhpGenerator\ClassType;
use Mskocik\Vinnete\Bridges\NetteTracy\VitePanel;
use Mskocik\Vinnete\Vite;


class ViteExtension extends \Nette\DI\CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'manifest' => Expect::string()->required(),
            'assetPath' => Expect::string(null)->nullable(),
            'devServer' => Expect::string('http://localhost:5173'),
        ]);
    }

    public function beforeCompile()
    {
        /** @var \Nette\DI\CompilerExtension */
        $parameters = $this->compiler->getExtensions()['parameters']->getConfig();
        $wwwDir = $parameters['wwwDir'];
        $debugMode = $parameters['debugMode'];
        
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('assets'))
            ->setFactory(Vite::class)
            ->setArguments([$this->config->devServer, ltrim($this->config->manifest, '/'), $this->config->assetPath, $wwwDir, !$debugMode]);

        $builder->getDefinition('latte.templateFactory')
            ->addSetup('$onCreate', [[0 => $this->prefix('@assets')]]);

		$builder->getDefinition('latte.latteFactory')
            ->getResultDefinition()
			->addSetup('addFilter', ['asset', [$this->prefix('@assets'), 'getAsset']]);
    }

    public function afterCompile(ClassType $class)
    {
        $initialize = $this->initialization;
		$initialize->addBody('if (!Tracy\Debugger::isEnabled()) { return; }');
        $initialize->addBody('Tracy\Debugger::getBar()->addPanel(new ' . VitePanel::class . ');');
    }
}