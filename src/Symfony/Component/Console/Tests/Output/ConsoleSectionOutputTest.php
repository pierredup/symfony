<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Output;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class ConsoleSectionOutputTest extends TestCase
{
    private $stream;

    protected function setUp()
    {
        $this->stream = fopen('php://memory', 'r+', false);
    }

    protected function tearDown()
    {
        $this->stream = null;
    }

    public function testClearAll()
    {
        $output = new ConsoleSectionOutput($this->stream, OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());

        $output->writeln("Foo\nBar");
        $output->clear();

        rewind($output->getStream());
        $this->assertEquals("Foo\nBar".PHP_EOL.sprintf("\x1b[%dA", 2)."\x1b[0J", stream_get_contents($output->getStream()));
    }

    public function testClearNumberOfLines()
    {
        $output = new ConsoleSectionOutput($this->stream, OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());

        $output->writeln("Foo\nBar\nBaz\nFooBar");
        $output->clear(2);

        rewind($output->getStream());
        $this->assertEquals("Foo\nBar\nBaz\nFooBar".PHP_EOL.sprintf("\x1b[%dA", 2)."\x1b[0J", stream_get_contents($output->getStream()));
    }

    public function testOverwrite()
    {
        $output = new ConsoleSectionOutput($this->stream, OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());

        $output->writeln('Foo');
        $output->overwrite('Bar');

        rewind($output->getStream());
        $this->assertEquals('Foo'.PHP_EOL.$this->generateOutput('Bar').PHP_EOL, stream_get_contents($output->getStream()));
    }

    public function testOverwriteMultipleLines()
    {
        $output = new ConsoleSectionOutput($this->stream, OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());

        $output->writeln("Foo\nBar\nBaz");
        $output->overwrite('Bar');

        rewind($output->getStream());
        $this->assertEquals("Foo\nBar\nBaz".PHP_EOL.sprintf("\x1b[%dA", 3)."\x1b[0J".'Bar'.PHP_EOL, stream_get_contents($output->getStream()));
    }

    public function testAddingMultipleSections()
    {
        $output1 = new ConsoleSectionOutput($this->stream, OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());
        $output2 = new ConsoleSectionOutput($this->stream, OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());

        $this->assertTrue(self::readAttribute($output1, 'sections') > 1);
    }

    public function testMultipleSectionsOutput()
    {
        $output = new StreamOutput($this->stream);
        $output1 = new ConsoleSectionOutput($output->getStream(), OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());
        $output2 = new ConsoleSectionOutput($output->getStream(), OutputInterface::VERBOSITY_NORMAL, true, new OutputFormatter());

        $output1->writeln('Foo');
        $output2->writeln('Bar');

        $output1->overwrite('Baz');
        $output2->overwrite('Foobar');

        rewind($output->getStream());
        $this->assertEquals("Foo\nBar\n\x1b[2A\x1b[0JBar\n\x1b[1A\x1b[0JBaz\nBar\n\x1b[1A\x1b[0JFoobar".PHP_EOL, stream_get_contents($output->getStream()));
    }

    protected function generateOutput(string $expected = '')
    {
        $count = substr_count($expected, "\n") + 1;

        return sprintf("\x1b[%dA", $count)."\x1b[0J".$expected;
    }
}
