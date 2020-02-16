<?php

use atk4\data\Model;
use atk4\schema\Migration;
use atk4\ui\App;
use atk4\ui\Form;
use atk4\ui\FormField\DropDown;
use atk4\ui\FormField\Line;
use atk4\ui\Header;
use atk4\ui\Layout\Centered;
use atk4\ui\Loader;
use atk4\ui\View;

require "../../vendor/autoload.php";

class ModelHash extends Model
{

    public $table = 'tbl_hash';

    public function init()
    {
        parent::init();

        $this->addField('hash_string', [
            'type' => 'string',
            'system' => true // will be not visible in UI
        ]);
    }

    public function setHashFromInputString(string $input): self
    {
        $this->set('hash_string', password_hash($input, PASSWORD_DEFAULT));

        return $this;
    }

    public function isValidHash(string $clear_value): bool
    {
        return password_verify($clear_value, $this->get('hash_string'));
    }

    public function isAlreadyHashed(string $clear_value): bool
    {
        $model = $this->newInstance();
        foreach ($model->getIterator() as $m) {
            if ($m->isValidHash($clear_value)) {
                return true;
            }
        }
        return false;
    }
}

$app = new App([
    'title' => 'Full Example',
    'call_exit' => false
]);
$app->initLayout(Centered::class)->dbConnect('sqlite:db.sq3');

(Migration::getMigration(new ModelHash($app->db)))->migrate();

$model = new ModelHash($app->db);
$count = (int)$model->action('count')->getOne();

/**
 * Add form to app
 * @var Form $form
 */
$form = $app->add(Form::class);
$form->buttonSave->set('Execute Action');

$app->add([Header::class, 'Result message', 'size' => 4]);
/**
 * Add Loader and setup
 * @var Loader $loader
 */
$loader = $app->add([
    Loader::class,
    'loadEvent' => false
]);
$loader->set(function (Loader $loader) {
    $loader->add(View::class)->set($loader->stickyGet('action_response') ?? '');
});

// Setup form
$form->setModel($model);
$form->addField('input_value', [Line::class], [
    'never_persist' => true,
    'required' => true
]);
$form->addField('action_request', [DropDown::class], [
    'type' => 'enum',
    'values' => [
        'add' => 'Convert input string to hash and save to persistence',
        'validate' => 'Validate input string vs stored hashes'
    ],
    'never_persist' => true,
    'required' => true,
]);
$form->model->set('action_request', 'add');

$form->onSubmit(function ($f) use ($loader) {

    $input = $f->model->get('input_value');
    $response = 'Input : ' . $input;

    switch ($f->model->get('action_request')) {
        case 'add':

            if ($f->model->isAlreadyHashed($input)) {
                return $loader->jsLoad([
                    'action_response' => $response . ' was already hashed.'
                ]);
            }

            $f->model->setHashFromInputString($input)->save();

            $response .= ' => ';
            $response .= 'Hash : ' . $f->model->get('hash_string');

            return $loader->jsLoad([
                'action_response' => $response
            ]);

            break;
        case 'validate':

            $model = new ModelHash($f->app->db);

            if ($model->isAlreadyHashed($input)) {
                return $loader->jsLoad([
                    'action_response' => $response . ' is valid!'
                ]);
            }

            return $loader->jsLoad([
                'action_response' => $response . ' is not valid!'
            ]);
            break;
    }

    throw new \atk4\ui\Exception('Something went wrong');
});