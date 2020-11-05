<?php

use Slim\App;

return function (App $app) {
    $app->get('/stories/{url-unique-id}', \App\Action\ScrapeAction::class . ':get')->setName('get');
    $app->post('/stories', \App\Action\ScrapeAction::class . ':post')->setName('post');
};
