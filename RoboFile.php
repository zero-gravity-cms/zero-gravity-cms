<?php

/*
 * =================================================================
 * Start CI auto fetch (downloading robo dependencies automatically)
 * =================================================================.
 */
if (!is_file('.ci/vendor/autoload.php')) {
    (is_dir('.ci') || mkdir('.ci')) && chdir('.ci');
    exec('composer req c33s-toolkit/robo-file -n', $output, $resultCode);
    if ($resultCode > 0) {
        copy('https://getcomposer.org/composer.phar', 'composer');
        exec('php composer req c33s-toolkit/robo-file -n');
        unlink('composer');
    }
    chdir('..');
}
require '.ci/vendor/autoload.php';
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
    use \C33s\Robo\C33sTasks;

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
            'codeception' => '2.3.6',
            'composer' => '@latest',
            'php-cs-fixer' => 'v2.7.1',
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

        $this->composerGlobalRequire('fxp/composer-asset-plugin', '~1.3');
        $this->composerGlobalRequire('hirak/prestissimo', '^0.3');

        $this->update();
    }

    /**
     * Perform code-style checks.
     *
     * @param string $arguments Optional path or other arguments
     */
    public function check($arguments = '')
    {
        $this->_execPhp("php .ci/bin/php-cs-fixer.phar fix --verbose --dry-run $arguments");
    }

    /**
     * Perform code-style checks and cleanup source code automatically.
     *
     * @param string $arguments Optional path or other arguments
     */
    public function fix($arguments = '')
    {
        if ($this->confirmIfInteractive('Do you really want to run php-cs-fixer on your source code?')) {
            $this->_execPhp("php .ci/bin/php-cs-fixer.phar fix --verbose $arguments");
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
    }

    /**
     * Update the Project.
     */
    public function update()
    {
        if ($this->isEnvironmentCi()) {
            $this->_execPhp('php ./.ci/bin/composer.phar install --no-progress --no-suggest --prefer-dist --optimize-autoloader');
        } else {
            $this->_execPhp('php ./.ci/bin/composer.phar install');
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
