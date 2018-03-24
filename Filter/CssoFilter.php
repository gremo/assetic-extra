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
 * Filters assets through CSSO.
 *
 * @link https://github.com/css/csso
 */
class CssoFilter extends BaseProcessFilter
{
    private $cssoBin;
    private $comments;
    private $forceMediaMerge;
    private $restructureOff;
    private $usage;

    public function __construct($cssoBin = '/usr/bin/csso')
    {
        $this->cssoBin = $cssoBin;
    }

    /**
     * @param string $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @param bool $forceMediaMerge
     */
    public function setForceMediaMerge($forceMediaMerge)
    {
        $this->forceMediaMerge = $forceMediaMerge;
    }

    /**
     * @param bool $restructureOff
     */
    public function setRestructureOff($restructureOff)
    {
        $this->restructureOff = $restructureOff;
    }

    /**
     * @param string $usage
     */
    public function setUsage($usage)
    {
        $this->usage = $usage;
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
        $pb = $this->createProcessBuilder(array($this->cssoBin));

        $input = FilesystemUtils::createTemporaryFile('csso_in');
        file_put_contents($input, $asset->getContent());

        $pb->add($input);

        if ($this->comments) {
            $pb->add('--comments')->add($this->comments);
        }

        if ($this->forceMediaMerge) {
            $pb->add('--force-media-merge');
        }

        if ($this->restructureOff) {
            $pb->add('--restructure-off');
        }

        if ($this->usage) {
            $pb->add('--usage')->add($this->usage);
        }

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $asset->setContent($proc->getOutput());
    }
}
