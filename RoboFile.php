<?php

// https://github.com/squizlabs/PHP_CodeSniffer/issues/2015
// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Generic.Files.LineLength.TooLong
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
define('C33S_SKIP_LOAD_DOT_ENV', true);
/*
 * =================================================================
 * Start CI auto fetch (downloading robo dependencies automatically)
 * =================================================================.
 */
define('C33S_ROBO_DIR', '.robo');

$roboDir = C33S_ROBO_DIR;
$previousWorkingDir = getcwd();
(is_dir($roboDir) || mkdir($roboDir)) && chdir($roboDir);
if (!is_file('composer.json')) {
    exec('composer init --no-interaction', $output, $resultCode);
    exec('composer require c33s/robofile --no-interaction', $output, $resultCode);
    exec('rm composer.yaml || rm composer.yml || return true', $output, $resultCode2);
    if ($resultCode > 0) {
        copy('https://getcomposer.org/composer.phar', 'composer');
        exec('php composer require c33s/robofile --no-interaction');
        unlink('composer');
    }
} else {
    exec('composer install --dry-run --no-interaction 2>&1', $output);
    if (false === in_array('Nothing to install or update', $output)) {
        fwrite(STDERR, "\n##### Updating .robo dependencies #####\n\n") && exec('composer install --no-interaction');
    }
}
chdir($previousWorkingDir);
require $roboDir.'/vendor/autoload.php';
/*
 * =================================================================
 *                        End CI auto fetch
 * =================================================================.
 */

use Identicon\Identicon;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    const GLOBAL_COMPOSER_PACKAGES = [];

    use \C33s\Robo\C33sTasks;
    use \C33s\Robo\C33sExtraTasks;

    protected $portsToCheck = [
        // 'http' => null,
        // 'https' => null,
        // 'mysql' => null,
        // 'postgres' => null,
        // 'elasticsearch' => null,
        // 'mongodb' => null,
    ];

    /**
     * @hook pre-command
     */
    public function preCommand()
    {
        $this->stopOnFail(true);
        $this->_prepareCiModules([
            'composer' => '2.0.7',
            'php-cs-fixer' => 'v2.17.3',
            'phpstan' => '0.12.67',
            'phpcs' => '3.4.0',
        ]);
    }

    /**
     * Initialize project.
     */
    public function init()
    {
        if (!$this->confirmIfInteractive('Have you read the README.md?')) {
            $this->abort();
        }

        if (!$this->ciCheckPorts($this->portsToCheck)) {
            if (!$this->confirmIfInteractive('Do you want to continue?')) {
                $this->abort();
            }
        }

        foreach (self::GLOBAL_COMPOSER_PACKAGES as $package => $version) {
            $this->composerGlobalRequire($package, $version);
        }

        $this->update();
    }

    /**
     * Perform code-style checks.
     *
     * @param string $arguments Optional path or other arguments
     */
    public function check($arguments = '')
    {
        $this->_execPhp("php .robo/bin/php-cs-fixer.phar fix --verbose --dry-run $arguments");
    }

    /**
     * Perform code-style checks and cleanup source code automatically.
     *
     * @param string $arguments Optional path or other arguments
     */
    public function fix($arguments = '')
    {
        if ($this->confirmIfInteractive('Do you really want to run php-cs-fixer on your source code?')) {
            $this->_execPhp("php .robo/bin/php-cs-fixer.phar fix --verbose $arguments");
        } else {
            $this->abort();
        }
    }

    /**
     * Run tests.
     */
    public function test()
    {
        $this->_execPhp('php ./vendor/bin/codecept run --coverage-xml --coverage-html --coverage-text', true);
        $this->outputCoverage();
    }

    /**
     * Write plain coverage line used for gitlab CI detecting the coverage score.
     */
    private function outputCoverage(): void
    {
        $this->writeln(file(__DIR__.'/tests/_output/coverage.txt')[8]);
    }

    /**
     * Update the Project.
     */
    public function update()
    {
        if ($this->isEnvironmentCi() || $this->isEnvironmentProduction()) {
            $this->_execPhp('php ./.robo/bin/composer.phar install --no-progress --no-suggest --prefer-dist --optimize-autoloader');
        } else {
            $this->_execPhp('php ./.robo/bin/composer.phar install');
        }
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
            '04.with_children/_child1/child_file1.png',
            '04.with_children/_child1/child_file2.png',
            '04.with_children/_child1/child_file3.png',
            '04.with_children/_child1/child_file4.png',
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
