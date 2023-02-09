<?php declare(strict_types=1);

namespace Mskocik\Vinette;

use Nette;
use Nette\Application\UI\Template;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\FileSystem;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\NotImplementedException;

class Vite
{
	public bool $enabled = false;
	private array $manifest = [];
	private string $basePath;

	public function __construct(
		private string $viteServer,
		string $manifestFile,
		string $wwwDir,
		bool $productionMode,
		bool $consoleMode,
		Nette\Http\Request $httpRequest
	){
		$this->enabled = $consoleMode
			? false
			: (!$productionMode && $httpRequest->getCookie('netteVite') === 'enabled');

		$absoluteManifestPath = $wwwDir . '/' . $manifestFile;
		if (!$this->enabled) {
			if (file_exists($absoluteManifestPath)) {
				$this->manifest = Json::decode(FileSystem::read($absoluteManifestPath), Json::FORCE_ARRAY);
			} else {
				trigger_error('Missing expected manifest file: ' . $wwwDir . '/' . $manifestFile . '. Maybe you just need to build your frontend assets or toggle vite dev mode from tracy bar.', E_USER_WARNING);
			}
		}

		$this->basePath = $httpRequest->getUrl()->getBasePath();
	}

	/**
	 * FUTURE: use for latte node, which will print build assets into template
	 */
	public function __invoke(): void
	{
		throw new NotImplementedException('Not implemented yet');
	}

	/**
	 * @throws \Nette\Utils\JsonException
	 */
	public function getAsset(string $entrypoint): string
	{
		$asset = '';
		$baseUrl = $this->basePath;

		if (!$this->enabled) {
			$asset = $this->manifest[$entrypoint]['file'];
		} else {
			$baseUrl = $this->viteServer . '/';
			$asset = $entrypoint;
		}

		return $baseUrl . $asset;
	}

	/**
	 * @throws \Nette\Utils\JsonException
	 */
	public function getCssAssets(string $entrypoint, /** @internal */ bool $isDeepCall = false): array
	{
		$assets = [];

		if (!$this->enabled) {
			$assets = $this->manifest[$entrypoint]['css'] ?? [];
			foreach ($this->manifest[$entrypoint]['imports'] ?? [] as $import) {
				$importedAssets = $this->getCssAssets($import, true);
				!empty($importedAssets) && array_push($assets, ...$importedAssets);
			}
		}

		return $isDeepCall
            ? $assets
            : array_map(fn($css) => $this->basePath . $css, $assets);
	}

	/**
	 * @throws \Nette\Utils\JsonException
	 */
	public function printTags(string $entrypoint): void
	{
		$scripts = [$this->getAsset($entrypoint)];
		$styles = $this->getCssAssets($entrypoint);

		foreach ($styles as $path) {
			echo Html::el('link')->rel('stylesheet')->href($path);
		}

		foreach ($scripts as $path) {
			echo Html::el('script')->type('module')->src($path);
		}
	}
}
