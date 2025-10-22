<?php
/**
 * LightBlog CMS - Router
 * Lightweight routing system for handling URLs
 */

class Router {
    private $routes = [];
    private $notFoundHandler;

    /**
     * Add a route pattern and handler
     * @param string $pattern Regex pattern for URL matching
     * @param callable|string $handler Function or controller method to call
     */
    public function add($pattern, $handler) {
        $this->routes[$pattern] = $handler;
    }

    /**
     * Set custom 404 handler
     * @param callable $handler
     */
    public function setNotFoundHandler($handler) {
        $this->notFoundHandler = $handler;
    }

    /**
     * Dispatch the request to appropriate handler
     * @param string $uri The request URI
     */
    public function dispatch($uri) {
        // Remove query string
        $uri = strtok($uri, '?');

        // Remove trailing slash except for root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $pattern => $handler) {
            if (preg_match($pattern, $uri, $matches)) {
                // Remove the full match, keep only captured groups
                array_shift($matches);

                // Call the handler
                if (is_callable($handler)) {
                    return call_user_func_array($handler, $matches);
                } elseif (is_string($handler) && strpos($handler, '::') !== false) {
                    // Handle "ControllerName::methodName" format
                    list($class, $method) = explode('::', $handler);
                    if (class_exists($class) && method_exists($class, $method)) {
                        return call_user_func_array([$class, $method], $matches);
                    }
                }

                return;
            }
        }

        // No route matched - handle 404
        $this->handleNotFound();
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound() {
        http_response_code(404);

        if ($this->notFoundHandler && is_callable($this->notFoundHandler)) {
            call_user_func($this->notFoundHandler);
        } else {
            // Default 404 page
            if (file_exists(SITE_PATH . '/themes/' . (defined('CURRENT_THEME') ? CURRENT_THEME : 'default') . '/404.php')) {
                include SITE_PATH . '/themes/' . (defined('CURRENT_THEME') ? CURRENT_THEME : 'default') . '/404.php';
            } else {
                echo '<h1>404 Not Found</h1>';
                echo '<p>The page you are looking for does not exist.</p>';
            }
        }
    }

    /**
     * Get current URI
     * @return string
     */
    public static function getCurrentUri() {
        $uri = $_SERVER['REQUEST_URI'];

        // Remove base path if running in subdirectory
        if (defined('BASE_PATH') && BASE_PATH !== '/') {
            $basePath = rtrim(BASE_PATH, '/');
            if (strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
            }
        }

        // Ensure URI starts with /
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /**
     * Redirect to a URL
     * @param string $url
     * @param int $code HTTP status code (301 or 302)
     */
    public static function redirect($url, $code = 302) {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }
}
