<?php
class RectanglePacker {
  public $root;
  public 
    $usedWidth,
    $usedHeight = 0;
  
  public function __construct($width,$height) {
    $this->root = new Node($width,$height);
  }

  public function findCoords($w, $h) {
    // perform the search
    $coords = $this->recursiveFindCoords($this->root, $w, $h);
    // if fitted then recalculate the used dimensions
    if ($coords) {
      if ($this->usedWidth < $coords['x'] + $w ) {
        $this->usedWidth = $coords['x'] + $w;
      }
      if ($this->usedHeight < $coords['y'] + $h ) {
        $this->usedHeight = $coords['y'] + $h;
      }
    }
    return $coords;
  }

  private function recursiveFindCoords(Node $node, $w, $h) {
    // if we are not at a leaf then go deeper
    if ($node->lft) {
      // check first the left branch if not found then go by the right
      $coords = $this->recursiveFindCoords($node->lft, $w, $h);
      return $coords ?: $this->recursiveFindCoords($node->rgt, $w, $h);
    } else {
      // if already used or it's too big then return
      if ($node->used || $w > $node->w || $h > $node->h )
        return null;
        
      // if it fits perfectly then use this gap
      if ($w == $node->w && $h == $node->h ) {
        $node->used = true;
        return array(
          'x' => $node->x, 
          'y' => $node->y
        );
      }
      
      // initialize the left and right leafs by clonning the current one
      $node->lft = clone $node;
      $node->rgt = clone $node;
      
      // checks if we partition in vertical or horizontal
      if ( $node->w - $w > $node->h - $h ) {
        $node->lft->w = $w;
        $node->rgt->x = $node->x + $w;
        $node->rgt->w = $node->w - $w;
      } else {
        $node->lft->h = $h;
        $node->rgt->y = $node->y + $h;
        $node->rgt->h = $node->h - $h;
      }
      
      return $this->recursiveFindCoords($node->lft, $w, $h );
    }

  }
}

class Node {
  public
    $x,
    $y,
    $w,
    $h = 0;
  public 
    $lft,
    $rgt,
    $used = false;

  public function __construct($width=0, $height=0) {
    $this->w = $width;
    $this->h = $height;
  }

  public function __clone() {
    $this->lft = $this->rgt = $this->used = false;
  }
}