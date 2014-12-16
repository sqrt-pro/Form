<?php

namespace SQRT\Form;

use SQRT\Tag\Select;
use SQRT\TagWithOptions;

abstract class ElementWithOptions extends Element
{
  protected $options;
  protected $ignore_options_keys;

  /** @return TagWithOptions */
  public function render($attr = null)
  {
    $opts = $this->getOptions();

    if ($ph = $this->getPlaceholder()) {
      $opts = array('' => $ph) + (!empty($opts) ? $opts : array());
    }

    $t = new Select($this->getInputName(), $opts, $this->getValue(), $this->prepareAttr($attr));

    if ($this->getIgnoreOptionsKeys()) {
      $t->setIgnoreOptionsKeys(true);
    }

    return $t;
  }

  /** Варианты выбора */
  public function setOptions(array $options, $add_filter = true, $ignore_keys = null)
  {
    $this->options = $options;

    if (!is_null($ignore_keys)) {
      $this->setIgnoreOptionsKeys($ignore_keys);
    }

    if ($add_filter) {
      $this->addFilter($this->getIgnoreOptionsKeys() ? $options : array_keys($options));
    }

    return $this;
  }

  /** Варианты выбора */
  public function getOptions()
  {
    return $this->options;
  }

  /** Использовать только значения опций */
  public function getIgnoreOptionsKeys()
  {
    return $this->ignore_options_keys;
  }

  /** Использовать только значения опций */
  public function setIgnoreOptionsKeys($ignore_options_keys)
  {
    $this->ignore_options_keys = $ignore_options_keys;

    return $this;
  }
}