<?php

class BirkmanGrid
{
    private $gridIm;
    private $gridCanvas;

    public function __construct($coreData)
    {
        $this->gridIm  = imagecreatefrompng(__DIR__ . '/../birkman-img/lifestyle_grid_base.png');
        if (!$this->gridIm)
        {
            throw new Exception("couldn't load base grid layer.");
        }

        $this->gridCanvas['0']['x'] = 166;
        $this->gridCanvas['0']['y'] = 137;
        $this->gridCanvas['100']['x'] = 646;
        $this->gridCanvas['100']['y'] = 616;

        $this->drawGrid($coreData);
    }

    public function __destruct()
    {
        imagedestroy($this->gridIm);
    }

    public function asPNG($toStream = NULL)
    {
        imagepng($this->gridIm, $toStream);
    }

    private function gridDrawIconAtXY($iconIm, $valX, $valY) {
        $xFactor = ($this->gridCanvas['100']['x'] - $this->gridCanvas['0']['x'])/100;
        $yFactor = ($this->gridCanvas['100']['y'] - $this->gridCanvas['0']['y'])/100;

        $iconW = imagesx($iconIm);
        $iconH = imagesy($iconIm);

        /**
         * birkman coordinate system is 0..100 with 0,0 at bottom-left
         * gd coordiante system is 0,0 at top-left
         *
         * Need to flip the Y coordinate.
         */
        $valY = 100 - $valY;

        $iconAtX = $this->gridCanvas['0']['x'] + ( $xFactor * $valX ) - ( $iconW/2 );
        $iconAtY = $this->gridCanvas['0']['y'] + ( $yFactor * $valY ) - ( $iconH/2 );

        $ok = imagecopy($this->gridIm, $iconIm, $iconAtX, $iconAtY, 0, 0, $iconW, $iconH);
    }

    private function drawGrid($coreData)
    {
        $interest = imagecreatefrompng(__DIR__ . '/../birkman-img/icon_interest.png');
        if (!$interest)
        {
            throw new Exception("Couldn't load interest icon.");
        }
        $usual = imagecreatefrompng(__DIR__ . '/../birkman-img/icon_usual.png');
        if (!$usual)
        {
            throw new Exception("Couldn't load usual icon.");
        }
        $need_stress = imagecreatefrompng(__DIR__ . '/../birkman-img/icon_need_stress.png');
        if (!$need_stress)
        {
            throw new Exception("Couldn't load need_stress icon.");
        }

        $gridData = $coreData['grid'];
        $this->gridDrawIconAtXY($interest, $gridData['interest_x'], $gridData['interest_y']);
        $this->gridDrawIconAtXY($usual, $gridData['usual_x'], $gridData['usual_y']);
        $this->gridDrawIconAtXY($need_stress, $gridData['need_x'], $gridData['need_y']);

        imagedestroy($interest);
        imagedestroy($usual);
        imagedestroy($need_stress);
    }
}
