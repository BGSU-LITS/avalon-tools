<?php
/**
 * Application Routes
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use Slim\Container;
use Slim\Flash\Messages;
use App\Session;
use Slim\Views\Twig;

// Index action.
$container[IndexAction::class] = function (Container $container) {
    return new IndexAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class]
    );
};

$app->get('/', IndexAction::class);
