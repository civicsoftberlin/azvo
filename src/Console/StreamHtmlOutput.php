<?php

namespace App\Console;

use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SensioLabs\AnsiConverter\Theme\SolarizedXTermTheme;
use Symfony\Component\Console\Output\Output;

class StreamHtmlOutput extends Output
{
    private AnsiToHtmlConverter $converter;

    public function __construct()
    {
        parent::__construct();
        $theme = new SolarizedXTermTheme();
        $this->converter = new AnsiToHtmlConverter($theme);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        // ANSI-Codes in HTML umwandeln
        $html = $this->converter->convert($message);

        // In ein <div> oder <pre> wrappen für sauberes Styling
        //echo '<span class="console-line">' . $html . '</span>';
        echo $html;

        // WICHTIG: Sofort an den Browser senden!
        flush();
    }

    public function __destruct()
    {
        echo "\n";
    }

}
