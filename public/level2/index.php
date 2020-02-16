<?php

use atk4\ui\App;
use atk4\ui\Form;
use atk4\ui\Layout\Centered;
use atk4\ui\Loader;
use atk4\ui\View;

require "../../vendor/autoload.php";

$file_path = '../hashes.txt';
$file_hashes = fopen($file_path, 'a+');
$app = new App([
    "title" => 'Level 2 - Add the hashed string to a text file',
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
    $loader->add(View::class)->set($loader->stickyGet('result'));
});

$form->addField('input_string');
$form->onSubmit(function (Form $form) use ($loader, $file_hashes) {

    $input_string = $form->model->get("input_string");
    $hash = password_hash($input_string, PASSWORD_DEFAULT);

    fputs($file_hashes, $hash . PHP_EOL);
    fclose($file_hashes);

    return $loader->jsLoad(['result' => 'hash added with success']);

});