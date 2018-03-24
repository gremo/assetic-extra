<?php

/*
 * This file is part of the assetic-extra package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\AsseticExtra\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;
use Assetic\Filter\BaseProcessFilter;
use Assetic\Util\FilesystemUtils;

/**
 * Filters assets through Node-sass.
 *
 * @link https://github.com/sass/node-sass
 */
class NodeSassFilter extends BaseProcessFilter
{
    private $nodeSassBin;
    private $importPaths;
    private $outputStyle;
    private $indentType;
    private $indentWidth;
    private $linefeed;
    private $precision;
    private $sourceComments;
    private $sourceMapLocation;
    private $sourceMapPublicDir;

    public function __construct($nodeSassBin = '/usr/bin/node-sass')
    {
        $this->nodeSassBin = $nodeSassBin;
        $this->importPaths = array();
    }

    /**
     * @param array $importPaths
     */
    public function setImportPaths(array $importPaths)
    {
        $this->importPaths = $importPaths;
    }

    /**
     * @param string $outputStyle
     */
    public function setOutputStyle($outputStyle)
    {
        $this->outputStyle = $outputStyle;
    }

    /**
     * @param string $indentType
     */
    public function setIndentType($indentType)
    {
        $this->indentType = $indentType;
    }

    /**
     * @param string $indentWidth
     */
    public function setIndentWidth($indentWidth)
    {
        $this->indentWidth = $indentWidth;
    }

    /**
     * @param string $linefeed
     */
    public function setLinefeed($linefeed)
    {
        $this->linefeed = $linefeed;
    }

    /**
     * @param string $precision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
    }

    /**
     * @param bool $sourceComments
     */
    public function setSourceComments($sourceComments)
    {
        $this->sourceComments = $sourceComments;
    }

    /**
     * @param string $sourceMapLocation
     */
    public function setSourceMapLocation($sourceMapLocation)
    {
        $this->sourceMapLocation = rtrim($sourceMapLocation, '\\/');
    }

    /**
     * @param string $sourceMapPublicDir
     */
    public function setSourceMapPublicDir($sourceMapPublicDir)
    {
        $this->sourceMapPublicDir = rtrim($sourceMapPublicDir, '\\/');
    }

    /**
     * {@inheritdoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
        $pb = $this->createProcessBuilder(array($this->nodeSassBin));

        if ($this->outputStyle) {
            $pb->add('--output-style')->add($this->outputStyle);
        }

        if ($this->indentType) {
            $pb->add('--indent-type')->add($this->indentType);
        }

        if ($this->indentWidth) {
            $pb->add('--indent-width')->add($this->indentWidth);
        }

        if ($this->linefeed) {
            $pb->add('--linefeed')->add($this->linefeed);
        }

        if ($this->precision) {
            $pb->add('--precision')->add($this->precision);
        }

        if ($this->sourceComments) {
            $pb->add('--source-comments');
        }

        if ($this->sourceMapLocation) {
            $mapFileName = $this->createMapFileName($asset->getSourcePath());
            $pb->add('--source-map')->add($mapFileName);
        }

        $importPaths = $this->importPaths;
        array_unshift($importPaths, $asset->getSourceDirectory());
        foreach ($importPaths as $path) {
            if ($path = realpath($path)) {
                $pb->add('--include-path')->add($path);
            }
        }

        $input = FilesystemUtils::createTemporaryFile('nodesass_in');
        file_put_contents($input, $asset->getContent());

        $pb->add($input);

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $output = $proc->getOutput();

        if ($this->sourceMapLocation) {
            $output = $this->extractAndSaveSourceMap($output, $mapFileName);
        }

        $asset->setContent($output);
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
    }

    private function createMapFileName($sourcePath)
    {
        $pathInfo = pathinfo($sourcePath);

        // Create a unique map name for the asset
        return sprintf(
            '%s_%s.%s.map',
            $pathInfo['filename'],
            crc32($sourcePath),
            $pathInfo['extension']
        );
    }

    private function extractAndSaveSourceMap($output, $mapFileName)
    {
        // Extract mapping from node-sass output
        preg_match_all(
            "/(?<css>.*)(?<reference>\/\*\# sourceMappingURL=.*\*\/)(?<map>.*)/ms",
            $output,
            $matches
        );
        if (!isset($matches['map'])) {
            return $output;
        }

        // Write source map to sourceMapLocation
        $mappingFilePath = realpath($this->sourceMapLocation).DIRECTORY_SEPARATOR.$mapFileName;
        file_put_contents($mappingFilePath, $matches['map'][0]);

        // Rewrite sourceMappingURL when sourceMapPublicDir is provided
        $sourceMappingURL = $this->sourceMapPublicDir
            ? '/*# sourceMappingURL='.$this->sourceMapPublicDir.'/'.$mapFileName.'*/'
            : $matches['reference'][0];

        return $matches['css'][0].$sourceMappingURL;
    }
}
