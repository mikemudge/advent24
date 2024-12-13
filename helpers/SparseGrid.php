<?php

/**
 * A sparse grid can handle very large areas.
 * It relies on the fact that many rows/columns are repeats of previous ones.
 */
class SparseGrid extends Grid {
    private array $xs, $ys;
    private array $mapX, $mapY;

    public function __construct($xs, $ys) {
        // X's and Y's should be unique and in order.
        $this->xs = array_unique($xs);
        $this->ys = array_unique($ys);
        sort($this->xs);
        sort($this->ys);
        $this->mapX = array_flip($this->xs);
        $this->mapY = array_flip($this->ys);

        $data = [];
        for ($y = 0; $y < count($this->ys) + 1; $y++) {
            $data[] = str_repeat('.', count($this->xs) + 1);
        }

        parent::__construct($data);
    }

    public function addBlock($x, $y, $x2, $y2, $key) {
        // Need to lookup in mapping where the locations are?
        $mx1 = $this->getXMap($x);
        $mx2 = $this->getXMap($x2);
        $my1 = $this->getYMap($y);
        $my2 = $this->getYMap($y2);
        for ($y = $my1; $y < $my2; $y++) {
            for ($x = $mx1; $x < $mx2; $x++) {
                $this->get($x, $y)->setKey($key);
            }
        }
    }

    private function getXMap($x) {
        if (array_key_exists($x, $this->mapX)) {
            return $this->mapX[$x];
        }
        throw new RuntimeException("X: $x is not aligned");
    }

    private function getYMap(int $y) {
        if (array_key_exists($y, $this->mapY)) {
            return $this->mapY[$y];
        }
        throw new RuntimeException("Y: $y is not aligned");
    }

    /**
     * Sparse Grid count is very similar to grid count, but we need to use the area of each region.
     */
    public function count(string $string): int {
        $cnt = 0;
        for ($y = 0; $y < $this->getHeight() - 1; $y++) {
            for ($x = 0; $x < $this->getWidth() - 1; $x++) {
                if ($this->get($x, $y)->getKey() == $string) {
                    $dx = $this->ys[$y + 1] - $this->ys[$y];
                    $dy = $this->xs[$x + 1] - $this->xs[$x];
                    $area = $dx * $dy;
                    $cnt += $area;
                }
            }
        }
        return $cnt;
    }
}