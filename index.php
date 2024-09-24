<?php

declare(strict_types=1);

error_reporting(E_ALL | E_STRICT);

try {


    class Application
    {
        public function __construct(
            public string           $pageTitle,
            public string           $view,
            public readonly Request $request,
            public readonly Router  $router = new Router(),
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
            public array $cookies,
            public array $server
        )
        {
        }

        public static function fromSuperGlobals(): self
        {
            return new self(
                $_GET,
                $_POST,
                $_COOKIE,
                $_SERVER
            );
        }
    }

    readonly class Router
    {
        public function getRouteFromName(string $name): Route
        {
            $classes = array_filter(
                get_declared_classes(),
                fn (string $class) => is_subclass_of($class, AbstractController::class)
            );

            foreach ($classes as $class) {
                $reflectedClass = new ReflectionClass($class);
                $reflectedAttributes = $reflectedClass->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);
                foreach($reflectedAttributes as $reflectedAttribute) {
                    $route = $reflectedAttribute->newInstance();
                    if (!$route instanceof Route) {
                        throw new Exception(sprintf('Expected `%s`, got `%s`', Route::class, get_class($reflectedAttribute)));
                    }
                    if ($route->name === $name) {
                        return $route;
                    }
                }
            }
            throw new Exception("No route found for name `{$name}`", code: 404);
        }
    }

    /**
     * ATTRIBUTES
     */

    #[Attribute(Attribute::TARGET_CLASS)]
    class Route {
        public function __construct(
            public string $path,
            public string $name
        ) {}
    }

    class AbstractController {}

    #[Route(path: '/', name: 'index')]
    class HomeController extends AbstractController
    {
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


