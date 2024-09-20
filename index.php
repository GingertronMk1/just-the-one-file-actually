<?php

declare(strict_types=1);

error_reporting(E_ALL | E_STRICT);

try {


    class Application
    {
        public function __construct(
            public string  $pageTitle,
            public string  $view,
            public Request $request,
        )
        {
        }

        public function getBaseStyles(): string
        {
            return '';
        }

        public function getStyle(): string
        {
            return '';
        }

        public function render(): void
        {
            ob_start();
            echo <<<HTML

    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <title>{$this->pageTitle}</title>
        <meta name="author" content="">
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>

    <body>
    <header class="header">
        <h1>{$this->pageTitle}</h1>
        <div class="header__links">
            <a href="{$this->router->getRouteFromName(
                'index'
            )->path}">Index</a>
            <a href="{$this->router->getRouteFromName(
                'jerseys'
            )->path}">Jerseys</a>
        </div>
    </header>
    <div class="body">{$this->view}</div>
    <footer>
    </footer>
    </body>
    <script>
    </script>

    <!-- Base styles -->
    {$this->getBaseStyles()}

    <!-- App styling -->
    {$this->getStyle()}

    </html>
HTML;
            ob_end_flush();

        }
    }

    readonly class Request
    {
        private function __construct(
            public array $get,
            public array $post,
            public array $cookies
        )
        {
        }

        public static function fromSuperGlobals(): self
        {
            return new self(
                $_GET,
                $_POST,
                $_COOKIE,
            );
        }
    }

    $app = new Application(
        'Farts',
        '',
        Request::fromSuperGlobals(),
    );

    $app->render();

} catch (\Throwable $e) {
    echo "<h1>{$e->getMessage()}</h1>";
    echo '<pre>';
    print_r(get_defined_vars());
    echo '</pre>';
    throw $e;
}


