<?php

declare(strict_types=1);

namespace Brick\Geo\Tests;

use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\IO\WKBByteOrder;
use Brick\Geo\IO\WKBTools;
use Brick\Geo\Geometry;
use Brick\Geo\Point;

/**
 * Unit tests for Geometry.
 */
class GeometryTest extends AbstractTestCase
{
    /**
     * @dataProvider providerTextBinary
     *
     * @param string $text The WKT of the geometry to test.
     */
    public function testFromAsText(string $text) : void
    {
        $geometry = Geometry::fromText($text);

        self::assertSame($text, $geometry->asText());
        self::assertSame(0, $geometry->SRID());

        $geometry = Geometry::fromText($text, 4326);

        self::assertSame($text, $geometry->asText());
        self::assertSame(4326, $geometry->SRID());
    }

    public function testFromTextOnWrongSubclassThrowsException() : void
    {
        $this->expectException(UnexpectedGeometryException::class);
        Point::fromText('LINESTRING (1 2, 3 4)');
    }

    /**
     * @dataProvider providerTextBinary
     *
     * @param string $text               The WKT of the geometry under test.
     * @param string $bigEndianBinary    The big endian WKB of the geometry under test.
     * @param string $littleEndianBinary The little endian WKB of the geometry under test.
     */
    public function testFromBinary(string $text, string $bigEndianBinary, string $littleEndianBinary) : void
    {
        foreach ([$bigEndianBinary, $littleEndianBinary] as $binary) {
            $geometry = Geometry::fromBinary(hex2bin($binary));

            self::assertSame($text, $geometry->asText());
            self::assertSame(0, $geometry->SRID());

            $geometry = Geometry::fromBinary(hex2bin($binary), 4326);

            self::assertSame($text, $geometry->asText());
            self::assertSame(4326, $geometry->SRID());
        }
    }

    /**
     * @dataProvider providerTextBinary
     *
     * @param string $text               The WKT of the geometry under test.
     * @param string $bigEndianBinary    The big endian WKB of the geometry under test.
     * @param string $littleEndianBinary The little endian WKB of the geometry under test.
     */
    public function testAsBinary(string $text, string $bigEndianBinary, string $littleEndianBinary) : void
    {
        $machineByteOrder = WKBTools::getMachineByteOrder();

        $binary = match ($machineByteOrder) {
            WKBByteOrder::BIG_ENDIAN => $bigEndianBinary,
            WKBByteOrder::LITTLE_ENDIAN => $littleEndianBinary,
        };

        self::assertSame($binary, bin2hex(Geometry::fromText($text)->asBinary()));
    }

    /**
     * This is a very succinct series of tests for text/binary import/export methods.
     * Exhaustive tests for WKT and WKB are in the IO directory.
     */
    public function providerTextBinary() : array
    {
        return [
            ['POINT (1 2)', '00000000013ff00000000000004000000000000000', '0101000000000000000000f03f0000000000000040'],
            ['LINESTRING Z EMPTY', '00000003ea00000000', '01ea03000000000000'],
            ['MULTIPOLYGON M EMPTY', '00000007d600000000', '01d607000000000000'],
            ['POLYHEDRALSURFACE ZM EMPTY', '0000000bc700000000', '01c70b000000000000'],
        ];
    }

    /**
     * @dataProvider providerDimension
     */
    public function testDimension(string $geometry, int $dimension) : void
    {
        $geometry = Geometry::fromText($geometry);
        self::assertSame($dimension, $geometry->dimension());
    }

