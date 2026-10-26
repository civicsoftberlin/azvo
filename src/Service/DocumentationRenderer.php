<?php

// src/Service/DocumentationRenderer.php
namespace App\Service;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\ParameterBag;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

class DocumentationRenderer
{
    private TwigEnvironment $twig;
    private MarkdownConverter $converter;

    public function __construct(
        TwigEnvironment $twig,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    )
    {
        $this->twig = $twig;
        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AttributesExtension());
        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * @throws SyntaxError
     * @throws LoaderError
     * @throws CommonMarkException
     */
    public function render(string $relativePath, array $context = []): string
    {
        $markdownContent = file_get_contents($this->projectDir . "/" . ltrim($relativePath, '/'));
        $twigRenderedContent = $this->twig->createTemplate($markdownContent)->render($context);
        return $this->converter->convert($twigRenderedContent)->getContent();
    }
}
