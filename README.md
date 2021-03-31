<img src="assets/superpage-dark.png" alt="SuperPage Logo" style="zoom: 67%; float: left;" />

# SuperPage

SuperPage is a simple, fast and lightweight, which means an awesome router for PHP apps!

## Features

- Supports `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `PATCH` and `HEAD` request methods
- [Static Route Patterns](#route-patterns)
- Dynamic Route Patterns: 
  - [Dynamic PCRE-based Route Patterns](#dynamic-pcre-based-route-patterns) 
  - [Dynamic Placeholder-based Route Patterns](#dynamic-placeholder-based-route-patterns)
- [Optional Route Subpatterns](#optional-route-subpatterns)
- [Supports `X-HTTP-Method-Override` header](#overriding-the-request-method)
- [Subrouting / Mounting Routes](#subrouting--mounting-routes)
- [Custom 404 handling](#custom-404)
- [After Router Middleware / After App Middleware (Finish Callback)](#after-router-middleware--run-callback)
- [Works fine in subfolders](#subfolder-support)

## Prerequisites/Requirements

- PHP 7.2+ or greater
- URL Rewriting

## Installation

You can require `SuperPage.php` class, or use an autoloader like Loom or Composer.

## Demo

A demo is included in the `demo` sub-folder. Serve it using your favorite web server, or using PHP 5.4+'s built-in server by executing `php -S localhost:8080` on the shell. A `.htaccess` for use with Apache is included.

## Usage

- Create an instance of `\Dorkodu\SuperPage\SuperPage`.
- Define your routes.
- Then run SuperPage!

```php
/*
 * Require your autoloader script, 
 * We use Loom for that :) You can use Composer too.
 */
require __DIR__ . '/loot/loom-weaver.php';

# Create Router instance
$superpage = new \Dorkodu\SuperPage\SuperPage();

# Define routes
$superpage->to('/', 'GET', function() { 
	echo "Home";
});

$superpage->get('/about', function() { 
	echo "About";
});

# Run it!
$superpage->run();
```


### Routing

Hook __routes__ (a combination of one or more HTTP methods and a pattern) using <br>`$superpage->to(pattern, method(s), callback)` :

```php
$superpage->to('...pattern', 'GET|POST|...[METHOD]', function() { ··· });
```

SuperPage supports `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD` _(see [note](#a-note-on-making-head-requests))_, and `OPTIONS` HTTP request methods. Pass in a single request method, or multiple request methods separated by `|`.

When a route matches against the current URL (e.g. `$_SERVER['REQUEST_URI']`), the attached __route handling function__ will be executed. The route handling function must be a [callable](http://php.net/manual/en/language.types.callable.php). Only the first route matched will be handled. When no matching route is found, a 404 handler will be executed.

### Routing Shorthands

Shorthands for single request methods are provided :

```php
$superpage->get('pattern', function() { ··· });
$superpage->post('pattern', function() { ··· });
$superpage->put('pattern', function() { ··· });
$superpage->delete('pattern', function() { ··· });
$superpage->options('pattern', function() { ··· });
$superpage->patch('pattern', function() { ··· });
```

You can use this shorthand for a route that can be accessed using any method :

```php
$superpage->any('pattern', function() { ··· });
```

### Redirecting

You can redirect a pattern to another absolute URI. This is what we choose for simplicity, may add *parameters* or *pattern-to-pattern* matches in the future. If you so desire, let us know by opening an issue. Maybe we can collaborate!

```php
/*
 * 3rd parameter is request method, defaults to 'GET'
 * 4th parameter is status code, defaults to 301
 */ 
$superpage->redirect('pattern', '/redirect-to', $method, $statusCode);
```

### 

> **Note :** Routes must be defined before `$superpage->run();` is being called.

> **Note :** There is no shorthand like `head()` as SuperPage will internally re-route such requests to their equivalent `GET` request, in order to comply with RFC2616 _(see [note](#a-note-on-making-head-requests))_.

### Route Patterns

Route Patterns can be static or dynamic:

- __Static Route Patterns__ 

  They contain no dynamic parts and must match exactly against the `path` part of the current URL.

- __Dynamic Route Patterns__ 

  They contain dynamic parts that can vary per request. The varying parts are named __subpatterns__ and are defined using either Perl-compatible regular expressions (PCRE) or by using __placeholders__

#### Static Route Patterns

A static route pattern is a regular string representing a URI. It will be compared directly against the `path` part of the current URL.

Example : `/about`

Usage Example :

```php
// This route handling function will only be executed when visiting http(s)://www.example.org/about
$superpage->get('/about', function() {
    echo 'About Page Contents';
});
```

#### Dynamic PCRE-based Route Patterns

This type of Route Patterns contain dynamic parts which can vary per request. The varying parts are named __subpatterns__ and are defined using regular expressions.

Examples:

- `/user/(\d+)`
- `/blog/(\w+)`

Commonly used PCRE-based subpatterns within Dynamic Route Patterns are:

- `\d+` = One or more digits (0-9)
- `\w+` = One or more word characters (a-z 0-9 _)
- `[a-z0-9_-]+` = One or more word characters (a-z 0-9 _) and the dash (-)
- `.*` = Any character (including `/`), zero or more
- `[^/]+` = Any character but `/`, one or more

Note: The [PHP PCRE Cheat Sheet](https://courses.cs.washington.edu/courses/cse154/15sp/cheat-sheets/php-regex-cheat-sheet.pdf) might come in handy.

The __subpatterns__ defined in Dynamic PCRE-based Route Patterns are converted to parameters which are passed into the route handling function. Prerequisite is that these subpatterns need to be defined as __parenthesized subpatterns__, which means that they should be wrapped between parens:

```php
// Bad
$superpage->get('/hello/\w+', function($name) {
    echo 'Hello ' . htmlentities($name);
});