    public function providerDimension() : array
    {
        return [
            ['POINT EMPTY', 0],
            ['POINT Z EMPTY', 0],
            ['POINT M EMPTY', 0],
            ['POINT ZM EMPTY', 0],
            ['POINT (1 2)', 0],
            ['POINT Z (1 2 3)', 0],
            ['POINT M (1 2 3)', 0],
            ['POINT ZM (1 2 3 4)', 0],
            ['LINESTRING EMPTY', 1],
            ['LINESTRING Z EMPTY', 1],
            ['LINESTRING M EMPTY', 1],
            ['LINESTRING ZM EMPTY', 1],
            ['LINESTRING (1 2, 3 4)', 1],
            ['LINESTRING Z (1 2 3, 4 5 6)', 1],
            ['LINESTRING M (2 3 4, 5 6 7)', 1],
            ['LINESTRING ZM (1 2 3 4, 5 6 7 8)', 1],
            ['POLYGON EMPTY', 2],
            ['POLYGON Z EMPTY', 2],
            ['POLYGON M EMPTY', 2],
            ['POLYGON ZM EMPTY', 2],
            ['POLYGON ((0 0, 0 1, 1 1, 1 0, 0 0))', 2],
            ['POLYGON Z ((0 0 0, 0 1 0, 1 1 0, 1 0 0, 0 0 0))', 2],
            ['POLYGON M ((0 0 1, 0 1 2, 1 1 3, 1 0 4, 0 0 1))', 2],
            ['POLYGON ZM ((0 0 0 1, 0 1 0 2, 1 1 0 3, 1 0 0 4, 0 0 0 1))', 2],
            ['MULTIPOINT EMPTY', 0],
            ['MULTIPOINT Z EMPTY', 0],
            ['MULTIPOINT M EMPTY', 0],
            ['MULTIPOINT ZM EMPTY', 0],
            ['MULTILINESTRING EMPTY', 1],
            ['MULTILINESTRING Z EMPTY', 1],
            ['MULTILINESTRING M EMPTY', 1],
            ['MULTILINESTRING ZM EMPTY', 1],
            ['MULTIPOLYGON EMPTY', 2],
            ['MULTIPOLYGON Z EMPTY', 2],
            ['MULTIPOLYGON M EMPTY', 2],
            ['MULTIPOLYGON ZM EMPTY', 2],
            ['GEOMETRYCOLLECTION EMPTY', 0],
            ['GEOMETRYCOLLECTION Z EMPTY', 0],
            ['GEOMETRYCOLLECTION M EMPTY', 0],
            ['GEOMETRYCOLLECTION ZM EMPTY', 0],
            ['GEOMETRYCOLLECTION (POINT (1 1))', 0],
            ['GEOMETRYCOLLECTION (POINT (1 1), MULTILINESTRING EMPTY)', 1],
            ['GEOMETRYCOLLECTION (POLYGON EMPTY, LINESTRING (1 1, 2 2), POINT (3 3))', 2],
            ['GEOMETRYCOLLECTION Z (LINESTRING Z (1 2 3, 4 5 6))', 1],
            ['TRIANGLE ((0 0, 0 1, 1 0, 0 0))', 2],
        ];
    }

    /**
     * @dataProvider providerCoordinateDimension
     *
     * @param string $geometry            The WKT of the geometry to test.
     * @param int    $coordinateDimension The expected coordinate dimension.
     */
    public function testCoordinateDimension(string $geometry, int $coordinateDimension) : void
    {
        self::assertSame($coordinateDimension, Geometry::fromText($geometry)->coordinateDimension());
    }

    public function providerCoordinateDimension() : array
    {
        return [
            ['POINT (1 2)', 2],
            ['POINT Z (1 2 3)', 3],
            ['POINT M (1 2 3)', 3],
            ['POINT ZM (1 2 3 4)', 4],
            ['LINESTRING (1 2, 3 4)', 2],
            ['LINESTRING Z (1 2 3, 4 5 6)', 3],
            ['LINESTRING M (1 2 3, 4 5 6)', 3],
            ['LINESTRING ZM (1 2 3 4, 5 6 7 8)', 4],
        ];
    }

    /**
     * @dataProvider providerSpatialDimension
     *
     * @param string $geometry         The WKT of the geometry to test.
     * @param int    $spatialDimension The expected spatial dimension.
     */
    public function testSpatialDimension(string $geometry, int $spatialDimension) : void
    {
        self::assertSame($spatialDimension, Geometry::fromText($geometry)->spatialDimension());
    }

    public function providerSpatialDimension() : array
    {
        return [
            ['POINT (1 2)', 2],
            ['POINT Z (1 2 3)', 3],
            ['POINT M (1 2 3)', 2],
            ['POINT ZM (1 2 3 4)', 3],
            ['LINESTRING (1 2, 3 4)', 2],
            ['LINESTRING Z (1 2 3, 4 5 6)', 3],
            ['LINESTRING M (1 2 3, 4 5 6)', 2],
            ['LINESTRING ZM (1 2 3 4, 5 6 7 8)', 3],
        ];
    }

    /**
     * @dataProvider providerGeometryType
     *
     * @param string $geometry     The WKT of the geometry to test.
     * @param string $geometryType The expected geometry type.
     */
    public function testGeometryType(string $geometry, string $geometryType) : void
    {
        $geometry = Geometry::fromText($geometry);
        self::assertSame($geometryType, $geometry->geometryType());
    }

