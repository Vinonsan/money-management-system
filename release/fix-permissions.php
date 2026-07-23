<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$root = realpath(__DIR__);
if ($root === false) {
    http_response_code(500);
    exit("Unable to resolve application root.\n");
}

$directories = 0;
$files = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $root,
        FilesystemIterator::SKIP_DOTS
    ),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    if ($item->isLink()) {
        continue;
    }

    $path = $item->getPathname();
    if ($item->isDir()) {
        if (@chmod($path, 0755)) {
            $directories++;
        }
        continue;
    }

    if ($path !== __FILE__ && @chmod($path, 0644)) {
        $files++;
    }
}

@chmod($root, 0755);
@chmod(__FILE__, 0644);

echo "Permissions fixed.\n";
echo "Directories set to 0755: {$directories}\n";
echo "Files set to 0644: {$files}\n";
echo "Delete this script if it is still present.\n";

@unlink(__FILE__);
