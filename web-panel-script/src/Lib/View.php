<?php
declare(strict_types=1);

namespace App\Lib;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }
}
