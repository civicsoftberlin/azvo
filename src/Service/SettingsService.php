<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SettingsService
{
    private array $settings;
    private string $filePath;

    public function __construct(ParameterBagInterface $params)
    {
        $this->filePath = $params->get('kernel.project_dir') . '/config/settings.json';
        $this->settings = json_decode(file_get_contents($this->filePath), true);
    }

    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function save(array $newSettings): void
    {
        $this->settings = array_merge($this->settings, $newSettings);
        file_put_contents($this->filePath, json_encode($this->settings, JSON_PRETTY_PRINT));
    }
}
