<?php

use atk4\ui\App;
use atk4\ui\Form;
use atk4\ui\Layout\Centered;
use atk4\ui\Loader;
use atk4\ui\View;

require "../../vendor/autoload.php";

$app = new App([
    "title" => "Level 1 - Encode a string",
    "call_exit" => false
]);
$app->initLayout(Centered::class);

/** @var Form $form */
$form = $app->add(new Form());

/** @var Loader $loader */
$loader = $app->add([
    Loader::class,
    'loadEvent' => false
]);
$loader->set(function (Loader $loader) {
    $loader->add(View::class)->set(password_hash($loader->stickyGet('input_string'), PASSWORD_DEFAULT));
});

$form->addField('input_string');
$form->onSubmit(function (Form $form) use ($loader) {
    return $loader->jsLoad(['input_string' => $form->model->get("input_string")]);
});