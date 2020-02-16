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

/** Persistence model to store data in persistence */
class ModelHash extends Model
{
    public $table = 'tbl_hash';

    /**
     * Internal function called from persistence
     * @throws \atk4\core\Exception
     * @internal
     */
    public function init()
    {
        // perform initialization of the model
        parent::init();

        // define fields of the model
        $this->addField('hash_string', [
            'type' => 'string', // type of field
            'system' => true // set not visible in UI
        ]);
    }

    /**
     * Set Hash to model converting from input string
     *
     * @param string $input_value Clear value to be converted
     * @return $this
     * @throws \atk4\data\Exception
     *
     */
    public function setHashFromInputString(string $input_value): self
    {
        $this->set('hash_string', password_hash($input_value, PASSWORD_DEFAULT));

        return $this;
    }

    /**
     * Validate if input string has an already valid hash in persistence
     *
     * @param string $clear_value
     * @return bool
     * @throws \atk4\core\Exception
     *
     */
    public function isAlreadyHashed(string $clear_value): bool
    {
        // Same as new static($this->persistence)
        // but better for performance
        $model = $this->newInstance();

        // loop model
        foreach ($model->getIterator() as $m) {
            if ($m->isValidHash($clear_value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate input string vs stored hash
     *
     * @param string $clear_value
     *
     * @return bool
     */
    public function isValidHash(string $clear_value): bool
    {
        return password_verify($clear_value, $this->get('hash_string'));
    }
}

$app = new App([
    'title' => 'How to Code Well', // App title
    'call_exit' => false // don't call exit function, in place use exception to avoid php script shutdown
]);

// add HTCW logo
$app->cdn['layout-logo'] = 'https://codechallenges.howtocodewell.net/icons/icon-144x144.png';

// Set layout of the app
$app->initLayout(Centered::class);

// connect to PDO
$app->dbConnect('sqlite:db.sq3');

// If table is not present is not present, create it.
(Migration::getMigration(new ModelHash($app->db)))->migrate();

// instantiate Model with persistence $app->db
$model = new ModelHash($app->db);

// add some header
$app->add([
    View::class,
    'element' => 'a'
])->set('2020 - February Challenge Description')->setAttr('href', 'https://codechallenges.howtocodewell.net/2020/february');

$app->add([View::class])->addClass('ui divider');
/**
 * Add form to app
 * @var Form $form
 */
$form = $app->add([View::class])->addClass('ui segment')->add(Form::class);
$form->buttonSave->set('Execute Action');

$app->add([View::class])->addClass('ui divider');

// response container
$res_container = $app->add([View::class])->addClass('ui segment');
$res_container->add([Header::class, 'Result message', 'size' => 4]);

// Add Loader to app and setup for reloading
/** @var Loader $loader */
$loader = $res_container->add([
    Loader::class,
    'loadEvent' => false
]);
$loader->set(function (Loader $loader) {
    $loader->add(View::class)->set($loader->stickyGet('action_response') ?? '');
});

// Add counter for already stored hash
$app->add([View::class])->addClass('ui divider');
$counter = $app->add([View::class])->addClass('ui segment')->set('Already hashed strings: ' . (int)$model->action('count')->getOne());

// Setup form
$form->setModel($model);

// Add virtual field to the form as input
$form->addField('input_value', [Line::class], [
    'never_persist' => true, // don't save to persistence
    'required' => true
]);

// Add virtual field to the form as dropdown
$form->addField('action_request', [DropDown::class], [
    'type' => 'enum',
    'values' => [
        'add' => 'Convert input string to hash and save to persistence',
        'validate' => 'Validate input string vs stored hashes'
    ],
    'never_persist' => true,
    'required' => true,
]);
// Set initial value
$form->model->set('action_request', 'add');

// Define form submit callback
$form->onSubmit(function ($f) use ($counter, $loader) {

    // get input from model
    $input = $f->model->get('input_value');
    $response = 'Input : ' . $input;

    // get selected action from dropdown
    switch ($f->model->get('action_request')) {
        case 'add':

            $already_hashed = $f->model->isAlreadyHashed($input);
            if (!$already_hashed) {
                $f->model->setHashFromInputString($input)->save();
            }

            $response .= $already_hashed ? ' was already hashed.' : ' => Hash : ' . $f->model->get('hash_string');

            break;

        case 'validate':
            $response .= $f->model->newInstance()->isAlreadyHashed($input) ? ' is valid!' : ' is not valid!';
            break;
    }

    // return JS actions to be done on response a sort of Partial fragment reload of UI
    return [
        $counter->jsReload(), // JS Reload counter
        $loader->jsLoad(['action_response' => $response]) // JS Load Loader with a new response message
    ];
});