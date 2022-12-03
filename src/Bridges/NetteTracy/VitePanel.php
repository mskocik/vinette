<?php declare(strict_types=1);

namespace Mskocik\Vinnete\Bridges\NetteTracy;

use Tracy\IBarPanel;

class VitePanel implements IBarPanel
{
    public function getTab()
    {
        return file_get_contents(__DIR__ . '/Vite.html');
    }

    public function getPanel()
    {
        return '';
    }
}
