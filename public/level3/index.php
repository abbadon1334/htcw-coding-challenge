<?php

use atk4\ui\App;
use atk4\ui\Form;
use atk4\ui\Layout\Centered;
use atk4\ui\Loader;
use atk4\ui\View;

require "../../vendor/autoload.php";

$file_path = '../hashes.txt';
$file_hashes = file_get_contents($file_path);
$hashes = explode(PHP_EOL,$file_hashes);

$data = [];
$model_hash = new \atk4\data\Model(new \atk4\data\Persistence\Array_($data));
$model_hash->addField('hash');
foreach($hashes as $hash) {

    $hash = trim($hash);

    if (empty($hash)) {
        continue;
    }

    $model_hash->insert(['hash' => $hash]);
}

$app = new App([
    "title" => 'Level 3 - Validate the clear text string',
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
$loader->set(function(Loader $loader) {
    $loader->add(View::class)->set($loader->stickyGet('result'));
});

$form->addField('input_string');
$form->addField('add_to_file', [\atk4\ui\FormField\CheckBox::class]);
$form->onSubmit(function(Form $form) use ($loader,$model_hash,$file_path) {

    $input_string = $form->model->get("input_string");

    foreach($model_hash->getIterator() as $m) {
        $hash = $m->get('hash');
        if(password_verify($input_string,$hash)) {
            return $loader->jsLoad(['result' => 'hash is valid']);
        }
    }

    $result = 'Hash is not valid.';

    $add_to_file = (int) $form->model->get("add_to_file");
    if($add_to_file === 1) {

        $hash = password_hash($input_string, PASSWORD_DEFAULT);
        $model_hash->insert(['hash' => $hash]);
        $file_content = [];
        foreach($model_hash->getIterator() as $m) {
            $file_content[] = $m->get('hash');
        }

        file_put_contents($file_path, implode(PHP_EOL, $file_content));

        $result.= PHP_EOL . "Hash was add to file";
    }

    return $loader->jsLoad(['result' => $result]);
});