// Good
$superpage->get('/hello/(\w+)', function($name) {
    echo 'Hello ' . htmlentities($name);
});
```

Note: The leading `/` at the very beginning of a route pattern is not mandatory, but recommended.

When multiple subpatterns are defined, the resulting __route handling parameters__ are passed into the route handling function in the order they are defined in:

```php
$superpage->get('/movies/(\d+)/photos/(\d+)', function($movieId, $photoId) {
    echo 'Movie #' . $movieId . ', photo #' . $photoId;
});
```

#### Dynamic Placeholder-based Route Patterns

This type of Route Patterns are the same as __Dynamic PCRE-based Route Patterns__, but with one difference: they don't use regex to do the pattern matching but they use the more easy __placeholders__ instead. Placeholders are strings surrounded by curly braces, e.g. `{name}`. You don't need to add paren's around placeholders.

**Examples :**

- `/movies/{id}`
- `/profile/{username}`

Placeholders are easier to use than PRCEs, but offer you less control as they internally get translated to a PRCE that matches any character (`.*`).

```php
$superpage->get('/user/{userId}/post/{postId}', function($userId, $postId) {
    echo 'User #' . $userId . ', post #' . $postId;
});
```

Note: the name of the placeholder does NOT NEED TO MATCH with the name of the parameter that is passed into the route handling function :

```php
$superpage->get('/movies/{foo}/photos/{bar}', function($movieId, $photoId) {
    echo 'Movie #' . $movieId . ', photo #' . $photoId;
});
```


### Optional Route Subpatterns

Route subpatterns can be made optional by making the subpatterns optional by adding a `?` after them. Think of blog URLs in the form of `/blog(/year)(/month)(/day)(/slug)`:

```php
$superpage->get(
    '/blog(/\d+)?(/\d+)?(/\d+)?(/[a-z0-9_-]+)?',
    function($year = null, $month = null, $day = null, $slug = null) {
        if (!$year) { echo 'Blog overview'; return; }
        if (!$month) { echo 'Blog year overview'; return; }
        if (!$day) { echo 'Blog month overview'; return; }
        if (!$slug) { echo 'Blog day overview'; return; }
        echo 'Blogpost ' . htmlentities($slug) . ' detail';
    }
);
```

The code snippet above responds to the URLs `/blog`, `/blog/year`, `/blog/year/month`, `/blog/year/month/day`, and `/blog/year/month/day/slug`.

Note: With optional parameters it is important that the leading `/` of the subpatterns is put inside the subpattern itself. Don't forget to set default values for the optional parameters.

The code snipped above unfortunately also responds to URLs like `/blog/foo` and states that the overview needs to be shown - which is incorrect. Optional subpatterns can be made successive by extending the parenthesized subpatterns so that they contain the other optional subpatterns: The pattern should resemble `/blog(/year(/month(/day(/slug))))` instead of the previous `/blog(/year)(/month)(/day)(/slug)`:

```php
$superpage->get('/blog(/\d+(/\d+(/\d+(/[a-z0-9_-]+)?)?)?)?', function($year = null, $month = null, $day = null, $slug = null) {
    # ...
});
```

Note: It is highly recommended to __always__ define successive optional parameters.

To make things complete use [quantifiers](http://www.php.net/manual/en/regexp.reference.repetition.php) to require the correct amount of numbers in the URL:

```php
$superpage->get('/blog(/\d{4}(/\d{2}(/\d{2}(/[a-z0-9_-]+)?)?)?)?', function($year = null, $month = null, $day = null, $slug = null) {
    # ...
});
```


### Sub-routing / Mounting Routes

Use `$superpage->mount($baseroute, $callback)` to mount a collection of routes onto a sub-route pattern. The sub-route pattern is prefixed onto all following routes defined in the scope. 

e.g. Mounting a callback `$callback` onto `/people` will prefix `/people` onto all following routes.

```php
$superpage->mount('/people', function() use ($superpage) {
    # will result in '/people/'
    $superpage->get('/', function() {
        echo 'people overview';
    });
  
    # will result in '/people/id'
    $superpage->get('/(\d+)', function($id) {
        echo 'person id : ' . htmlentities($id);
    });

});
```

Nesting of sub-routes is possible, just define a second `$superpage->mount()` in the callable that's already contained within a preceding `$superpage->mount()`.

### Custom "404 Not Found"

The default 404 handler responses with a 404 status code and exits. 

You can override this default 404 handler by using `$superpage->fallback(callable);`

```php
$superpage->fallback(function() {
    header('HTTP/1.1 404 Not Found');
    # ... do something special here
});
```

The 404 handler will be executed when no route pattern was matched to the current URL.

💡 You can also manually trigger the 404 handler by calling `$superpage->notFound()`

```php
$superpage->get('/([a-z0-9-]+)', function($id) use ($superpage) {
    if (!Posts::exists($id)) {
      $superpage->notFound();
      return;
    }
    # …
});
```


### After Router Middleware / Run Callback

Run one (1) middleware function, name the __After Router Middleware__ _(in other projects sometimes referred to as after app middlewares)_ after the routing was processed. Just pass it along the `$superpage->run()` function. The run callback is route independent.

```php
$superpage->run(function() { ... });
```

**Note :** If the route handling function has `exit()`ed the run callback won't be run.


### Overriding the request method

Use `X-HTTP-Method-Override` to override the HTTP Request Method. Only works when the original Request Method is `POST`. Allowed values for `X-HTTP-Method-Override` are `PUT`, `DELETE`, or `PATCH`.


### Subfolder support

Out-of-the box **SuperPage** will run in any (sub)folder you place it into … no adjustments to your code are needed. You can freely move your _entry script_ `index.php` around, and the router will automatically adapt itself to work relatively from the current folder's path by mounting all routes onto that __basePath__.

Say you have a server hosting the domain `www.example.org` using `public_html/` as its document root, with this little _entry script_ `index.php`:

```php
$superpage->get('/', function() { echo 'Index'; });
$superpage->get('/hello', function() { echo 'Hello!'; });
```

- If your were to place this file _(along with its accompanying `.htaccess` file or the like)_ at the document root level (e.g. `public_html/index.php`), **SuperPage** will mount all routes onto the domain root (e.g. `/`) and thus respond to `https://www.example.org/` and `https://www.example.org/hello`.

