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
    private $routes = array();

    public $root;
    private $requestMethod;

    private $notFoundCallback;
    
    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods.
     *
     * @param string $pattern A route pattern such as /about/company
     * @param string $methods Allowed methods, | delimited
     * @param callable $callback The handling function to be executed
     */
    public function to(string $pattern, string $methods, $callback)
    {
      $pattern = $this->root . '/' . trim($pattern, '/');
      $pattern = $this->root ? rtrim($pattern, '/') : $pattern;

      foreach (explode('|', $methods) as $method) {
        $this->routes[$method][] = array(
            'pattern' => $pattern,
            'callback' => $callback,
        );
      }
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
      $this->post($pattern, 'POST', $fn);
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


  }
