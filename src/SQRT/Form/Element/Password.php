<?php

namespace SQRT\Form\Element;

use SQRT\Form\Element;

class Password extends Element
{
  protected $show_value;

  public function render($attr = null)
  {
    return new \SQRT\Tag\Input(
      $this->getInputName(),
      $this->isShowValueEnabled() ? $this->getValue() : null,
      $this->prepareAttr($attr, true),
      'password'
    );
  }

  /** Выводить ли значение поля */
  public function isShowValueEnabled()
  {
    return $this->show_value;
  }

  /**
   * Выводить значение поля
   *
   * @return static
   */
  public function enableShowValue($show_value = true)
  {
    $this->show_value = $show_value;

    return $this;
  }
}