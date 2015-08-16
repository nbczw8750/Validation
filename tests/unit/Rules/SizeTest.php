<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Respect\Validation\Rules;

use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use SplFileInfo;

/**
 * @author Henrique Moody <henriquemoody@gmail.com>
 * @group  rule
 * @covers Respect\Validation\Rules\Size
 * @covers Respect\Validation\Exceptions\SizeException
 */
class SizeTest extends PHPUnit_Framework_TestCase
{
    public function validSizeProvider()
    {
        return array(
            array(42, 42),
            array('1b', 1),
            array('1kb', 1024),
            array('1mb', 1048576),
            array('1gb', 1073741824),
            array('1tb', 1099511627776),
            array('1pb', 1125899906842624),
            array('1eb', 1152921504606846976),
            array('1zb', 1.1805916207174113E+21),
            array('1yb', 1.2089258196146292E+24),
        );
    }

    public function validFileProvider()
    {
        $root = vfsStream::setup();

        $file2Kb = vfsStream::newFile('2kb.txt')->withContent(LargeFileContent::withKilobytes(2))->at($root);
        $file2Mb = vfsStream::newFile('2mb.txt')->withContent(LargeFileContent::withMegabytes(2))->at($root);

        return array(
            // Valid data
            array($file2Kb->url(), '1kb', null, true),
            array($file2Kb->url(), '2kb', null, true),
            array($file2Kb->url(), null, '2kb', true),
            array($file2Kb->url(), null, '3kb', true),
            array($file2Kb->url(), '1kb', '3kb', true),
            array($file2Mb->url(), '1mb', null, true),
            array($file2Mb->url(), '2mb', null, true),
            array($file2Mb->url(), null, '2mb', true),
            array($file2Mb->url(), null, '3mb', true),
            array($file2Mb->url(), '1mb', '3mb', true),
            // Invalid data
            array($file2Kb->url(), '3kb', null, false),
            array($file2Kb->url(), null, '1kb', false),
            array($file2Kb->url(), '1kb', '1.5kb', false),
            array($file2Mb->url(), '2.5mb', null, false),
            array($file2Mb->url(), '3gb', null, false),
            array($file2Mb->url(), null, '1b', false),
            array($file2Mb->url(), '1pb', '3pb', false),
        );
    }

    /**
     * @dataProvider validSizeProvider
     */
    public function testShouldConvertUnitonConstructor($size, $bytes)
    {
        $rule = new Size($size);

        $this->assertEquals($bytes, $rule->minValue);
    }

    /**
     * @expectedException Respect\Validation\Exceptions\ComponentException
     * @expectedExceptionMessage "42jb" is not a recognized file size
     */
    public function testShouldThrowsAnExceptionWhenSizeIsNotValid()
    {
        new Size('42jb');
    }

    /**
     * @dataProvider validFileProvider
     */
    public function testShouldValidateFile($filename, $minSize, $maxSize, $expectedValidation)
    {
        $rule = new Size($minSize, $maxSize);

        $this->assertEquals($expectedValidation, $rule->validate($filename));
    }

    public function testShouldValidateSplFileInfo()
    {
        $root = vfsStream::setup();
        $file1Gb = vfsStream::newFile('1gb.txt')->withContent(LargeFileContent::withGigabytes(1))->at($root);
        $file1GbObject = new SplFileInfo($file1Gb->url());

        $rule = new Size('1MB', '2GB');

        $this->assertTrue($rule->validate($file1GbObject));
    }

    /**
     * @expectedException Respect\Validation\Exceptions\SizeException
     * @expectedExceptionMessage "vfs://root/1gb.txt" must be greater than 2pb
     */
    public function testShouldThrowsSizeExceptionWhenAsserting()
    {
        $root = vfsStream::setup();
        $file1Gb = vfsStream::newFile('1gb.txt')->withContent(LargeFileContent::withGigabytes(1))->at($root);

        $rule = new Size('2pb');
        $rule->assert($file1Gb->url());
    }
}
