<?php

namespace SQRT\Form\Element;

use SQRT\Form;
use SQRT\Form\ElementWithOptions;

class Select extends ElementWithOptions
{
  function __construct($field, array $options = null, $name = null, Form $form = null)
  {
    parent::__construct($field, $name, $form);

    if ($options) {
      $this->setOptions($options);
    }
  }
}