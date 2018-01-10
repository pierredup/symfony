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
 * @author Gabriel Ostrolucký <gabriel.ostrolucky@gmail.com>
 */
class ConsoleSectionOutput extends StreamOutput
{
    private $content = '';

    private $lines = 0;

    private $sections;

    private $terminal;

    public function __construct($stream, array &$sections, $verbosity, $decorated, OutputFormatterInterface $formatter)
    {
        parent::__construct($stream, $verbosity, $decorated, $formatter);
        array_unshift($sections, $this);
        $this->sections = &$sections;
        $this->terminal = new Terminal();
    }

    /**
     * Clears previous output for this section.
     *
     * @param int $lines Number of lines to clear. If null, then the entire output of this section is cleared
     */
    public function clear(int $lines = null)
    {
        if (empty($this->content) || !$this->isDecorated()) {
            return;
        }

        if (!$lines) {
            $lines = $this->lines;
        }

        $this->content = '';
        $this->lines -= $lines;

        parent::doWrite($this->popStreamContentUntilCurrentSection($lines), false);
    }

    /**
     * Overwrites the previous output with a new message.
     *
     * @param string $message
     */
    public function overwrite($message)
    {
        $this->clear();
        $this->writeln($message);
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        if (!$this->isDecorated()) {
            return parent::doWrite($message, $newline);
        }

        $erasedContent = $this->popStreamContentUntilCurrentSection();

        foreach (explode(PHP_EOL, $message) as $lineContent) {
            $this->lines += ceil($this->getDisplayLength($lineContent) / $this->terminal->getWidth()) ?: 1;
        }

        $this->content .= $message.PHP_EOL;

        parent::doWrite($message, true);
        //$this->column = $this->getCursorPosition();
        parent::doWrite($erasedContent, false);
    }

    /**
     * At initial stage, cursor is at the end of stream output. This method makes cursor crawl upwards until it hits
     * current section. Then it erases content it crawled through. Optionally, it erases part of current section too.
     *
     * @param int $numberOfLinesToClearFromCurrentSection
     *
     * @return string
     */
    private function popStreamContentUntilCurrentSection(int $numberOfLinesToClearFromCurrentSection = 0): string
    {
        $numberOfLinesToClear = $numberOfLinesToClearFromCurrentSection;
        $erasedContent = array();

        foreach ($this->sections as $section) {
            if ($section === $this) {
                break;
            }

            $numberOfLinesToClear += $section->lines;
            $erasedContent[] = $section->content;
        }

        if ($numberOfLinesToClear > 0) {
            // Move cursor up n lines
            parent::doWrite(sprintf("\x1b[%dA", $numberOfLinesToClear), false);
            // erase to end of screen
            parent::doWrite("\x1b[0J", false);
        }

        return implode('', array_reverse($erasedContent));
    }

    private function getDisplayLength(string $text): string
    {
        return Helper::strlenWithoutDecoration($this->getFormatter(), str_replace("\t", '        ', $text));
    }
}
