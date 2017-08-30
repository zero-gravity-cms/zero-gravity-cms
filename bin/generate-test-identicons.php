#!/usr/bin/env php
<?php

/**
 * This little script is used to generate test fixture images. Run it from the project root:
 * `php bin/generate-test-identicons.php`
 */

require __DIR__ . '/../vendor/autoload.php';

$basePath = __DIR__ . '/../tests/_data/page_fixtures/valid_pages/';

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

$identicon = new \Identicon\Identicon();
$fs = new \Symfony\Component\Filesystem\Filesystem();

foreach ($files as $file) {
    $path = $basePath . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) {
        $fs->mkdir($dir);
    }

    echo "$path\n";
    $data = $identicon->getImageData($file, 64);
    file_put_contents($path, $data);
}
