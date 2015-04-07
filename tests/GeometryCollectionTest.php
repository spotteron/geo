<?php

namespace Brick\Geo\Tests;

use Brick\Geo\Exception\GeometryException;
use Brick\Geo\GeometryCollection;
use Brick\Geo\LineString;
use Brick\Geo\Point;

/**
 * Unit tests for class GeometryCollection.
 */
class GeometryCollectionTest extends AbstractTestCase
{
    /**
     * @dataProvider providerNumGeometries
     *
     * @param string  $geometry      The WKT of the GeometryCollection to test.
     * @param integer $numGeometries The expected number of geometries.
     */
    public function testNumGeometries($geometry, $numGeometries)
    {
        $geometry = GeometryCollection::fromText($geometry);
        $this->assertSame($numGeometries, $geometry->numGeometries());
    }

    /**
     * @return array
     */
    public function providerNumGeometries()
    {
        return [
            ['GEOMETRYCOLLECTION EMPTY', 0],
            ['GEOMETRYCOLLECTION (POINT EMPTY)', 1],
            ['GEOMETRYCOLLECTION (POINT EMPTY, LINESTRING EMPTY)', 2],
        ];
    }

    /**
     * @dataProvider providerGeometryN
     *
     * @param string      $geometry  The WKT of the GeometryCollection to test.
     * @param integer     $n         The number of the geometry to return.
     * @param string|null $geometryN The WKT of the expected result, or NULL if an exception is expected.
     */
    public function testGeometryN($geometry, $n, $geometryN)
    {
        if ($geometryN === null) {
            $this->setExpectedException(GeometryException::class);
        }

        foreach ([0, 1] as $srid) {
            $g = GeometryCollection::fromText($geometry, $srid);
            $this->assertWktEquals($g->geometryN($n), $geometryN, $srid);
        }
    }

    /**
     * @return array
     */
    public function providerGeometryN()
    {
        return [
            ['GEOMETRYCOLLECTION EMPTY', 0, null],
            ['GEOMETRYCOLLECTION EMPTY', 1, null],
            ['GEOMETRYCOLLECTION (POINT EMPTY)', 0, null],
            ['GEOMETRYCOLLECTION (POINT EMPTY)', 1, 'POINT EMPTY'],
            ['GEOMETRYCOLLECTION (POINT EMPTY)', 2, null],
            ['GEOMETRYCOLLECTION (LINESTRING (1 2, 3 4), POINT (5 6))', 0, null],
            ['GEOMETRYCOLLECTION (LINESTRING (1 2, 3 4), POINT (5 6))', 1, 'LINESTRING (1 2, 3 4)'],
            ['GEOMETRYCOLLECTION (LINESTRING (1 2, 3 4), POINT (5 6))', 2, 'POINT (5 6)'],
            ['GEOMETRYCOLLECTION (LINESTRING (1 2, 3 4), POINT (5 6))', 3, null]
        ];
    }

    public function testGeometryType()
    {
        $this->assertSame('GeometryCollection', GeometryCollection::xy([])->geometryType());
    }

    /**
     * @dataProvider providerDimension
     *
     * @param string  $geometry  The WKT of the geometry to test.
     * @param integer $dimension The expected dimension.
     */
    public function testDimension($geometry, $dimension)
    {
        $geometry = GeometryCollection::fromText($geometry);
        $this->assertSame($dimension, $geometry->dimension());
    }

    /**
     * @return array
     */
    public function providerDimension()
    {
        return [
            ['GEOMETRYCOLLECTION EMPTY', 0],
            ['GEOMETRYCOLLECTION (POINT (1 1))', 0],
            ['GEOMETRYCOLLECTION (POINT (1 1), MULTILINESTRING EMPTY)', 1],
            ['GEOMETRYCOLLECTION (POLYGON EMPTY, LINESTRING (1 1, 2 2), POINT (3 3))', 2]
        ];
    }

    /**
     * Tests Countable and Traversable interfaces.
     */
    public function testInterfaces()
    {
        $point = Point::fromText('POINT (1 2)');
        $lineString = LineString::fromText('LINESTRING (1 2, 3 4)');

        $geometryCollection = GeometryCollection::xy([$point, $lineString]);

        $this->assertInstanceOf(\Countable::class, $geometryCollection);
        $this->assertSame(2, $geometryCollection->count());

        $this->assertInstanceOf(\Traversable::class, $geometryCollection);
        $this->assertSame([$point, $lineString], iterator_to_array($geometryCollection));
    }
}
