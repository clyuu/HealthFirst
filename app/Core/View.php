<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        echo self::renderContent($view, $data, $layout);
    }

    public static function renderContent(string $view, array $data = [], string $layout = 'main'): string
    {
        $viewFile = AppContext::basePath('app/Views/' . $view . '.php');
        if (!is_file($viewFile)) {
            return sprintf('View not found: %s', e($view));
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === '') {
            return $content;
        }

        $layoutFile = AppContext::basePath('app/Views/layouts/' . $layout . '.php');
        if (!is_file($layoutFile)) {
            return $content;
        }

        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }
}

