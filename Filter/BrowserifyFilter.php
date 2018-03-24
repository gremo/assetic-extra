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
use Assetic\Filter\BaseNodeFilter;

/**
 * Runs assets through Browserify.
 *
 * @link http://browserify.org
 */
class BrowserifyFilter extends BaseNodeFilter
{
    private $browserifyBin;
    private $nodeBin;
    private $transforms;

    public function __construct($browserifyBin = '/usr/bin/browserify', $nodeBin = null)
    {
        $this->browserifyBin = $browserifyBin;
        $this->nodeBin = $nodeBin;

        $this->addNodePath(dirname(dirname(realpath($browserifyBin))));
    }

    /**
     * @param string|array $transforms
     */
    public function setTransforms($transforms)
    {
        $this->transforms = $transforms;
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
        $pb = $this->createProcessBuilder(
            $this->nodeBin
                ? array($this->nodeBin, $this->browserifyBin)
                : array($this->browserifyBin)
        );

        $input = realpath($asset->getSourceRoot().DIRECTORY_SEPARATOR.$asset->getSourcePath());
        $output = tempnam(sys_get_temp_dir(), 'output');

        $pb->add($input)->add('-o')->add($output);

        $proc = $pb->getProcess();

        // Process builder adds double quotes around arguments. Unfortunately browserify command will fail when passing
        // the transformer argument. Simply append the transform(s) as is.
        // https://github.com/symfony/symfony/issues/20527
        if (null !== $this->transforms) {
            foreach ((array) $this->transforms as $transform) {
                $proc->setCommandLine($proc->getCommandLine().' -t '.$transform);
            }
        }

        $code = $proc->run();

        if (0 !== $code) {
            if (file_exists($output)) {
                unlink($output);
            }

            if (127 === $code) {
                throw new \RuntimeException('Path to node executable could not be resolved.');
            }

            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        if (!file_exists($output)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $asset->setContent(file_get_contents($output));

        unlink($output);
    }
}
