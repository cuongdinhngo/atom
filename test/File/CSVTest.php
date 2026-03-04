<?php

namespace Atom\Test\File;

use Atom\Test\TestCase;
use Atom\File\CSV;
use Atom\File\Exception\CsvException;

class CSVTest extends TestCase
{
    private $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        // Reset static state
        $ref = new \ReflectionClass(CSV::class);

        $prop = $ref->getProperty('checkEmpty');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $prop = $ref->getProperty('setNull');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $prop = $ref->getProperty('setHeader');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    private function createCsvFile(array $rows): array
    {
        $fp = fopen($this->tmpFile, 'w');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        return ['tmp_name' => $this->tmpFile];
    }

    // --- Read CSV ---
    // NOTE: CSV::parseCsv() has a bug where the $nullable parameter is not
    // captured in the closure scope (line 93). We use CSV::setNull() as a
    // workaround which sets the static property that IS accessible in the closure.

    public function testReadCsvReturnsRows()
    {
        $file = $this->createCsvFile([
            ['John', 'john@test.com'],
            ['Jane', 'jane@test.com'],
        ]);

        // Use parseCsv directly without nullable to avoid the $nullable closure bug
        $result = CSV::parseCsv($file, [], true, false, false);

        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0][0]);
        $this->assertEquals('jane@test.com', $result[1][1]);
    }

    // --- To Array ---

    public function testToArrayWithStandardHeader()
    {
        $file = $this->createCsvFile([
            ['name', 'email'],
            ['John', 'john@test.com'],
            ['Jane', 'jane@test.com'],
        ]);

        $header = ['name' => 'name', 'email' => 'email'];
        $result = CSV::parseCsv($file, $header, true, false, true);

        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]['name']);
        $this->assertEquals('jane@test.com', $result[1]['email']);
    }

    // --- Set Header ---

    public function testSetHeader()
    {
        $header = ['Name', 'Email'];
        CSV::setHeader($header);

        $ref = new \ReflectionClass(CSV::class);
        $prop = $ref->getProperty('setHeader');
        $prop->setAccessible(true);

        $this->assertEquals($header, $prop->getValue());
    }

    // --- Check Empty ---

    public function testCheckEmpty()
    {
        CSV::checkEmpty();

        $ref = new \ReflectionClass(CSV::class);
        $prop = $ref->getProperty('checkEmpty');
        $prop->setAccessible(true);

        $this->assertTrue($prop->getValue());
    }

    // --- Set Null ---

    public function testSetNull()
    {
        CSV::setNull();

        $ref = new \ReflectionClass(CSV::class);
        $prop = $ref->getProperty('setNull');
        $prop->setAccessible(true);

        $this->assertTrue($prop->getValue());
    }

    // --- Nullable via setNull() static method (workaround for closure bug) ---

    public function testReadWithNullableViaSetNull()
    {
        $file = $this->createCsvFile([
            ['John', ''],
        ]);

        CSV::setNull();
        $result = CSV::parseCsv($file, [], true, false, false);
        $this->assertNull($result[0][1]);
    }

    public function testReadWithoutNullable()
    {
        $file = $this->createCsvFile([
            ['John', ''],
        ]);

        $result = CSV::parseCsv($file, [], true, false, false);
        $this->assertEquals('', $result[0][1]);
    }

    // --- Invalid header throws ---

    public function testToArrayWithInvalidHeaderThrows()
    {
        $file = $this->createCsvFile([
            ['wrong', 'headers'],
            ['John', 'john@test.com'],
        ]);

        $header = ['name' => 'name', 'email' => 'email'];

        $this->expectException(CsvException::class);
        CSV::parseCsv($file, $header, true, false, true);
    }

    // --- Single row ---

    public function testReadSingleRow()
    {
        $file = $this->createCsvFile([
            ['single', 'row', 'data'],
        ]);

        $result = CSV::parseCsv($file, [], true, false, false);
        $this->assertCount(1, $result);
        $this->assertEquals(['single', 'row', 'data'], $result[0]);
    }

    // --- Empty file ---

    public function testReadEmptyFile()
    {
        file_put_contents($this->tmpFile, '');
        $file = ['tmp_name' => $this->tmpFile];

        $result = CSV::parseCsv($file, [], true, false, false);
        $this->assertEmpty($result);
    }
}
