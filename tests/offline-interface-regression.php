<?php

use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\ValidateFormInput;
use Illuminate\Config\Repository;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Illuminate\View\ViewServiceProvider;
use Symfony\Component\HttpFoundation\Response;

/** Run: php tests/offline-interface-regression.php (after composer install). */
$root = dirname(__DIR__);
require getenv('CARDER_TEST_AUTOLOAD') ?: $root.'/vendor/autoload.php';
spl_autoload_register(function ($class) use ($root) {
    if (str_starts_with($class, 'App\\')) {
        $path = $root.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
}, true, true);

$app = new Application($root);
Facade::setFacadeApplication($app);
$app->instance('config', new Repository([
    'view' => ['paths' => [$root.'/resources/views'], 'compiled' => sys_get_temp_dir().'/carder-offline-blade'],
]));
$app->instance('files', new Filesystem);
$app->register(EventServiceProvider::class);
$app->register(ViewServiceProvider::class);
$translator = new Translator(new ArrayLoader, 'en');
$app->instance('validator', new Factory($translator, $app));

$passed = 0;
$assert = function ($condition, $description) use (&$passed) {
    if (! $condition) {
        throw new RuntimeException($description);
    }
    $passed++;
};
$cases = [
    [['name' => ' John'], ['name']],
    [['name' => "\tJohn"], ['name']],
    [['name' => "\nJohn"], ['name']],
    [['name' => "\u{00A0}John"], ['name']],
    [['name' => "\u{3000}John"], ['name']],
    [['name' => "\u{FEFF}John"], ['name']],
    [['items' => [['name' => ' Invalid']]], ['items.0.name']],
    [['password' => ' Secret!'], ['password']],
    [['name' => 'John Dias', 'notes' => "First\n Second", 'count' => 0], []],
    [['name' => 'සිංහල', 'description' => 'தமிழ்', 'optional' => ''], []],
    [['_token' => ' transport-token', '_method' => 'POST'], []],
];
foreach ($cases as [$input, $expected]) {
    foreach (['form', 'json', 'query'] as $type) {
        $request = $type === 'json'
            ? Request::create('/probe', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($input))
            : Request::create('/probe', $type === 'query' ? 'GET' : 'POST', $input);
        $called = false;
        try {
            (new TrimStrings)->handle($request, function ($request) use (&$called) {
                return (new ValidateFormInput)->handle($request, function () use (&$called) {
                    $called = true;

                    return new Response('OK');
                });
            });
            $assert($expected === [] && $called, 'Invalid input reached controller');
        } catch (ValidationException $exception) {
            $assert(array_keys($exception->errors()) === $expected && ! $called, 'Incorrect validation errors');
        }
    }
}

$compiler = $app->make('blade.compiler');
$compiledDirectory = $app['config']['view.compiled'];
if (! is_dir($compiledDirectory)) {
    mkdir($compiledDirectory, 0700, true);
}
$bladeCount = 0;
$phpCount = 0;
foreach (['app', 'routes', 'config', 'database', 'resources/views', 'lang'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory));
    foreach ($iterator as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
            continue;
        }
        $path = $file->getPathname();
        if (str_ends_with($path, '.blade.php')) {
            $compiled = $compiledDirectory.'/'.sha1($path).'.php';
            file_put_contents($compiled, $compiler->compileString(file_get_contents($path)));
            $path = $compiled;
            $bladeCount++;
        } else {
            $phpCount++;
        }
        $command = escapeshellarg(PHP_BINARY);
        if (php_ini_loaded_file()) {
            $command .= ' -c '.escapeshellarg(php_ini_loaded_file());
        }
        exec($command.' -l '.escapeshellarg($path).' 2>&1', $output, $status);
        if ($status !== 0) {
            throw new RuntimeException($file->getPathname()."\n".implode("\n", $output));
        }
        $output = [];
    }
}
printf("PASS: %d whitespace cases; %d PHP files; %d compiled Blade views.\n", $passed, $phpCount, $bladeCount);
