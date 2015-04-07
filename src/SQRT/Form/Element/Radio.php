<?php

namespace SQRT\Form\Element;

use SQRT\Form;
use SQRT\Form\ElementWithOptions;
use SQRT\Tag\RadioListing;

class Radio extends ElementWithOptions
{
  function __construct($field, array $options = null, $name = null, Form $form = null)
  {
    parent::__construct($field, $name, $form);

    if ($options) {
      $this->setOptions($options);
    }
  }

  /**
   * TODO: $attr не используется
   * @return RadioListing
   */
  public function render($attr = null)
  {
    $t = new RadioListing($this->getInputName(), $this->getOptions(), $this->getValue());

    if ($this->getIgnoreOptionsKeys()) {
      $t->setIgnoreOptionsKeys(true);
    }

    return $t;
  }
}