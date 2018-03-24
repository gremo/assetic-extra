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

/**
 * Runs assets through Babel.
 *
 * @link https://babeljs.io
 */
class BabeljsFilter extends BaseProcessFilter
{
    private $babelBin;
    private $retainLines;
    private $presets;
    private $plugins;
    private $noComments;
    private $compact;
    private $minified;
    private $noBabelRc;
    private $auxiliaryCommentBefore;
    private $auxiliaryCommentAfter;
    private $parserOptions;
    private $generatorOptions;

    public function __construct($babelBin = '/usr/bin/babel')
    {
        $this->babelBin = $babelBin;
    }

    /**
     * @param bool $retainLines
     */
    public function setRetainLines($retainLines)
    {
        $this->retainLines = $retainLines;
    }

    /**
     * @param string|array $presets
     */
    public function setPresets($presets)
    {
        $this->presets = (array) $presets;
    }

    /**
     * @param string|array $plugins
     */
    public function setPlugins($plugins)
    {
        $this->plugins = (array) $plugins;
    }

    /**
     * @param bool $noComments
     */
    public function setNoComments($noComments)
    {
        $this->noComments = $noComments;
    }

    /**
     * @param bool|string $compact
     */
    public function setCompact($compact)
    {
        $this->compact = $compact;
    }

    /**
     * @param bool $minified
     */
    public function setMinified($minified)
    {
        $this->minified = $minified;
    }

    /**
     * @param bool $noBabelRc
     */
    public function setNoBabelRc($noBabelRc)
    {
        $this->noBabelRc = $noBabelRc;
    }

    /**
     * @param string $auxiliaryCommentBefore
     */
    public function setAuxiliaryCommentBefore($auxiliaryCommentBefore)
    {
        $this->auxiliaryCommentBefore = $auxiliaryCommentBefore;
    }

    /**
     * @param string $auxiliaryCommentAfter
     */
    public function setAuxiliaryCommentAfter($auxiliaryCommentAfter)
    {
        $this->auxiliaryCommentAfter = $auxiliaryCommentAfter;
    }

    /**
     * @param string $parserOptions
     */
    public function setParserOptions($parserOptions)
    {
        $this->parserOptions = $parserOptions;
    }

    /**
     * @param string $generatorOptions
     */
    public function setGeneratorOptions($generatorOptions)
    {
        $this->generatorOptions = $generatorOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
        $pb = $this->createProcessBuilder(array($this->babelBin));

        if ($this->retainLines) {
            $pb->add('--retain-lines');
        }

        if (is_array($this->presets) && !empty($this->presets)) {
            $pb->add('--presets')->add(implode(',', $this->presets));
        }

        if (is_array($this->plugins) && !empty($this->plugins)) {
            $pb->add('--plugins')->add(implode(',', $this->plugins));
        }

        if ($this->noComments) {
            $pb->add('--no-comments');
        }

        if (null !== $this->compact) {
            $pb->add('--compact')->add(is_string($this->compact) ? $this->compact : var_export($this->compact, true));
        }

        if ($this->minified) {
            $pb->add('--minified');
        }

        if ($this->noBabelRc) {
            $pb->add('--no-babelrc');
        }

        if (null !== $this->auxiliaryCommentBefore) {
            $pb->add('--auxiliary-comment-before')->add($this->auxiliaryCommentBefore);
        }

        if (null !== $this->auxiliaryCommentAfter) {
            $pb->add('--auxiliary-comment-after')->add($this->auxiliaryCommentAfter);
        }

        if (null !== $this->parserOptions) {
            $pb->add('--parser-opts')->add($this->parserOptions);
        }

        if (null !== $this->generatorOptions) {
            $pb->add('--generator-opts')->add($this->generatorOptions);
        }

        $pb->add(realpath($asset->getSourceDirectory()).DIRECTORY_SEPARATOR.basename($asset->getSourcePath()));

        $proc = $pb->getProcess();
        $code = $proc->run();

        if (0 !== $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $asset->setContent($proc->getOutput());
    }
}
