<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Lib\View;

abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
