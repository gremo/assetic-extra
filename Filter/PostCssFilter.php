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
 * Filters assets through PostCSS.
 *
 * @link http://postcss.org
 */
class PostCssFilter extends BaseProcessFilter
{
    private $postCssBin;
    private $noMap;
    private $use;
    private $parser;
    private $stringifier;
    private $syntax;
    private $config;

    public function __construct($postCssBin = '/usr/bin/postcss')
    {
        $this->postCssBin = $postCssBin;
    }

    /**
     * @param bool $noMap
     */
    public function setNoMap($noMap)
    {
        $this->noMap = $noMap;
    }

    /**
     * @param mixed $use
     */
    public function setUse($use)
    {
        $this->use = $use;
    }

    /**
     * @param string $parser
     */
    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $stringifier
     */
    public function setStringifier($stringifier)
    {
        $this->stringifier = $stringifier;
    }

    /**
     * @param string $syntax
     */
    public function setSyntax($syntax)
    {
        $this->syntax = $syntax;
    }

    /**
     * @param string $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
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
        $pb = $this->createProcessBuilder(array($this->postCssBin));

        $input = FilesystemUtils::createTemporaryFile('postcss_in');
        $output = FilesystemUtils::createTemporaryFile('postcss_out');
        file_put_contents($input, $asset->getContent());

        $pb->add($input);

        if ($this->noMap) {
            $pb->add('--no-map');
        }

        if ($this->use) {
            foreach ((array) $this->use as $use) {
                $pb->add('--use')->add($use);
            }
        }

        if ($this->parser) {
            $pb->add('--parser')->add($this->parser);
        }

        if ($this->stringifier) {
            $pb->add('--stringifier')->add($this->stringifier);
        }

        if ($this->syntax) {
            $pb->add('--syntax')->add($this->syntax);
        }

        if ($this->config) {
            $pb->add('--config')->add($this->config);
        }

        $pb->add('--output')->add($output);

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $asset->setContent(file_get_contents($output));
        unlink($output);
    }
}
