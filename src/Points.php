<?php

namespace ClacyBuilders\Graphics;

use ClacyBuilders\Graphics\Point;
use ClacyBuilders\Graphics\Angle;

class Points implements \IteratorAggregate
{
	private $start;
	private $points;
	private $ignoreFirst;

	protected function __construct(Point $start = null, $ignoreFirst = true)
	{
		$this->points = [];
		$this->start = $start;
		$this->ignoreFirst = $ignoreFirst;
	}

	/**
	 * Adds a point.
	 *
	 * @param  Point  $point
	 * @return The copied point.
	 */
	protected function addPoint(Point $point)
	{
		return $this->points[] = $point->copy();
	}

	/**
	 * Adds some points.
	 *
	 * @param  Point[]  $points
	 */
	protected function addPoints($points)
	{
		foreach ($points as $point) {
			$this->points[] = $point;
		}
	}

	/**
	 * Calculates the points for a rectangle.
	 *
	 * @param  Point    $corner  The top left corner.
	 * @param  float    $width
	 * @param  float    $height
	 * @param  boolean  $ccw     Whether to list the points counterclockwise or not.
	 */
	public static function rectangle(Point $corner, $width, $height, $ccw = false)
	{
		$points = new Points($corner);
		$points->addPoint($corner);
		$points->addPoint($corner)->translateX($width);
		$points->addPoint($corner)->translate($width, $height);
		$points->addPoint($corner)->translateY($height);
		$points->reverseIfCcw($ccw);
		return $points;
	}

	/**
	 * Calculates the points for a regular polygon.
	 *
	 * @param  Point    $center
	 * @param  int      $n       Number of corners.
	 * @param  float    $radius
	 * @param  boolean  $ccw     Whether to list the points counterclockwise or not.
	 */
	public static function polygon(Point $center, $n, $radius, $ccw = false)
	{
		return self::star($center, $n, $radius, [], $ccw);
	}

	/**
	 * Calculates the Points for a regular star polygon.
	 *
	 * @param  Point          $center
	 * @param  int            $n          Number of corners of the underlying polygon.
	 * @param  float          $radius
	 * @param  float|float[]  $starRadii
	 * @param  boolean        $ccw        Whether to list the points counterclockwise or not.
	 */
	public static function star(Point $center, $n, $radius, $starRadii = [], $ccw = false)
	{
		$points = new Points($center);
		if (!is_array($starRadii)) {
			$starRadii = [$starRadii];
		}
		$radii = array_merge([$radius], $starRadii);
		$count = count($radii);
		$delta = deg2rad(360) / $n / $count;
		$angle = Angle::create(0);
		for ($i = 0; $i < $n; $i++) {
			foreach ($radii as $k => $radius) {
				$points->addPoint($center)->translateY(-$radius)->rotate($center, $angle);
				$angle->add($delta);
			}
		}
		$points->reverseIfCcw($ccw);
		return $points;
	}

	public static function rotated(Point $center, $n, $pointsArray, $ccw = false)
	{
		$points = new Points($center, false);
		$delta = deg2rad(360) / $n;
		$angle = Angle::create(0);
		for ($i = 0; $i < $n; $i++) {
			foreach ($pointsArray as $point) {
				$points->addPoint($point)->rotate($center, $angle);
			}
			$angle->add($delta);
		}
		$points->reverseIfCcw($ccw);
		return $points;
	}

	/**
	 * Calculates the points for a sector of a circle.
	 *
	 * @param  Point    $center
	 * @param  Angle    $start
	 * @param  Angle    $stop    Must be greater than <code>$start</code>.
	 * @param  float    $radius
	 * @param  boolean  $ccw     Whether to list the points counterclockwise or not.
	 */
	public static function sector(Point $center, Angle $start, Angle $stop, $radius, $ccw = false)
	{
		$points = new Points($center);
		$points->addPoint($center);
		$points->addPoint($center)->translateX($radius)->rotate($center, $start);
		$points->addPoint($center)->translateX($radius)->rotate($center, $stop);
		$points->reverseIfCcw($ccw);
		return $points;
	}

	/**
	 * Calculates the points for a sector of a ring.
	 *
	 * @param  Point    $center
	 * @param  Angle    $start
	 * @param  Angle    $stop         Must be greater than <code>$start</code>.
	 * @param  float    $radius
	 * @param  float    $innerRadius
	 * @param  boolean  $ccw          Whether to list the points counterclockwise or not.
	 */
	public static function ringSector(Point $center, Angle $start, Angle $stop,
			$radius, $innerRadius, $ccw = false)
	{
		$points = new Points($center, false);
		if ($ccw) { $swap = $start; $start = $stop; $stop = $swap; }
		$points->addPoint($center)->translateX($radius)->rotate($center, $start);
		$points->addPoint($center)->translateX($radius)->rotate($center, $stop);
		$points->addPoint($center)->translateX($innerRadius)->rotate($center, $stop);
		$points->addPoint($center)->translateX($innerRadius)->rotate($center, $start);
		return $points;
	}

