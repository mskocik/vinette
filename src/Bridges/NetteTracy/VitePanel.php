<?php declare(strict_types=1);

namespace Mskocik\Vinette\Bridges\NetteTracy;

use Tracy\IBarPanel;

class VitePanel implements IBarPanel
{
    public function __construct(private string $devServerUrl) {}

    public function getTab()
    {
        $url = $this->devServerUrl;
        ob_start();
        require __DIR__ . '/Vite.phtml';
        return ob_get_clean();
    }

    public function getPanel()
    {
        return '';
    }
}
