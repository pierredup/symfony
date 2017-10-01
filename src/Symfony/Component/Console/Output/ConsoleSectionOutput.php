<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Terminal;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class ConsoleSectionOutput extends StreamOutput
{
    public $content;
    public $lines = 0;

    private $sectionReference;
    private $terminal;

    /**
     * @param resource                 $stream
     * @param OutputSectionReference   $sectionReference
     * @param int                      $verbosity
     * @param null                     $decorated
     * @param OutputFormatterInterface $formatter
     */
    public function __construct($stream, OutputSectionReference $sectionReference, $verbosity, $decorated, OutputFormatterInterface $formatter)
    {
        parent::__construct($stream, $verbosity, $decorated, $formatter);
        $this->sectionReference = $sectionReference;
        $this->sectionReference->addRef($this);
        $this->terminal = new Terminal();
    }

    /**
     * Clears previous output.
     *
     * @param int $lines Number of lines to clear. If null, then the entire output is cleared
     */
    public function clear($lines = null)
    {
        if (empty($this->content) || !$this->isDecorated()) {
            return;
        }

        $number = $lines ?: $this->lines;

        $buffer = $this->sectionReference->calculateBuffer($this);
        $buffer += $number;

        $this->content = '';
        $this->lines = $this->lines - $number;

        if ($buffer > 0) {
            $this->moveUp($buffer);
            $this->clearOutput();
        }

        $this->printOutput();
    }

    /**
     * Overwrites the output with a new message.
     *
     * @param string $message
     */
    public function overwrite($message)
    {
        $this->clear();
        $this->writeln($message, true);
    }

    /**
     * @param string $message
     * @param bool   $newline
     */
    protected function doWrite($message, $newline)
    {
        if (!$this->isDecorated()) {
            return parent::doWrite($message, $newline);
        }

        $newline = true;
        $buffer = $this->sectionReference->calculateBuffer($this);

        if ($buffer > 0) {
            $this->moveUp($buffer);
            $this->clearOutput();
        }

        $this->calculateLines($message, $newline);

        parent::doWrite($message, $newline);

        $this->printOutput();
    }

    private function calculateLines($messages, $newLine)
    {
        $total = 0;

        $prevLines = explode("\n", $messages);

        for ($i = 0; $i < count($prevLines); ++$i) {
            $total += ceil(Helper::strlen($prevLines[$i]) / $this->terminal->getWidth()) ?: 1;
        }

        $this->lines += $total;
        $this->content .= $messages;

        if ($newLine) {
            $this->content .= "\n";
        }

        return $total;
    }

    private function moveUp($n)
    {
        parent::doWrite(sprintf("\033[%dA", $n), false);
    }

    private function clearOutput()
    {
        parent::doWrite("\033[0J", false);
    }

    private function printOutput()
    {
        $output = $this->sectionReference->getOutput($this);

        if (!empty($output)) {
            parent::doWrite($output, false);
        }
    }
}
