<?php

declare(strict_types=1);

error_reporting(E_ALL | E_STRICT);

try {
    class Application
    {
        public function __construct(
            public string           $pageTitle,
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

        public function getView(): string
        {
            return $this->router->getRouteFromRequest($this->request)->getView();
        }

        public function render(): void
        {
            $controllers = array_filter(
                get_declared_classes(),
                fn(string $class) => is_subclass_of($class, AbstractController::class)
            );

            $nav = '';

            foreach ($controllers as $controllerClass) {
                /** @var AbstractController $controller */
                $controller = new $controllerClass();
                $nav .= sprintf(
                    '<a href="%s">%s</a>',
                    $controller->getPath(),
                    ucfirst($controller->getName())
                );
            }

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
            {$nav}
        </div>
    </header>
    <div class="body">{$this->getView()}</div>
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

        public function getUri(): string
        {
            return $this->server['REQUEST_URI'] ?? '/';
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
        private function getRouteFromFn(callable $fn): AbstractController
        {
            $classes = array_filter(
                get_declared_classes(),
                fn(string $class) => is_subclass_of($class, AbstractController::class)
            );

            foreach ($classes as $class) {
                $reflectedClass = new ReflectionClass($class);
                $reflectedAttributes = $reflectedClass->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);
                foreach ($reflectedAttributes as $reflectedAttribute) {
                    $route = $reflectedAttribute->newInstance();
                    if (!$route instanceof Route) {
                        throw new Exception(sprintf('Expected `%s`, got `%s`', Route::class, get_class($reflectedAttribute)));
                    }
                    if ($fn($route)) {
                        return $reflectedClass->newInstance();
                    }
                }
            }
            throw new Exception("No route found ", code: 404);
        }

        public function getRouteFromRequest(Request $request): AbstractController
        {
            return $this->getRouteFromFn(fn(Route $route) => $route->path === $request->getUri());
        }

        public function getRouteFromName(string $name): AbstractController
        {
            return $this->getRouteFromFn(fn(Route $route) => $route->name === $name);
        }
    }

    /**
     * ATTRIBUTES
     */
    #[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION)]
    class Route
    {
        public function __construct(
            public string $path,
            public string $name
        )
        {
        }
    }

    /**
     * CONTROLLERS
     */
    abstract class AbstractController
    {
        abstract public function getView(): string;

        public function getStyles(): string
        {
            return '';
        }
        public function getRoute(): Route
        {
            $reflection = new ReflectionClass($this);
            foreach ($reflection->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF) as $reflectedAttribute) {
                return $reflectedAttribute->newInstance();
            }

            throw new ErrorException('No route found for ' . static::class);
        }

        public function getPath(): string
        {
            return $this->getRoute()->path;
        }

        public function getName(): string
        {
            return $this->getRoute()->name;
        }
    }

    #[Route(path: '/', name: 'index')]
    class HomeController extends AbstractController
    {
        public function getView(): string
        {
            $combos = [];
            for ($i = 0; $i < 16; $i++) {
                for ($ii = 0; $ii < $i; $ii++) {
                    for ($iii = 0; $iii < $ii; $iii++) {
                        $combos[] = array_map(
                            fn (int $n) => dechex($n * 16),
                            [$i, $ii, $iii]
                        );
                    }
                }
            }
            $elems = '';
            foreach ($combos as $comboY) {
                foreach ($combos as $comboX) {
                    [$x1, $x2, $x3] = $comboX;
                    [$y1, $y2, $y3] = $comboY;
                    $colour = '#'.$x1.$y1.$x2.$y2.$x3.$y3;
                    $elems .= "<span style='color: {$colour}; background-color: {$colour}'>X</span>";
                }
                $elems .= PHP_EOL;
            }

            return $elems;
        }


    }

    #[Route(path: '/test', name: 'test')]
    class TestController extends AbstractController
    {
        public function getView(): string
        {
            return 'Test Controller';
        }
    }

    $app = new Application(
        'Test',
        Request::fromSuperGlobals(),
    );

    $app->render();

} catch (Throwable $e) {
    echo "<h1>{$e->getMessage()}</h1>";
    echo '<pre>';
    print_r(get_defined_vars());
    echo '</pre>';
    throw $e;
}


