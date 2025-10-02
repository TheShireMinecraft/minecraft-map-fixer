<?php

declare(strict_types=1);

use Aternos\Nbt\IO\Reader\GZipCompressedStringReader;
use Aternos\Nbt\IO\Writer\GZipCompressedStringWriter;
use Aternos\Nbt\NbtFormat;
use Aternos\Nbt\Tag\CompoundTag;
use Aternos\Nbt\Tag\Tag;
use Aternos\Nbt\Tag\TagType;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

require '../vendor/autoload.php';

$logger = new Logger('MinecraftMapFixer');
$logger->pushHandler(new StreamHandler('../logs/minecraft-map-fixer.log', Level::Debug));
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

$directoryToScan = '../assets/';
$outputDirectory = '../assets/fixed';

// Scan the directory for map_*.dat files and fix each one
$paths = glob($directoryToScan . 'map_*.dat');
foreach ($paths as $path) {
    fixMapFile($path, $outputDirectory, $logger);
}

// Broken maps have a data version >4440 and a dimension tag that is not a string
function fixMapFile(
    string $path,
    string $outputDirectory,
    LoggerInterface $logger
): void
{
    $file = file_get_contents($path);

    /** @noinspection PhpUnhandledExceptionInspection */
    $reader = new GZipCompressedStringReader(
        $file,
        NbtFormat::JAVA_EDITION
    );

    /** @noinspection PhpUnhandledExceptionInspection */
    $rootTag = Tag::load($reader);

    if (!($rootTag instanceof CompoundTag)) {
        $logger->error("Root tag is not a compound tag", [
            'path' => $path,
            'rootTagType' => get_class($rootTag)
        ]);
        return;
    }

    $dataVersion = $rootTag->getInt("DataVersion")?->getValue() ?? 0;

    if ($dataVersion <= 4440) {
        $logger->info("No fix needed for this data version", [
            'path' => $path,
            'dataVersion' => $dataVersion
        ]);
        return;
    }

    $data = $rootTag->getCompound("data");

    if (is_null($data)) {
        $logger->error("No data tag found", [
            'path' => $path,
        ]);
    }

    $dimension = $data->get("dimension");

    if ($dimension->getType() === TagType::TAG_String) {
        return;
    }

    // At this point, dataVersion is >4440 and dimension is not a string

    // Set the data version to 1343
    $dataVersionTag = $rootTag->getInt("DataVersion");
    $dataVersionTag->setValue(1343);

    // Save the corrected map
    $writer = new GZipCompressedStringWriter();
    $rootTag->write($writer);
    $newFileContent = $writer->getStringData();

    $fileName = basename($path);
    $outPath = $outputDirectory . '/' . $fileName;
    if (file_put_contents($outPath, $newFileContent) === false)
    {
        $logger->error("Unable to write fixed file", [
            'target' => $outPath,
        ]);
        return;
    }

    $logger->info("Successfully fixed map file", [
        'fileName' => $fileName,
        'newDataVersion' => $dataVersionTag->getValue()
    ]);
}
