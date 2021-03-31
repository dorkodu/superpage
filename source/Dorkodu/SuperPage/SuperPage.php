<?php

  /**
   * @author      Doruk Eray <doruk@dorkodu.com>
   * @copyright   Copyright (c), 2021 Dorkodu
   * @license     MIT public license
   */
  namespace Dorkodu\SuperPage;

  /**
   * A simple, callback based router.
   */
  class SuperPage
  {
    /**
     * @var array The route patterns and their handling functions
     */
    private $routes = array();

    /**
     * @var string Current base route, used for (sub)route mounting
     */
    public $root;

    /**
     * @var string The Request Method that needs to be handled
     */
    private $requestMethod;

    /**
     * @var callable The function to be executed when no route has been matched
     */
    private $notFoundCallback;

    /**
     * @var string The Server Base Path for Router Execution
     */
    private $serverBasePath;
    
    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods.
     *
     * @param string $pattern A route pattern such as /about/company
     * @param string $methods Allowed methods, | delimited
     * @param callable $callback The handling function to be executed
     */
    public function to(string $pattern, string $methods, $callback)
    {
      $pattern = $this->unifyUriPattern($pattern);

      foreach (explode('|', strtoupper($methods)) as $method) {
        $this->routes[$method][] = array(
            'pattern' => $pattern,
            'callback' => $callback,
        );
      }
    }

    /**
     * Redirects a route to another
     *
     * @param string $from
     * @param string $to
     * @param int $statusCode
     */
    public function redirect($from, $to, $method = 'GET', $statusCode = 301)
    {
      $fromPattern = $this->unifyUriPattern($from);
      $toPattern = $this->unifyUriPattern($to);

      $this->routes[$method][] = array(
          'pattern' => $fromPattern,
          'redirect' => $toPattern,
          'statusCode' => $statusCode
      );
    }

    /**
     * Unifies URI pattern
     *
     * @param string $pattern
     * @return string unified URI pattern
     */
    private function unifyUriPattern($pattern)
    {
      $pattern = $this->root . '/' . trim($pattern, '/');
      $pattern = $this->root ? rtrim($pattern, '/') : $pattern;
      return $pattern;
    }

    /**
     * Shorthand for a route accessed using any method.
     *
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function any($pattern, $fn)
    {
      $this->to($pattern, 'GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $fn);
    }

    /**
     * Shorthand for a route accessed using GET.
     *
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function get($pattern, $fn)
    {
      $this->to($pattern, 'GET', $fn);
    }

    /**
     * Shorthand for a route accessed using POST.
     *
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function post($pattern, $fn)
    {
      $this->to($pattern, 'POST', $fn);
    }

    /**
     * Shorthand for a route accessed using PATCH.
     *
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function patch($pattern, $fn)
    {
      $this->to($pattern, 'PATCH', $fn);
    }


    /**
     * Shorthand for a route accessed using DELETE.
     *
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function delete($pattern, $fn)
    {
      $this->to($pattern, 'DELETE', $fn);
    }

    /**
     * Shorthand for a route accessed using PUT.
     *
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function put($pattern, $fn)
    {
      $this->to($pattern, 'PUT', $fn);
    }

    /**
     * Shorthand for a route accessed using OPTIONS.
     *
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function options($pattern, $fn)
    {
      $this->to($pattern, 'OPTIONS', $fn);
    }


    /**
     * Execute the router.
     * Loop all defined routes, and execute the callback function if a match was found.
     *
     * @param callable $callback Function to be executed after a matching route was handled (= after router middleware)
     *
     * @return bool
     */
    public function run($callback = null)
    {
      # define which method we need to handle
      $this->requestMethod = $this->getRequestMethod();
      
      # handle all routes
      $numHandled = 0;
      if (isset($this->routes[$this->requestMethod])) {
        $numHandled = $this->handle($this->routes[$this->requestMethod], true);
      }

      # if no route was handled, trigger the 404 (if any)
      if ($numHandled === 0) {
        $this->notFound();
      } 
      
      # if a route was handled, perform the finish callback (if any)
      else {
        if ($callback && is_callable($callback))
          $callback();
      }

      # if it originally was a HEAD request, clean up after ourselves by emptying the output buffer
      if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
        ob_end_clean();
      }

      # return true if a route was handled, false otherwise
      return $numHandled !== 0;
    }

    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function.
     *
     * @param array $routes       Collection of route patterns and their handling functions
     * @param bool  $quitAfterRun Does the handle function need to quit after one route was matched?
     *
     * @return int The number of routes handled
     */
    private function handle($routes, $quitAfterRun = false)
    {
      # counter to keep track of the number of routes we've handled
      $numHandled = 0;

      # the current page URL
      $uri = $this->getPath();

      # loop all routes
      foreach ($routes as $route) {
        # replace all curly braces matches {} into word patterns
        $route['pattern'] = preg_replace('/\/{(.*?)}/', '/(.*?)', $route['pattern']);

        # we have a match!
        if (preg_match_all('~^' . $route['pattern'] . '$~', $uri, $matches, PREG_OFFSET_CAPTURE)) {
          # rework matches to only contain the matches, not the original string
          $matches = array_slice($matches, 1);

          # extract the matched URL parameters (and only THE parameters)
          $params = $this->extractMatchedURLParams($matches);

          if (isset($route['redirect'])) {
            # static redirect
            # TODO: if has any extracted params, pass them to the redirected callback. it's too hard though :(
            header('Location:'.$route['redirect'], true, $route['statusCode']);
          } else {
            # call the handling function with the URL parameters if the desired input is callable
            $this->invoke($route['callback'], $params);
          }

          ++$numHandled;

          # if we need to quit, then quit
          if ($quitAfterRun) {
            break;
          }
        }
      }
      # return the number of routes handled
      return $numHandled;
    }

    /**
     * Extracts the matched URL parameters (and only THE parameters)
     *
     * @param array $matches
     * @return void
     */
    private function extractMatchedURLParams(array $matches)
    {
      return array_map(function ($match, $index) use ($matches) {
        # we have a following parameter : 
        # take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
        if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
          if ($matches[$index + 1][0][1] > -1) {
            return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
          }
        } 
        # We have no following parameters: return the whole lot
        return isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
      }, $matches, array_keys($matches));
    }

    /**
     * Mounts a collection of callbacks onto a base route.
     *
     * @param string   $baseRoute The route sub pattern to mount the callbacks on
     * @param callable $fn        The callback method
     */
    public function mount($baseRoute, $fn)
    {
      # track current base route
      $curBaseRoute = $this->root;

      # build new base route string
      $this->root .= $baseRoute;

      # call the callable
      call_user_func($fn);

      # restore original base route
      $this->root = $curBaseRoute;
    }

    /**
     * Set a 404 fallback route callback to redirect in case others doesn't match
     *
     * @param Callable $callback
     * @return void
     */
    public function fallback($callback)
    {
      $this->notFoundCallback = $callback;
    }

    /**
     * Triggers 404 Not Found response
     */
    public function notFound()
    {
      if (isset($this->notFoundCallback) && !empty($this->notFoundCallback)) {
        $this->invoke($this->notFoundCallback);
      } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
      }
    }

    /**
     * Invokes a user-given callable
     *
     * @param $fn
     * @param array $params
     * @return void
     */
    private function invoke($fn, $params = array())
    {
      if (is_callable($fn)) {
        call_user_func_array($fn, $params);
      }
    }


    /**
     * Define the current relative URI.
     *
     * @return string
     */
    public function getPath()
    {
      # Get the current request URI and remove rewrite base path from it (= allows one to run the router in a sub folder)
      $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->getBasePath()));
      # Don't take query params into account on the URL
      if (strstr($uri, '?')) {
        $uri = substr($uri, 0, strpos($uri, '?'));
      }
      # Remove trailing slash + enforce a slash at the start
      return '/' . trim($uri, '/');
    }

    /**
     * Get all request headers.
     *
     * @return array The request headers
     */
    public function getRequestHeaders()
    {
      $headers = array();

      # Method getallheaders() not available or went wrong: manually extract 'm
      foreach ($_SERVER as $name => $value) {
        if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
          $headers[str_replace(array(' ', 'Http'), array('-', 'HTTP'), ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
      }

      return $headers;
    }

    /**
     * Get the request method used, taking overrides into account.
     *
     * @return string The Request method to handle
     */
    public function getRequestMethod()
    {
      # Take the method as found in $_SERVER
      $method = $_SERVER['REQUEST_METHOD'];

      # If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
      # @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
      if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
        ob_start();
        $method = 'GET';
      }

      # If it's a POST request, check for a method override header
      elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $headers = $this->getRequestHeaders();
        if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], array('PUT', 'DELETE', 'PATCH'))) {
            $method = $headers['X-HTTP-Method-Override'];
        }
      }

      return $method;
    }

    /**
     * Return server base Path, and define it if isn't defined.
     *
     * @return string
     */
    public function getBasePath()
    {
      // Check if server base path is defined, if not define it.
      if ($this->serverBasePath === null) {
        $this->serverBasePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
      }

      return $this->serverBasePath;
    }

    /**
     * Explicilty sets the server base path. To be used when your entry script path differs from your entry URLs.
     *
     * @param string
     */
    public function setBasePath($serverBasePath)
    {
      $this->serverBasePath = $serverBasePath;
    }

  }
