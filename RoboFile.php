<?php

use Identicon\Identicon;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    /**
     * @var string
     */
    protected $ciBinDir = './.ci/bin';

    /**
     * @var string
     */
    protected $ciTmpDir = './.ci/tmp';

    /**
     * @var string
     */
    protected $ciInitializedFile = './.ci/.initialized';

    protected $defaultCheckPaths = [
        './src',
        './tests/unit',
        './RoboFile.php',
    ];

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        $this->stopOnFail(true);
    }

    /**
     * Initialize the Project.
     */
    public function init()
    {
        $question = 'have you read the README.md? (y/n)';
        if ($this->getInput()->isInteractive() && 'y' !== $this->ask($question)) {
            return 1;
        }

        $this->ciRebuild();

        $this->composerGlobalRequire('fxp/composer-asset-plugin', '~1.3');
        $this->composerGlobalRequire('hirak/prestissimo', '^0.3');

        $this->update();
    }

    /**
     * Run all Tests using codeception.
     */
    public function test()
    {
        $this->ciInit();
        $this->yell('Tests');
        $this->_exec('php ./vendor/bin/codecept run --coverage-xml --coverage-html --coverage-text');
    }

    /**
     * Run all Checks.
     */
    public function check()
    {
        $this->ciInit();
        foreach ($this->defaultCheckPaths as $path) {
            $this->fix($path, ['dry-run' => true]);
        }
    }

    /**
     * Fix all paths. Unfortunately robo fix only supports a single path.
     */
    public function fixAll(array $opts = ['dry-run' => false])
    {
        foreach ($this->defaultCheckPaths as $path) {
            $this->yell('Running fixer for '.$path);
            $this->fix($path, $opts);
        }
    }

    /**
     * Fix the Coding Style of the Project.
     *
     * @param string $directory
     * @param array  $opts
     */
    public function fix(string $directory = './src', array $opts = ['dry-run' => false])
    {
        $dryRun = '';
        if ($opts['dry-run']) {
            $dryRun = '--dry-run';
        }

        if (!$opts['dry-run']) {
            $this->yell('Be careful, you must not make a lot of changes to the project and then apply the fixer to everything (also files you have not touched)',
                40, 'red'
            );
            $this->say('');
        }

        if ($opts['dry-run'] || 'y' === $this->ask("Do you want to fix everything in '$directory' (y/n)")) {
            /*
             * we have to disable the empty_return fixer because PHP 7.1 explicitly requires "return null" on method
             * signatures like:
             *
             * public function foo(): ? Bar
             * {
             *     return;              // E_ERROR, is the same as
             *     return void;         // E_ERROR
             *     return null;         // will work
             *     return new Bar();    // will work
             *
             * The symfony fixer does not like "return null;", which can be disabled using "--fixers=-empty_return"
             *
             * @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/2091
             */
            $this->_exec("php .ci/bin/php-cs-fixer fix $directory --level=symfony --fixers=-empty_return --verbose $dryRun");
        }
    }

    /**
     * Update the Project.
     */
    public function update()
    {
        $this->ciRebuild();

        if ($this->isEnvironmentCi()) {
            $this->_exec('php ./.ci/bin/composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader');
        } else {
            $this->_exec('php ./.ci/bin/composer install');
        }
    }

    /**
     * Reinitialize the Build/CI Environment.
     */
    public function ciRebuild()
    {
        $this->ciClean();
        $this->ciInit();
    }

    /**
     * Clean/Empty Build/CI Environment.
     */
    public function ciClean()
    {
        $this->_remove([
            $this->ciBinDir,
            $this->ciTmpDir,
            $this->ciInitializedFile,
        ]
        );
    }

    /**
     * Initialize Build/CI Environment.
     *
     * @return int
     */
    public function ciInit()
    {
        if (file_exists($this->ciInitializedFile)) {
            return 0;
        }

        if (!file_exists($this->ciBinDir)) {
            $this->_mkdir($this->ciBinDir);
        }

        if (!file_exists($this->ciTmpDir)) {
            $this->_mkdir($this->ciTmpDir);
        }

        $downloads = $this->getDownloads();
        $error = false;
        foreach ($downloads as $name => $download) {
            $currentError = false;
            $currentBinary = "{$this->ciBinDir}/${name}";
            if (!file_exists($currentBinary)) {
                $this->_exec("wget ${download['url']} --output-document=${currentBinary} --quiet --");
                $this->_chmod($currentBinary, 0661);
            }

            if (array_key_exists('hash', $download) && array_key_exists('hash_algorithm', $download) && $download['hash'] !== '') {
                $realHashSum = hash_file($download['hash_algorithm'], $currentBinary);
                if (strtoupper($download['hash']) === strtoupper($realHashSum)) {
                    $this->say('signature ok');
                } else {
                    $this->_rename($currentBinary, $this->ciTmpDir.'/'.$name);
                    $currentError = true;
                    $this->yell("error: file signature does not match stored signature.\nfile:   '{$download['hash']}'\nstored: '{$realHashSum}'\nmoved downloaded file to {$this->ciTmpDir}/{$name}",
                        40, 'red'
                    );
                }
            }

            $error = $error || $currentError;
            if (!$currentError) {
                $this->createBatFile($currentBinary, $name);
            }
        }

        if ($error) {
            $this->say('Ci System not fully inizialized. error occured');

            return 1;
        }

        $this->_touch($this->ciInitializedFile);
        $pathExportCommand = $this->getExportPathCommand($this->ciBinDir);
        $this->say("Add {$this->ciBinDir} to your PATH env variable with:");
        $this->yell("${pathExportCommand}", 40, 'yellow');
    }

    /**
     * Returns the console command to export/set the CI binary path.
     *
     * @param $path
     *
     * @return string
     */
    protected function getExportPathCommand($path): string
    {
        $fullBinaryPath = realpath(getcwd().'/'.$path);
        if ($fullBinaryPath === false) {
            $this->yell("invalid Binary Path '$path' (realpath was not able to resolve)");
            exit(1);
        }
        if ($this->isWin()) {
            str_replace('/', '\\', $fullBinaryPath);
            $pathExportCommand = $this->getExportCommand('PATH', "%PATH%;${fullBinaryPath}");
        } else {
            str_replace('\\', '/', $fullBinaryPath);
            $pathExportCommand = $this->getExportCommand('PATH', "\$PATH:${fullBinaryPath}");
        }

        return $pathExportCommand;
    }

    /**
     * Helper to create a OS specific Environment Variable export/set command.
     *
     * @param $name
     * @param $value
     *
     * @return string
     */
    protected function getExportCommand($name, $value): string
    {
        $name = strtoupper($name);

        if ($this->isWin()) {
            $pathExportCommand = "set {$name}={$value}";
        } else {
            $pathExportCommand = "export {$name}={$value}";
        }

        return $pathExportCommand;
    }

    /**
     * Checks if the current Environment is Production.
     *
     * @return bool
     */
    protected function isEnvironmentProduction(): bool
    {
        if (strtolower(getenv('ENVIRONMENT')) == 'dev') {
            return false;
        }
        if ($this->isEnvironmentCi()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the current Enviroment is CI.
     *
     * @return bool
     */
    protected function isEnvironmentCi(): bool
    {
        if (getenv('CI') == 'true') {
            return true;
        }
        if (getenv('CI_BUILD_TOKEN') !== false) {
            return true;
        }
        if (getenv('CI_JOB_ID') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the System is Windows or not.
     *
     * @return bool
     */
    protected function isWin()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }

        return false;
    }

    /**
     * Wrapper for Robo shortcut _exec to smart fix the commands slashes on Windows Systems.
     *
     * @param $command
     *
     * @return \Robo\Result
     */
    protected function _exec($command)
    {
        $command = $this->fixCommandSlashes($command);

        return $this->taskExec($command)->run();
    }

    /**
     * Smart Slashes Fixer based upon OS.
     *
     * @param $command
     *
     * @return mixed|string
     */
    protected function fixCommandSlashes($command)
    {
        if ($command[0] == '.' || $command[0] == '/') {
            if (!stristr($command, ' ')) {
                return str_replace('/', DIRECTORY_SEPARATOR, $command);
            }
            $commandParts = explode(' ', $command, 2);

            $firstCommandPart = str_replace('/', DIRECTORY_SEPARATOR, $commandParts[0]);
            $secondCommand = $commandParts[1];

            return $firstCommandPart.' '.$secondCommand;
        }

        return $command;
    }

    /**
     * Helper for Calling commands with xdebug enabled which is globally disabled.
     *
     * @return string
     */
    protected function enableXdebug()
    {
        $xdebugSoLoaded = getenv('XDEBUG_SO');
        if (!extension_loaded('xdebug') && $xdebugSoLoaded === false) {
            $this->yell('Error: you need to set the XDEBUG_SO environment variable to use metrics. must have the path to xdebug.so', 40, 'red');

            exit(1);
        }

        if ($xdebugSoLoaded !== false) {
            return "-d zend_extension=${$xdebugSoLoaded}";
        }

        return '';
    }

    /**
     * Batfile generator.
     *
     * @param $file
     * @param $name
     */
    protected function createBatFile($file, $name)
    {
        if (!file_exists($file.'.bat') && strpos($name, '.pubkey') === false) {
            $this->taskWriteToFile("${file}.bat")
                ->line('@ECHO OFF')
                ->line("php %~dp0${name} %*")
                ->run()
            ;
        }
    }

    protected function composerGlobalRequire($module, $version)
    {
        try {
            $this->_exec("php ./.ci/bin/composer global why ${module}");
        } catch (Exception $e) {
            $this->_exec("php ./.ci/bin/composer global require \"${module}:${version}\"");
        }
    }

    protected function getDownloads()
    {
        return [
////            'symfony' => [
////                'url' => 'https://symfony.com/installer',
////                'hash' => 'f9c380c58157c6a1c7bf8f4e67947697f842f2a123a87098d6b2a796fcf2407e68a67ddf0a3acb30c8bcb434906445b47d2d962f396c235d8cb44e986d99323e',
////                'hash_algorithm' => 'sha512',
////            ],
////            'robo' => [
////                'url' => 'http://robo.li/robo.phar',
////                'hash' => 'fe01860df851c2b9c64f5d7b0d6d3081611160eebb47b0c7082531817b841a924fc17f99b670fb0b9bc96fcc1ff27eb68e8dcf2ee64ccd6a18df826cfc746d3c',
////                'hash_algorithm' => 'sha512',
////            ],
'composer' => [
    'url' => 'https://getcomposer.org/download/1.2.0/composer.phar',
    'hash' => '21e6bc3672a3d7df683d1ff85a5f89a857a24e5cf563cc714e9331d9b76bdfc232494599c5188604dce18c6edd0ba8d015ca738537d99e985c58d94b9b466f43',
    'hash_algorithm' => 'sha512',
],
//            'phpunit' => [
//                'url' => 'https://phar.phpunit.de/phpunit-5.4.6.phar', //'https://phar.phpunit.de/phpunit.phar',
//                'hash' => '25ff3c2614011bf81dfb191d6211aec870e9a07f9e4bfe85e165d632277c1668f56ecfbd8b9bf004f0e1169b2648266a687c52d2124a2646eba2389c487a4893',
//                'hash_algorithm' => 'sha512',
//            ],
//             'phpunit' => [
//                 'url' => 'https://phar.phpunit.de/phpunit-6.1.3.phar', //'https://phar.phpunit.de/phpunit.phar',
//                 'hash' => '1b65229a207dca72ca87211e35a9b27b39eda4eeb4d4ff8e4a4b503f230a9e800d705d132f9c739e37ee152e75729ed0e7a09c8ec6103c3f6d8a4a6d2de3e974',
//                 'hash_algorithm' => 'sha512',
//             ],
//             'phpmd' => [
//                 'url' => 'http://static.phpmd.org/php/2.4.3/phpmd.phar', //'http://static.phpmd.org/php/latest/phpmd.phar',
//                 'hash' => '82d27d2787d2af1cbbe987e0b3fe3e8fef30e2e9667b83b3d2151e42c8dd14cea37b66ad4cdd12bc2bee756241a35902c22d5d46e80d79de9276fa1e1b2b7006',
//                 'hash_algorithm' => 'sha512',
//             ],
//            'phpcpd' => [
//                'url' => 'https://phar.phpunit.de/phpcpd-2.0.4.phar', //'https://phar.phpunit.de/phpcpd.phar',
//                'hash' => '8c62c1a4cd539e6dd491f3dac85ea3b4a45cd3d20e96a4c44162d60c8fbf6caebf5b948503330b752621e849d649cf630f4076f72706fb2ef4f8376c4770a941',
//                'hash_algorithm' => 'sha512',
//            ],
//            'deptrac' => [
//                'url' => 'http://get.sensiolabs.de/deptrac.phar',
//                'hash' => '0a117a608eee7f60da42b04dba563fa692affe9768d583acdb5bb03535a5ed65ed0d1a57e523b72fa76668d7e1e1d1d71ed3f26b1ee6d0f3bbbd5346a44bbde7',
//                'hash_algorithm' => 'sha512',
//            ],
'php-cs-fixer' => [
    'url' => 'http://get.sensiolabs.org/php-cs-fixer-v1.11.6.phar',  //http://get.sensiolabs.org/php-cs-fixer.phar
    'hash' => '6b5947e4b2e0afa05e461b72939b34aba2efb5347695ff13b469ac9eb0b677aae1bc7e7bc0fba75c85762e77db430c950f951f2a61c9b12c4ddde6b1e6d45066',
    'hash_algorithm' => 'sha512',
],
'codeception' => [
    'url' => 'http://codeception.com/releases/2.3.1/codecept.phar',  //http://codeception.com/codecept.phar
    'hash' => '9672F2B47D64B0FF4F91ACDB564EAAF6D8E304642FF2BE17DA575A813427D59AE357F66097D37B9F757B4B5E5A68773D48DE05167BD21BAFA082C416B766E036',
    'hash_algorithm' => 'sha512',
],
//            'phpcs' => [
//                'url' => 'https://github.com/squizlabs/PHP_CodeSniffer/releases/download/2.6.2/phpcs.phar',  //https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
//                'hash' => '539ed587abde12d7c17b9f2df064e84d49c8e5c36f3ed88c1047c22d934f80ef362d08d064e9720632f63de9b65f561f3a077bb6e61455bd080a7029e71662f5',
//                'hash_algorithm' => 'sha512',
//            ],
//            'phpcbf' => [
//                'url' => 'https://github.com/squizlabs/PHP_CodeSniffer/releases/download/2.6.2/phpcbf.phar',  //https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar
//                'hash' => 'e2b42e620ee064815fc8f55030978b3f313876fad8e67035473e1a3e019c75ffd7e9f5e3fbb8742b5576f33016c0cc1fee00d266df88d4f2e93df9ac57f5fdb0',
//                'hash_algorithm' => 'sha512',
//            ],
//            'phpmetrics' => [
//                'url' => 'https://github.com/phpmetrics/PhpMetrics/blob/v1.10.0/build/phpmetrics.phar',  //https://github.com/phpmetrics/PhpMetrics/raw/master/build/phpmetrics.phar
//                'hash' => 'e5f534cdb24cb73076ab85d78f7fe6a4a5ea8e6e382c9bf0fb946c7979408d7b8c337e5491980c8896e79a44fc38e005604808464a1babc051f6d7fd3c3a1121',
//                'hash_algorithm' => 'sha512',
//            ],
//            'humbug.phar' => [
//                'url' => 'https://padraic.github.io/humbug/downloads/humbug.phar',
//                'hash' => '1f2851568c39e50b995d181652a9a17f1f3b69bfc4d7746e53b2ff6ee5507d9350e07344575edc3252b8a5a7112df49c2b72590a19cbe65e853a3a8fe96cf280',
//                'hash_algorithm' => 'sha512',
//            ],
//            'humbug.phar.pubkey' => [
//                'url' => 'https://padraic.github.io/humbug/downloads/humbug.phar.pubkey',
//                'hash_algorithm' => 'sha512',
//                'hash' => '',
//            ],
//            'smoke' => [
//                'url' => 'http://pharchive.phmlabs.com/archive/phmLabs/Smoke/current/Smoke.phar',
//                'hash' => 'b83fa3bb53568a32239a7facee54029434512ec812b544fb8a101f1722451398acfa8e524f174a99e140476c723b062d017814546a5dc40b13e03ca737727e62',
//                'hash_algorithm' => 'sha512',
//            ],
//            'sami' => [
//                'url' => 'http://get.sensiolabs.org/sami.phar',
//                'hash' => 'eb679d54605151c92eca2cb707e07cb2ac4ea4754fb14bb8068ca00700158f3eb6c33bd2dce6562744f476843b33ee0135d9ba12902d46642bc5701bc26afd36',
//                'hash_algorithm' => 'sha512',
//            ],
//            'phpdox' => [
//                'url' => 'https://github.com/theseer/phpdox/releases/download/0.8.1.1/phpdox-0.8.1.1.phar',
//                'hash' => '9355bafa1006c72ec97b8e8f2f0e5191604b5d43b583e80b08d72efc47b537cce0cb95e2b33180948072c55e8097974ffd3a4da6c21849a62194c700f8718d2b',
//                'hash_algorithm' => 'sha512',
//            ],
//            'phpdoc' => [
//                'url' => 'http://phpdoc.org/phpDocumentor.phar',
//                'hash' => '908348766c0bcf112789a8ee4e614fda49985b07d06aee00129521a791b25327f923529acd7a1ec888c7ba5b709603122c8adae4b95936399bebbc87a374fbae',
//                'hash_algorithm' => 'sha512',
//            ],
//            'apigen' => [
//                'url' => 'http://apigen.org/apigen.phar',
//                'hash' => '67d87b8274d92d7ed6afc8ffefee48cc8469f125dc68a4bb6efef6d143b44dd2bfd6e992c56bdd0a7a30324a05424aa811e2a4b2e423c61a8feb31d238d0779a',
//                'hash_algorithm' => 'sha512',
//            ],
        ];
    }

    /**
     * (Re-)Generate test fixture images using identicon library.
     */
    public function generateTestIdenticons()
    {
        require_once __DIR__.'/vendor/autoload.php';
        $basePath = __DIR__.'/tests/_data/page_fixtures/valid_pages/';

        $files = [
            'root_file1.png',
            'root_file2.png',
            '01.yaml_only/file1.png',
            '01.yaml_only/file2.png',
            '01.yaml_only/file3.png',
            '04.with_children/01.child1/child_file1.png',
            '04.with_children/01.child1/child_file2.png',
            '04.with_children/01.child1/child_file3.png',
            '04.with_children/01.child1/child_file4.png',
            '04.with_children/03.empty/child_file5.png',
            '04.with_children/03.empty/child_file6.png',
            '04.with_children/03.empty/sub/dir/child_file7.png',
            '04.with_children/03.empty/sub/dir/child_file8.png',
            'images/header/top-header.png',
            'images/footer/footer.png',
            'images/file1.png',
            'images/person_a.png',
            'images/person_b.png',
            'images/person_c.png',
            'images/gallery/fancy_picture_01.png',
            'images/gallery/fancy_picture_02.png',
            'images/gallery/fancy_picture_03.png',
            'images/gallery/fancy_picture_20.png',
            'images/gallery/fancy_picture_21.png',
        ];

        $identicon = new Identicon();
        $fs = new Filesystem();

        foreach ($files as $file) {
            $path = $basePath.$file;
            $dir = dirname($path);
            if (!is_dir($dir)) {
                $fs->mkdir($dir);
            }

            echo "$path\n";
            $data = $identicon->getImageData($file, 64);
            file_put_contents($path, $data);
        }
    }
}
