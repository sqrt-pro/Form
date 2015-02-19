<?php

namespace SQRT\Form\Element;

use SQRT\Form;
use SQRT\Form\ElementWithOptions;
use SQRT\Tag\CheckboxListing;

class Checkbox extends ElementWithOptions
{
  protected $input_value = 1;

  /** @return \SQRT\Tag\Checkbox|CheckboxListing */
  public function render()
  {
    if ($this->getOptions()) {
      $tag = new CheckboxListing(
        $this->getInputName(),
        $this->getOptions(),
        $this->getValue()
      );

      if ($this->getIgnoreOptionsKeys()) {
        $tag->setIgnoreOptionsKeys(true);
      }
    } else {
      $tag = new \SQRT\Tag\Checkbox(
        $this->getInputName(),
        $this->getInputValue(),
        null,
        $this->getValue() == $this->getInputValue()
      );
    }

    return $tag;
  }

  /** Значение value у одиночного INPUT`a */
  public function getInputValue()
  {
    return $this->input_value;
  }

  /** Значение value у одиночного INPUT`a */
  public function setInputValue($input_value)
  {
    $this->input_value = $input_value;

    return $this;
  }

  public function setOptions(array $options, $add_filter = true, $ignore_keys = null)
  {
    parent::setOptions($options, $add_filter, $ignore_keys);

    $this->setMultipleChoiceAllowed();

    return $this;
  }
}