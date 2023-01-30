<?php declare(strict_types=1);

namespace Mskocik\Vinette;

use Nette;
use Nette\Application\UI\Template;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\FileSystem;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;


class Vite
{
    public bool $enabled = false;
    private array $manifest = [];
    private string $basePath;

    public function __construct(
        private string $viteServer,
        string $manifestFile,
        ?string $assetPath,
        string $wwwDir,
        bool $productionMode,
        Nette\Http\Request $httpRequest
    ){
        $this->enabled = (!$productionMode && $httpRequest->getCookie('netteVite') !== 'false');

        $absoluteManifestPath = $wwwDir . '/' . $manifestFile;
        if (!$this->enabled) {
            if (file_exists($absoluteManifestPath)) {
                $this->manifest = Json::decode(FileSystem::read($absoluteManifestPath), Json::FORCE_ARRAY);
            } else {
                trigger_error('Missing manifest file: ' . $manifestFile, E_USER_WARNING);
            }
        }

        if (!$assetPath) {
            $assetPath = str_replace('manifest.json', '', $manifestFile);
        }
        $this->basePath = $httpRequest->getUrl()->getBasePath() . ltrim($assetPath);
    }

    /**
     * Hook itself into template
     * @param DefaultTemplate|Template $template
     */
    public function __invoke($template): void
    {
        $template->vite = $this;
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
    public function getCssAssets(string $entrypoint): array
    {
        $assets = [];

        if (!$this->enabled) {
            $assets = $this->manifest[$entrypoint]['css'] ?? [];
            foreach ($this->manifest[$entrypoint]['imports'] ?? [] as $import) {
                $importedAssets = $this->getCssAssets($import);
                !empty($importedAssets) && array_push($assets, ...array_map(fn($css) => $this->basePath . $css, $importedAssets));
            }
        }

        return $assets;
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
