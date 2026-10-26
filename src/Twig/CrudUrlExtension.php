<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CrudUrlExtension extends AbstractExtension
{
    private UrlGeneratorInterface $router;
    private string $adminPrefix;

    public function __construct(
        UrlGeneratorInterface $router,
        string $adminPrefix = '/admin'
    ) {
        $this->router = $router;
        $this->adminPrefix = $adminPrefix;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('crud_url', [$this, 'generateCrudUrl']),
        ];
    }

    /**
     * Generiert CRUD-URLs mit optionalen Query-Parametern
     *
     * usage: {{ crud_url('overtime_entry') }}
     * usage: {{ crud_url('teacher_deputat/batch-export', {validFrom: '2026-08-01'}) }}
     * usage: {{ crud_url('overtime_entry', {'filters[month]': 10, 'filters[year]': 2026}) }}
     */
    public function generateCrudUrl(string $resource, array $params = []): string
    {
        // Prüfe ob es eine Custom-Action ist (mit /)
        if (str_contains($resource, '/')) {
            [$entity, $action] = explode('/', $resource, 2);
        } else {
            $entity = $resource;
            $action = 'index'; // Standard-Action
        }

        // Konvertiere Entity-Name zu Route-Name
        // Aus "teacher_deputat" wird "admin_teacher_deputat_index"
        // Aus "teacher_deputat/batch-export" wird "admin_teacher_deputat_batch-export"
        $routeName = sprintf(
            'admin_%s_%s',
            str_replace('-', '_', $entity),
            str_replace('-', '_', $action)
        );

        // Versuche die URL zu generieren
        try {
            return $this->router->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (\Exception $e) {
            // Fallback: Manuelle URL-Konstruktion falls Route nicht existiert
            return $this->buildManualUrl($entity, $action, $params);
        }
    }

    private function buildManualUrl(string $entity, string $action, array $params): string
    {
        $path = sprintf('%s/%s', $this->adminPrefix, str_replace('_', '-', $entity));

        if ($action !== 'index') {
            $path .= '/' . str_replace('_', '-', $action);
        }

        if (!empty($params)) {
            $path .= '?' . http_build_query($params);
        }

        return $path;
    }
}