- If you were to move this file _(along with its accompanying `.htaccess` file or the like)_ into a subfolder (e.g. `public_html/demo/index.php`), __SuperPage__ will mount all routes onto the current path (e.g. `/demo`) and thus repsond to `https://www.example.org/demo` and `https://www.example.org/demo/hello`. There's **no** need for `$superpage->mount(…)` in this case.

#### Disabling subfolder support

In case you **don't** want SuperPage to automatically adapt itself to the folder its being placed in, it's possible to manually override the _basePath_ by calling `setBasePath()`. This is necessary in the _(uncommon)_ situation where your _entry script_ and your _entry URLs_ are not tightly coupled _(e.g. when the entry script is placed into a subfolder that does not need be part of the URLs it responds to)_.

```php
// Override auto base path detection
$superpage->setBasePath('/');

$superpage->get('/', function() { echo 'Index'; });
$superpage->get('/hello', function() { echo 'Hello!'; });

$superpage->run();
```

If you were to place this file into a subfolder (e.g. `public_html/some/sub/folder/index.php`), it will still mount the routes onto the domain root (e.g. `/`) and thus respond to `https://www.example.org/` and `https://www.example.org/hello` _(given that your `.htaccess` file – placed at the document root level – rewrites requests to it)_

## Integration with other libraries

Integrate other libraries with **SuperPage** by making good use of the `use` keyword to pass dependencies into the handling functions.

```php
$view = new \Foo\Bar\View();

$superpage->get('/', function() use ($view) {
    $view->load('home.view');
    $view->setdata(array(
        'name' => 'Berk Cambaz'
    ));
});

$superpage->run(function() use ($view) {
    $view->display();
});
```

Given this structure it is still possible to manipulate the output from within the After Router Middleware


## A note on working with PUT

There's not a superglobal for PUT as `$_PUT` in PHP. <br>You must fake it :

```php
$superpage->put('/movies/(\d+)', function($id) {
    # Fake $_PUT
    $_PUT  = array();
    parse_str(file_get_contents('php://input'), $_PUT);
    # ...
});
```


## A note on making HEAD requests

When making `HEAD` requests all output will be buffered to prevent any content trickling into the response body, as defined in [RFC2616 (Hypertext Transfer Protocol -- HTTP/1.1)](http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4):

> The HEAD method is identical to GET except that the server MUST NOT return a message-body in the response. The meta data contained in the HTTP headers in response to a HEAD request should be identical to the information sent in response to a GET request. This method can be used for obtaining meta data about the entity implied by the request without transferring the entity-body itself. This method is often used for testing hypertext links for validity, accessibility, and recent modification.

To achieve this, **SuperPage** will internally re-route `HEAD` requests to their equivalent `GET` request and automatically suppress all output.


## Tests

SuperPage ships with unit tests using [Seekr](https://github.com/dorkodu/seekr/).

- To test the SuperPage library, run  `SuperPageTest.php` from PHP CLI. 

  Like `php test/SuperPageTest.php`, if you are in the root project folder.


## Acknowledgements

**SuperPage** is heavily inspired by `bramus/router`. I've stolen some useful docs content from there.

Even it works well, we wanted a simpler approach.

## License

SuperPage is released under the MIT public license. See the [LICENSE](LICENSE) for details.