    public function providerGeometryType() : array
    {
        return [
            ['POINT EMPTY', 'Point'],
            ['POINT Z EMPTY', 'Point'],
            ['POINT M EMPTY', 'Point'],
            ['POINT ZM EMPTY', 'Point'],
            ['POINT (1 2)', 'Point'],
            ['POINT Z (1 2 3)', 'Point'],
            ['POINT M (1 2 3)', 'Point'],
            ['POINT ZM (1 2 3 4)' , 'Point'],
            ['LINESTRING EMPTY', 'LineString'],
            ['LINESTRING Z EMPTY', 'LineString'],
            ['LINESTRING M EMPTY', 'LineString'],
            ['LINESTRING ZM EMPTY', 'LineString'],
            ['LINESTRING (1 2, 3 4)', 'LineString'],
            ['LINESTRING Z (1 2 3, 4 5 6)', 'LineString'],
            ['LINESTRING M (1 2 3, 4 5 6)', 'LineString'],
            ['LINESTRING ZM (1 2 3 4, 5 6 7 8)', 'LineString'],
            ['POLYGON EMPTY', 'Polygon'],
            ['POLYGON Z EMPTY', 'Polygon'],
            ['POLYGON M EMPTY', 'Polygon'],
            ['POLYGON ZM EMPTY', 'Polygon'],
            ['POLYGON ((0 0, 0 1, 1 1, 1 0, 0 0))', 'Polygon'],
            ['POLYGON Z ((0 0 0, 0 1 0, 1 1 0, 1 0 0, 0 0 0))', 'Polygon'],
            ['POLYGON M ((0 0 0, 0 1 0, 1 1 0, 1 0 0, 0 0 0))', 'Polygon'],
            ['POLYGON ZM ((0 0 0 0, 0 1 0 0, 1 1 0 0, 1 0 0 0, 0 0 0 0))', 'Polygon'],
            ['MULTIPOINT EMPTY', 'MultiPoint'],
            ['MULTIPOINT Z EMPTY', 'MultiPoint'],
            ['MULTIPOINT M EMPTY', 'MultiPoint'],
            ['MULTIPOINT ZM EMPTY', 'MultiPoint'],
            ['MULTILINESTRING EMPTY', 'MultiLineString'],
            ['MULTILINESTRING Z EMPTY', 'MultiLineString'],
            ['MULTILINESTRING M EMPTY', 'MultiLineString'],
            ['MULTILINESTRING ZM EMPTY', 'MultiLineString'],
            ['MULTIPOLYGON EMPTY', 'MultiPolygon'],
            ['MULTIPOLYGON Z EMPTY', 'MultiPolygon'],
            ['MULTIPOLYGON M EMPTY', 'MultiPolygon'],
            ['MULTIPOLYGON ZM EMPTY', 'MultiPolygon'],
            ['GEOMETRYCOLLECTION EMPTY', 'GeometryCollection'],
            ['GEOMETRYCOLLECTION Z EMPTY', 'GeometryCollection'],
            ['GEOMETRYCOLLECTION M EMPTY', 'GeometryCollection'],
            ['GEOMETRYCOLLECTION ZM EMPTY', 'GeometryCollection'],
            ['TRIANGLE EMPTY', 'Triangle'],
            ['TRIANGLE Z EMPTY', 'Triangle'],
            ['TRIANGLE M EMPTY', 'Triangle'],
            ['TRIANGLE ZM EMPTY', 'Triangle'],
            ['POLYHEDRALSURFACE EMPTY', 'PolyhedralSurface'],
            ['POLYHEDRALSURFACE Z EMPTY', 'PolyhedralSurface'],
            ['POLYHEDRALSURFACE M EMPTY', 'PolyhedralSurface'],
            ['POLYHEDRALSURFACE ZM EMPTY', 'PolyhedralSurface'],
            ['TIN EMPTY', 'TIN'],
            ['TIN Z EMPTY', 'TIN'],
            ['TIN M EMPTY', 'TIN'],
            ['TIN ZM EMPTY', 'TIN'],
        ];
    }

    /**
     * @dataProvider providerSRID
     */
    public function testSRID(int $srid) : void
    {
        self::assertSame($srid, Geometry::fromText('POINT EMPTY', $srid)->SRID());
    }

    public function providerSRID() : array
    {
        return [
            [4326],
            [4327],
        ];
    }

    /**
     * @dataProvider providerIsEmpty
     *
     * @param string $geometry The WKT of the geometry to test.
     * @param bool   $isEmpty  Whether the geometry is empty.
     */
    public function testIsEmpty(string $geometry, bool $isEmpty) : void
    {
        self::assertSame($isEmpty, Geometry::fromText($geometry)->isEmpty());
    }

