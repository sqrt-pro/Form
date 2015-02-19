<?php

namespace SQRT\Form;

use SQRT\Tag;
use SQRT\Form;
use SQRT\Tag\Input;
use SQRT\Helpers\Filter;

abstract class Element
{
  protected $allow_multiple_choice;
  protected $default_value;
  protected $is_required;
  protected $placeholder;
  protected $filters;
  protected $errors;
  protected $value;
  protected $field;
  protected $name;
  protected $form;
  protected $attr;

  /** Шаблон обязательное поле не заполнено */
  protected $tmpl_err_required = 'Обязательное поле "%s" не заполнено';

  /** Шаблон ошибки по фильтру или регулярному выражению */
  protected $tmpl_err_wrong = 'Поле "%s" заполнено некорректно';

  function __construct($field, $name = null, Form $form = null)
  {
    $this->field = $field;
    $this->form  = $form;
    $this->name  = $name;
  }

  /**
   * Отрисовка элемента
   *
   * @return Tag
   */
  public function render($attr = null)
  {
    return new Input($this->getInputName(), $this->getValue(), $this->prepareAttr($attr, true));
  }

  /** Сброс значений и ошибок */
  public function reset($reset_defaults = false)
  {
    $this->errors = null;
    $this->value  = null;

    if ($reset_defaults) {
      $this->default_value = null;
    }
  }

  /**
   * Получить значение элемента. Если стоит флаг multiple choice, всегда будет возвращаться массив
   * @return mixed|array
   */
  public function getValue($only_valid = false)
  {
    if ($only_valid && !$this->isValid()) {
      return $this->isMultipleChoiceAllowed() ? array() : false;
    }

    $v = is_null($this->value) ? $this->getDefaultValue() : $this->value;

    if ($this->isMultipleChoiceAllowed()) {
      $v = (array) $v;
    }

    return $v;
  }

  /** Установить значение по-умолчанию */
  public function setDefaultValue($value)
  {
    $this->default_value = $value;

    return $this;
  }

  /** Получить значение по-умолчанию */
  public function getDefaultValue()
  {
    return $this->default_value;
  }

  /** Атрибуты для отрисовки тега */
  public function setAttr($attr)
  {
    $this->attr = $attr;

    return $this;
  }

  /** Атрибуты для отрисовки тега */
  public function getAttr()
  {
    return $this->attr;
  }

  /** Является ли поле обязательным */
  public function isRequired()
  {
    return (bool)$this->is_required;
  }

  /** Является ли поле обязательным */
  public function setIsRequired($required = true)
  {
    $this->is_required = $required;

    return $this;
  }

  /** Валидация значения */
  public function validate($value)
  {
    $this->reset();

    $this->value = $value;

    $this->validateRequired();
    $this->validateFilters();

    return $this->isValid();
  }

  /** Добавить ошибку */
  public function addError($error)
  {
    $this->errors[] = $error;

    return $this;
  }

  /** Является ли значение валидным */
  public function isValid()
  {
    return empty($this->errors);
  }

  /** Получить ошибки в виде массива, или объединить с помощью $join */
  public function getErrors($join = false)
  {
    if ($this->isValid()) {
      return false;
    }

    return $join ? join($join, $this->errors) : $this->errors;
  }

  /** Имя поля */
  public function getField()
  {
    return $this->field;
  }

  /** Название поля для отображения */
  public function getName()
  {
    return $this->name ?: $this->field;
  }

  /**
   * Название поля для отображения
   *
   * @return static
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /** Атрибут name для инпута */
  public function getInputName()
  {
    $n = false;
    if ($f = $this->getForm()) {
      $n = $f->getName();
    }

    $inp = $this->getField();

    return $n ? $n . '[' . $inp . ']' : $inp;
  }

  /** Атрибут id для инпута */
  public function getInputId()
  {
    $n = false;
    if ($f = $this->getForm()) {
      $n = $f->getName();
    }

    return 'form-' . ($n ? $n . '-' : '') . $this->getField();
  }

  /** Плейсхолдер для тегов */
  public function getPlaceholder()
  {
    return $this->placeholder;
  }

  /**
   * Плейсхолдер для тегов
   *
   * @return static
   */
  public function setPlaceholder($placeholder)
  {
    $this->placeholder = $placeholder;

    return $this;
  }

  /** @return Form */
  public function getForm()
  {
    return $this->form;
  }

  /** @return static */
  public function setForm(Form $form)
  {
    $this->form = $form;

    return $this;
  }

  /** Добавление условия проверки. $filter может быть callable, regexp или массивом опций */
  public function addFilter($filter)
  {
    $this->filters[] = $filter;

    return $this;
  }

  /** Шаблон SPRINTF для ошибки выбора из опций. Единственный параметр %s - название поля */
  public function setTmplErrOption($tmpl_err_option)
  {
    $this->tmpl_err_option = $tmpl_err_option;

    return $this;
  }

  /** Шаблон SPRINTF для ошибки выбора из опций. Единственный параметр %s - название поля */
  public function getTmplErrOption()
  {
    return $this->tmpl_err_option;
  }

  /** Шаблон SPRINTF для ошибки обязательного поля. Единственный параметр %s - название поля */
  public function setTmplErrRequired($tmpl_err_required)
  {
    $this->tmpl_err_required = $tmpl_err_required;

    return $this;
  }

  /** Шаблон SPRINTF для ошибки обязательного поля. Единственный параметр %s - название поля */
  public function getTmplErrRequired()
  {
    return $this->tmpl_err_required;
  }

  /** Шаблон SPRINTF для ошибки неправильно заполненного поля. Единственный параметр %s - название поля */
  public function setTmplErrWrong($tmpl_err_wrong)
  {
    $this->tmpl_err_wrong = $tmpl_err_wrong;

    return $this;
  }

  /** Шаблон SPRINTF для ошибки неправильно заполненного поля. Единственный параметр %s - название поля */
  public function getTmplErrWrong()
  {
    return $this->tmpl_err_wrong;
  }

  /** Дать возможность ввода нескольких значений */
  public function setMultipleChoiceAllowed($allow = true)
  {
    $this->allow_multiple_choice = $allow;

    return $this;
  }

  /** Включена ли возможность ввода нескольких значений */
  public function isMultipleChoiceAllowed()
  {
    return $this->allow_multiple_choice;
  }

  /** Проверка обязательных элементов */
  protected function validateRequired()
  {
    if ($this->isRequired() && empty($this->value)) {
      $this->addError(sprintf($this->tmpl_err_required, $this->getName()));

      return false;
    }

    return true;
  }

  /** Проверка по списку фильтров */
  protected function validateFilters()
  {
    if (!empty($this->value) && !empty($this->filters)) {
      foreach ($this->filters as $filter) {
        if (is_array($this->value) && $this->isMultipleChoiceAllowed()) {
          $this->value = Filter::Arr($this->value, $filter, null);

          $bad = empty($this->value);
        } else {
          $bad = is_null(Filter::Value($this->value, $filter, null));
        }

        if ($bad) {
          $this->addError(sprintf($this->tmpl_err_wrong, $this->getName()));

          return false;
        }
      }
    }

    return true;
  }

  /** Подготовка атрибутов */
  protected function prepareAttr($attr, $add_placeholder = false)
  {
    if (is_null($attr)) {
      $attr = $this->getAttr();
    }

    $arr = array('id' => $this->getInputId());
    if ($add_placeholder && $ph = $this->getPlaceholder()) {
      $arr['placeholder'] = $ph;
    }

    return array_merge($arr, Tag::MergeAttr($attr) ?: array());
  }
}