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

    public function __construct($nodeSassBin = '/usr/bin/node-sass')
    {
        $this->nodeSassBin = $nodeSassBin;
        $this->importPaths = array();
    }

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
     * @param string $sourceComments
     */
    public function setSourceComments($sourceComments)
    {
        $this->sourceComments = $sourceComments;
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

        $importPaths = $this->importPaths;
        array_unshift($importPaths, $asset->getSourceDirectory());
        foreach ($importPaths as $path) {
            if ($path = realpath($path)) {
                $pb->add('--include-path')->add($path);
            }
        }

        $pb->add(realpath($asset->getSourceDirectory()).DIRECTORY_SEPARATOR.basename($asset->getSourcePath()));

        $proc = $pb->getProcess();
        $code = $proc->run();

        if (0 !== $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $asset->setContent($proc->getOutput());
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
    }
}
