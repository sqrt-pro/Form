<?php

namespace SQRT\Form\Element;

use SQRT\Form\Element;

class Textarea extends Element
{
  public function render($attr = null)
  {
    return new \SQRT\Tag\Textarea($this->getInputName(), $this->getValue(), $this->prepareAttr($attr, true));
  }
}