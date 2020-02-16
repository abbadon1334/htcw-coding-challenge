<?php

require "../../vendor/autoload.php";

use atk4\ui\App;
use atk4\ui\Form;
use atk4\ui\Layout\Centered;
use atk4\ui\Loader;
use atk4\ui\View;

$app = new App([
    "title" => "Level 1 - Encode a string",
    "call_exit" => false
]);
$app->initLayout(Centered::class);

$app->add(Form::class)
    ->addField('input_string')
    ->onSubmit(function (Form $form) use ($loader) {
        return $loader->jsLoad(['input_string' => $form->model->get("input_string")]);
    });

/** @var Loader $loader */
$loader = $app->add([
    Loader::class,
    'loadEvent' => false
])->set(function (Loader $loader) {
    $loader->add(View::class)->set(password_hash($loader->stickyGet('input_string'), PASSWORD_DEFAULT));
});