	/**
	 * Calculates the points for a rounded rectangle.
	 *
	 * @param  Point    $corner  The top left corner.
	 * @param  float    $width
	 * @param  float    $height
	 * @param  float    $radius
	 * @param  boolean  $ccw     Whether to list the points counterclockwise or not.
	 */
	public static function roundedRectangle(Point $corner, $width, $height, $radius, $ccw = false)
	{
		$points = new Points($corner, false);
		$points->addPoint($corner)->translateX($width - $radius);
		$points->addPoint($corner)->translate($width, $radius);
		$points->addPoint($corner)->translate($width, $height - $radius);
		$points->addPoint($corner)->translate($width - $radius, $height);
		$points->addPoint($corner)->translate($radius, $height);
		$points->addPoint($corner)->translateY($height - $radius);
		$points->addPoint($corner)->translateY($radius);
		$points->addPoint($corner)->translateX($radius);
		$points->reverseIfCcw($ccw);
		return $points;
	}

	/**
	 * Rotates points.
	 *
	 * @param  Point  $center
	 * @param  Angle  $angle
	 */
	public function rotate(Point $center, Angle $angle)
	{
		foreach ($this->points as $point) {
			$point->rotate($center, $angle);
		}
		$this->start->rotate($center, $angle);
		return $this;
	}

	/**
	 * Scales points.
	 *
	 * @param  Point  $center
	 * @param  float  $factor
	 */
	public function scale(Point $center, $factor)
	{
		foreach ($this->points as $point) {
			$point->scale($center, $factor);
		}
		$this->start->scale($center, $factor);
		return $this;
	}

	/**
	 * Scales points along the X-axis.
	 *
	 * @param  Point  $center
	 * @param  float  $factor
	 */
	public function scaleX(Point $center, $factor)
	{
		foreach ($this->points as $point) {
			$point->scaleX($center, $factor);
		}
		$this->start->scaleX($center, $factor);
		$this->reverseIfCcw($factor < 0);
		return $this;
	}

	/**
	 * Scales points along the Y-axis.
	 *
	 * @param  Point  $center
	 * @param  float  $factor
	 */
	public function scaleY(Point $center, $factor)
	{
		foreach ($this->points as $point) {
			$point->scaleY($center, $factor);
		}
		$this->start->scaleX($center, $factor);
		$this->reverseIfCcw($factor < 0);
		return $this;
	}

	/**
	 * A skew transformation along the X-axis.
	 *
	 * @param  Point  $center
	 * @param  Angle  $angle
	 */
	public function skewX(Point $center, Angle $angle)
	{
		foreach ($this->points as $point) {
			$point->skewX($center, $angle);
		}
		$this->start->skewX($center, $angle);
		return $this;
	}

	/**
	 * A skew transformation along the Y-axis.
	 *
	 * @param  Point  $center
	 * @param  Angle  $angle
	 */
	public function skewY(Point $center, Angle $angle)
	{
		foreach ($this->points as $point) {
			$point->skewY($center, $angle);
		}
		$this->start->skewY($center, $angle);
		return $this;
	}

	/**
	 * Translates points.
	 *
	 * @param  float  $deltaX
	 * @param  float  $deltaY
	 */
	public function translate($deltaX, $deltaY)
	{
		foreach ($this->points as $point) {
			$point->translate($deltaX, $deltaY);
		}
		$this->start->translate($deltaX, $deltaY);
		return $this;
	}

	/**
	 * Translates points along the X-axis.
	 *
	 * @param  float  $deltaX
	 * @param  float  $deltaY
	 */
	public function translateX($deltaX)
	{
		foreach ($this->points as $point) {
			$point->translateX($deltaX);
		}
		$this->start->translateX($deltaX);
		return $this;
	}

	/**
	 * Translates points along the Y-axis.
	 *
	 * @param  float  $deltaX
	 * @param  float  $deltaY
	 */
	public function translateY($deltaY)
	{
		foreach ($this->points as $point) {
			$point->translateY($deltaY);
		}
		$this->start->translateY($deltaY);
		return $this;
	}

	public function copy()
	{
		$copy = new Points($this->start->copy(), $this->ignoreFirst);
		foreach ($this->points as $point) {
			$copy->addPoint($point->copy());
		}
		return $copy;
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->points);
	}

	public function __get($name)
	{
		return $this->{$name};
	}

	public function __toString()
	{
		$str = '';
		foreach ($this->points as $point) {
			$str .= $point->x . ',' . $point->y;
		}
		return $str;
	}

	private function reverseIfCcw($ccw)
	{
		if (!$ccw) return;
		$count = count($this->points);
		for ($i = 0 + $this->ignoreFirst, $k = $count - 1; $i < $k; $i++, $k--) {
			$swap = $this->points[$i];
			$this->points[$i] = $this->points[$k];
			$this->points[$k] = $swap;
		}
	}
}