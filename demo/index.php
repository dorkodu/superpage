<?php
  require_once "loot/loom-weaver.php";
  error_reporting(0);

  use Dorkodu\SuperPage\SuperPage;

  # controllers 

  $FrontpageController = function() {
    echo "Hi there!<br>This is a demo for Superpage router.<br>Fast, isn't it?";
    echo "<br><br>Routes : <br>/<br>/about<br>/greet/{name}<br>/what (redirects to /about)";
  };

  $ErrorPageController = function() {
    http_response_code(404);
    echo "404 Not Found";
  };

  $GreetPageController = function($name = null) {
    echo "Hello " . $name . " :)";
  };

  $AboutPageController = function() {
    echo nl2br("Superpage is a fast, simple and lightweight router for PHP apps.".PHP_EOL."It's developed by Dorkodu (dorkodu.com)");
  };
  
  # ROUTES

  $superpage = new SuperPage();

  # frontpage and aliases
  $superpage->get("/", $FrontpageController);
  $superpage->get("/index.php", $FrontpageController);
  $superpage->get("/index", $FrontpageController);
  
  # other pages
  $superpage->get("/about", $AboutPageController);
  $superpage->get("/greet/{name}", $GreetPageController);

  # sample about
  $superpage->redirect("/what", "/about");

  # not found
  $superpage->fallback($ErrorPageController);
  $superpage->any('/oops', $ErrorPageController);

  # run it!
  $superpage->run();