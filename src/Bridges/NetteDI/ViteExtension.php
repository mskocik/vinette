<?php declare(strict_types=1);

namespace Mskocik\Vinette\Bridges\NetteDI;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\PhpGenerator\ClassType;
use Mskocik\Vinette\Bridges\NetteTracy\VitePanel;
use Mskocik\Vinette\Vite;


class ViteExtension extends \Nette\DI\CompilerExtension
{
	private bool $debugMode = true;

    private bool $consoleMode = false;

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'manifest' => Expect::string()->required(),
			'devServer' => Expect::string('http://localhost:5173'),
            'wwwDir' => Expect::string('www'),  // relative from %vendorDir%
		]);
	}

	public function beforeCompile()
	{

        /** @var \Nette\DI\CompilerExtension */
		$parameters = $this->compiler->getExtensions()['parameters']->getConfig();
		$this->debugMode = $parameters['debugMode'];
        $this->consoleMode = $parameters['consoleMode'];

        $wwwDir = dirname($parameters['vendorDir']) . '/' . ltrim($this->config->wwwDir, '/\\');

		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('assets'))
			->setFactory(Vite::class)
			->setArguments([$this->config->devServer, ltrim($this->config->manifest, '/'), $wwwDir, !$this->debugMode, $this->consoleMode]);

		$templateFactory = $builder->getDefinition('latte.templateFactory');
		$templateFactory
			->addSetup([self::class, 'addViteToTemplate'], [$templateFactory, $this->prefix('@assets')]);

		$builder->getDefinition('latte.latteFactory')
			->getResultDefinition()
			->addSetup('addFilter', ['asset', [$this->prefix('@assets'), 'getAsset']]);
	}

	public static function addViteToTemplate(\Nette\Application\UI\TemplateFactory $factory, Vite $vite): void
	{
		$factory->onCreate[] = function(\Nette\Application\UI\Template $template) use ($vite) {
			$template->vite = $vite;
		};
	}

    /**
     * Attach panel only in debug mode and not console mode
     */
	public function afterCompile(ClassType $class)
	{
		if (!$this->debugMode || $this->consoleMode) return;

		$initialize = $this->initialization;
		$initialize->addBody('if (!Tracy\Debugger::isEnabled()) { return; }');
		$ctor = VitePanel::class . '(\'' . $this->config->devServer . '\')';
		$initialize->addBody("Tracy\Debugger::getBar()->addPanel(new $ctor);");
	}
}
