<?php
/**
 * @package pcIdeHelper
 * @version 1.1.0
 * @url     https://github.com/proudcommerce/oxid-ide-modulehelper
 *
 * @author  Stefan Moises <beffy@proudcommerce.com>
 * @author  Tobias Merkl <tobias@proudcommerce.com>
 *
 * Script to generate a .module-helper.php file in the OXID root dir.
 * The generated file contains all module namespaces and extensions for your IDE to use for reference.
 * This way, the IDE should find the extended classes for every class that uses OXIDs
 * custom "_parent" logic to extend OXID classes.
 */

if (PHP_SAPI != 'cli') {
    die('Only cli execution allowed!');
}

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'bootstrap.php';

function _processMetadata($sPath)
{
    $str = '';
    try {
        include $sPath;

        if (isset($aModule['extend']) && count($aModule['extend'])) {
            $aNamespaces = [];
            foreach ($aModule['extend'] as $sParent => $sPathToModule) {
                // no valid namespaced class?
                if (strpos($sPathToModule, '\\') === false) {
                    continue;
                }
                // split at last backslash to get namespace and classname
                $namespace = substr($sPathToModule, 0, strrpos($sPathToModule, '\\'));
                $classname = substr($sPathToModule, strrpos($sPathToModule, '\\') + 1);
                // new namespace?
                if (!isset($aNamespaces[$namespace])) {
                    $aNamespaces[$namespace] = [];
                }
                $sFakeParent = $classname . '_parent';
                $aNamespaces[$namespace][$sFakeParent] = "\\" . $sParent;
            }
            // now generate string
            foreach ($aNamespaces as $sNameSpace => $aClasses) {
                $str .= "\n\nnamespace {$sNameSpace};";
                foreach ($aClasses as $sClass => $sParent) {
                    $str .= "\nclass {$sClass} extends {$sParent} {}";
                }
            }
        }
    } catch (\Exception $ex) {
        echo "\nCould not include file '$sPath': " . $ex->getMessage();
    }

    return $str;
}


$modulePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'modules';
$dir = new RecursiveDirectoryIterator($modulePath);
$iter = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iter, '/\/metadata\.php$/i', RecursiveRegexIterator::GET_MATCH);

$fileNames = [];
foreach ($files as $name => $file) {
    $fileNames[] = $name;
}
rsort($fileNames);

$str = "<?php\n/*\nAuto-generated file, do not edit! \nhttps://github.com/proudcommerce/oxid-ide-modulehelper\n*/\n";
foreach ($fileNames as $name) {
    $str .= _processMetadata($name);
}

// write to file
$helperFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.module-helper.php';
file_put_contents($helperFile, $str);
echo "\nDone, please check the generated file at " . str_replace('var/bin/../../../', '', $helperFile) . "\n";