    public function providerIsEmpty() : array
    {
        return [
            ['POINT EMPTY', true],
            ['POINT (1 2)', false],
            ['LINESTRING EMPTY', true],
            ['LINESTRING (0 0, 1 1)', false],
            ['GEOMETRYCOLLECTION EMPTY', true],
            ['GEOMETRYCOLLECTION (POINT (1 2))', false],
            ['GEOMETRYCOLLECTION (POINT EMPTY, LINESTRING EMPTY)', true],
        ];
    }

    /**
     * @dataProvider providerDimensionality
     *
     * @param string $geometry   The geometry to test.
     * @param bool   $is3D       Whether the geometry has a Z coordinate.
     * @param bool   $isMeasured Whether the geometry has a M coordinate.
     */
    public function testDimensionality(string $geometry, bool $is3D, bool $isMeasured) : void
    {
        self::assertSame($is3D, Geometry::fromText($geometry)->is3D());
        self::assertSame($isMeasured, Geometry::fromText($geometry)->isMeasured());
    }

    public function providerDimensionality() : array
    {
        return [
            ['POINT EMPTY', false, false],
            ['POINT Z EMPTY', true, false],
            ['POINT M EMPTY', false, true],
            ['POINT ZM EMPTY', true, true],
            ['LINESTRING (1 2, 3 4)', false, false],
            ['LINESTRING Z (1 2 3, 4 5 6)', true, false],
            ['LINESTRING M (1 2 3, 4 5 6)', false, true],
            ['LINESTRING ZM (1 2 3 4, 5 6 7 8)', true, true],
        ];
    }

    /**
     * @dataProvider providerWithSRID
     */
    public function testWithSRID(string $wkt): void
    {
        $geometry = Geometry::fromText($wkt)->withSRID(4326);

        $this->assertSRID(4326, $geometry);
        self::assertSame($wkt, $geometry->asText());
    }

    private function assertSRID(int $expectedSRID, Geometry $geometry): void
    {
        self::assertSame($expectedSRID, $geometry->SRID());

        foreach ($geometry as $value) {
            if ($value instanceof Geometry) {
                $this->assertSRID($expectedSRID, $value);
            }
        }
    }

    public function providerWithSRID(): array
    {
        return [
            ['POINT (1 2)'],
            ['POINT Z (1 2 3)'],
            ['POINT M (1 2 3)'],
            ['POINT ZM (1 2 3 4)'],
            ['LINESTRING (1 2, 3 4)'],
            ['LINESTRING Z (1 2 3, 4 5 6)'],
            ['LINESTRING M (1 2 3, 4 5 6)'],
            ['LINESTRING ZM (1 2 3 4, 5 6 7 8)'],
            ['POLYGON ((1 2, 3 4, 5 6, 1 2))'],
            ['POLYGON Z ((1 2 3, 4 5 6, 7 8 9, 1 2 3))'],
            ['POLYGON M ((1 2 3, 4 5 6, 7 8 9, 1 2 3))'],
            ['POLYGON ZM ((1 2 3 4, 5 6 7 8, 9 10 11 12, 1 2 3 4))'],
            ['MULTIPOINT (1 2, 3 4)'],
            ['MULTIPOINT Z (1 2 3, 4 5 6)'],
            ['MULTIPOINT M (1 2 3, 4 5 6)'],
            ['MULTIPOINT ZM (1 2 3 4, 5 6 7 8)'],
            ['MULTILINESTRING ((1 2, 3 4), (5 6, 7 8))'],
            ['MULTILINESTRING Z ((1 2 3, 4 5 6), (7 8 9, 10 11 12))'],
            ['MULTILINESTRING M ((1 2 3, 4 5 6), (7 8 9, 10 11 12))'],
            ['MULTILINESTRING ZM ((1 2 3 4, 5 6 7 8), (9 10 11 12, 13 14 15 16))'],
            ['MULTIPOLYGON (((1 2, 3 4, 5 6, 1 2)), ((7 8, 9 10, 11 12, 7 8)))'],
            ['MULTIPOLYGON Z (((1 2 3, 4 5 6, 7 8 9, 1 2 3)), ((10 11 12, 13 14 15, 16 17 18, 10 11 12)))'],
            ['MULTIPOLYGON M (((1 2 3, 4 5 6, 7 8 9, 1 2 3)), ((10 11 12, 13 14 15, 16 17 18, 10 11 12)))'],
            ['MULTIPOLYGON ZM (((1 2 3 4, 5 6 7 8, 9 10 11 12, 1 2 3 4)), ((13 14 15 16, 17 18 19 20, 21 22 23 24, 13 14 15 16)))'],
            ['GEOMETRYCOLLECTION (POINT (1 2), LINESTRING (3 4, 5 6))'],
            ['GEOMETRYCOLLECTION Z (POINT Z (1 2 3), LINESTRING Z (4 5 6, 7 8 9))'],
            ['GEOMETRYCOLLECTION M (POINT M (1 2 3), LINESTRING M (4 5 6, 7 8 9))'],
            ['GEOMETRYCOLLECTION ZM (POINT ZM (1 2 3 4), LINESTRING ZM (5 6 7 8, 9 10 11 12))'],
        ];
    }

