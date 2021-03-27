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
  }