    /**
     * @dataProvider providerToArray
     *
     * @param string $geometry The WKT of the geometry to test.
     * @param array  $array    The expected result array.
     */
    public function testToArray(string $geometry, array $array) : void
    {
        $this->castToFloat($array);
        self::assertSame($array, Geometry::fromText($geometry)->toArray());
    }

    public function providerToArray() : array
    {
        return [
            ['POINT EMPTY', []],
            ['POINT Z EMPTY', []],
            ['POINT M EMPTY', []],
            ['POINT ZM EMPTY', []],
            ['POINT (1 2)', [1, 2]],
            ['POINT Z (1 2 3)', [1, 2, 3]],
            ['POINT M (2 3 4)', [2, 3, 4]],
            ['POINT ZM (1 2 3 4)', [1, 2, 3, 4]],

            ['LINESTRING EMPTY', []],
            ['LINESTRING Z EMPTY', []],
            ['LINESTRING M EMPTY', []],
            ['LINESTRING ZM EMPTY', []],
            ['LINESTRING (1 2, 3 4, 5 6, 7 8)', [[1, 2], [3, 4], [5, 6], [7, 8]]],
            ['LINESTRING Z (1 2 3, 4 5 6, 7 8 9)', [[1, 2, 3], [4, 5, 6], [7, 8, 9]]],
            ['LINESTRING M (1 2 3, 4 5 6, 7 8 9)', [[1, 2, 3], [4, 5 , 6], [7, 8, 9]]],
            ['LINESTRING ZM (1 2 3 4, 5 6 7 8)', [[1, 2, 3, 4], [5, 6, 7, 8]]],
        ];
    }

    /**
     * @dataProvider providerIsIdenticalTo
     */
    public function testIsIdenticalTo(string $wkt1, string $wkt2, bool $identical) : void
    {
        $geometry1 = Geometry::fromText($wkt1);
        $geometry2 = Geometry::fromText($wkt2);

        self::assertSame($identical, $geometry1->isIdenticalTo($geometry2));
    }

    /**
     * @dataProvider providerIsIdenticalTo
     */
    public function testIsIdenticalToDifferentSRIDs(string $wkt1, string $wkt2) : void
    {
        $geometry1 = Geometry::fromText($wkt1, 1);
        $geometry2 = Geometry::fromText($wkt2, 2);

        self::assertFalse($geometry1->isIdenticalTo($geometry2));
    }

    public function providerIsIdenticalTo() : array
    {
        return [
            ['POINT EMPTY', 'POINT EMPTY', true],
            ['POINT EMPTY', 'POINT Z EMPTY', false],
            ['POINT EMPTY', 'POINT (1 1)', false],
            ['POINT (1 1)', 'POINT (1 1)', true],
            ['POINT (1 1)', 'POINT (1 2)', false],
            ['POINT (1 1)', 'POINT (1 1.000001)', false],
            ['POINT (1 1)', 'POINT Z (1 1 1)', false],
            ['POINT Z (1 2 3)', 'POINT M (1 2 3)', false],
            ['POINT (1 1)', 'MULTIPOINT (1 1)', false],
            ['POINT (1 1)', 'GEOMETRYCOLLECTION(POINT (1 1))', false],
            ['MULTIPOINT (1 1)', 'MULTIPOINT (1 1)', true],
            ['MULTIPOINT (1 1)', 'MULTIPOINT (2 3)', false],
            ['MULTIPOINT (1 1)', 'MULTIPOINT (1 1, 2 3)', false],
            ['MULTIPOINT (1 2, 2 3)', 'MULTIPOINT (2 3, 1 2)', false],
        ];
    }
}
