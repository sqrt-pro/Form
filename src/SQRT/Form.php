<?php

namespace SQRT;

use SQRT\Form\Element;
use SQRT\Form\ElementWithOptions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;

class Form
{
  /** @var Element[]|ElementWithOptions[] */
  protected $fields;
  protected $name;
  protected $data;

  /** @var Request */
  protected $request;

  protected $errors;
  protected $captcha;

  protected $before_validation;
  protected $after_validation;
  protected $process_data;

  /** Шаблон капча заполнена неверно */
  protected $err_captcha = 'Защитный код указан неверно';

  function __construct(Request $request, $name = null)
  {
    $this->request = $request;
    $this->name    = $name;

    $this->init();
  }

  /**
   * Добавление ошибки
   *
   * @return static
   */
  public function addError($err)
  {
    $this->errors[] = $err;

    return $this;
  }

  /**
   * Получить элемент по имени поля
   *
   * @return Element|ElementWithOptions
   */
  public function field($name)
  {
    return isset($this->fields[$name]) ? $this->fields[$name] : false;
  }

  /**
   * Включение капчи. $name - имя поля и переменной в сессии
   *
   * @return static
   */
  public function enableCaptcha($name = 'captcha')
  {
    $this->captcha = $name;

    return $this;
  }

  /** Имя переменной для капчи */
  public function getCaptchaName()
  {
    return $this->captcha ?: false;
  }

  /** Проверка, включена ли капча */
  public function isCaptchaEnabled()
  {
    return !empty($this->captcha);
  }

  /**
   * Получить все поля формы
   *
   * @return Element[]|ElementWithOptions[]
   */
  public function getFields()
  {
    return $this->fields;
  }

  /** Установка значений по-умолчанию. Предыдущие значения затираются. */
  public function setDefaultValues($array)
  {
    foreach ($this->fields as $field=>$el) {
      $v = false;
      if (is_array($array)) {
        $v = isset($array[$field]) ? $array[$field] : false;
      }
      $el->setDefaultValue($v);
    }
  }

  /** @return static */
  public function add(Element $element)
  {
    $this->fields[$element->getField()] = $element;

    return $this;
  }

  /** @return Element\Input */
  public function addInput($field, $name = null)
  {
    $el = new Element\Input($field, $name, $this);
    $this->add($el);

    return $el;
  }

  /** @return Element\Password */
  public function addPassword($field, $name = null)
  {
    $el = new Element\Password($field, $name, $this);
    $this->add($el);

    return $el;
  }

  /** @return Element\Checkbox */
  public function addCheckbox($field, $name = null, $options = null)
  {
    $el = new Element\Checkbox($field, $name, $this);

    if ($options) {
      $el->setOptions($options);
    }

    $this->add($el);

    return $el;
  }

  /** @return Element\Textarea */
  public function addTextarea($field, $name = null)
  {
    $el = new Element\Textarea($field, $name, $this);
    $this->add($el);

    return $el;
  }

  /** @return Element\Radio */
  public function addRadio($field, array $options, $name = null)
  {
    $el = new Element\Radio($field, $options, $name, $this);
    $this->add($el);

    return $el;
  }

  /** @return Element\Select */
  public function addSelect($field, array $options, $name = null)
  {
    $el = new Element\Select($field, $options, $name, $this);
    $this->add($el);

    return $el;
  }

  /** @return Element\File */
  public function addFile($field, $name = null)
  {
    $el = new Element\File($field, $name, $this);
    $this->add($el);

    return $el;
  }

  /** @return static */
  public function reset($reset_defaults = false)
  {
    $this->errors = null;
    $this->data = array();

    foreach ($this->getFields() as $el) {
      $el->reset($reset_defaults);
    }

    return $this;
  }

  /** Процессинг формы */
  public function validate($data = null)
  {
    $this->reset();

    if (!$this->checkCaptcha($data)) {
      $this->addError($this->getErrCaptcha());
    }

    $data = $this->beforeValidation($this->getValuesFromRequest($data));

    foreach ($this->fields as $field => $el) {
      $el->validate($data[$field]);
      unset($data[$field]);

      $this->data[$field] = $el->getValue(true);

      if ($err = $el->getErrors()) {
        foreach ($err as $str) {
          $this->addError($str);
        }
      }
    }

    // Если остались доп.поля после предвалидации - переносим к общим данным
    if (!empty($data)) {
      $this->data = array_merge($data, $this->data);
    }

    $this->data = $this->afterValidation($this->data);

    if ($this->isValid()) {
      $this->process();

      return $this->isValid();
    }

    return false;
  }

  /** Получить все данные формы после успешной валидации */
  public function getValues()
  {
    return $this->isValid() && !empty($this->data) ? $this->data : false;
  }

  /** Получить значение поля после успешной валидации */
  public function getValue($field)
  {
    $v = $this->getValues();

    return isset($v[$field]) ? $v[$field] : false;
  }

  /** Есть ли ошибки в форме */
  public function isValid()
  {
    return empty($this->errors);
  }

  /** Получить массив ошибок или объединить с помощью $join */
  public function getErrors($join = false)
  {
    if ($this->isValid()) {
      return false;
    }

    return $join ? join($join, $this->errors) : $this->errors;
  }

  /** Имя формы */
  public function getName()
  {
    return $this->name;
  }

  /** Имя формы */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /** @return Request */
  public function getRequest()
  {
    return $this->request;
  }

  /**
   * Добавить callable-проверку\фильтрацию данных ПОСЛЕ основной валидации.
   * В функцию передаются массив данных и объект формы.
   * После обработки функция должна вернуть обработанный массив.
   *
   * @return static
   */
  public function setAfterValidation($after_validation = null)
  {
    $this->after_validation = $after_validation;

    return $this;
  }

  /**
   * Добавить callable-проверку\фильтрацию данных ДО основной валидации.
   * В функцию передаются массив данных и объект формы.
   * После обработки функция должна вернуть обработанный массив.
   *
   * @return static
   */
  public function setBeforeValidation($before_validation)
  {
    $this->before_validation = $before_validation;

    return $this;
  }

  /**
   * Добавить callable-обработчик для "чистых" данных.
   *
   * Будет выполнен, только если форма прошла валидацию
   */
  public function setProcessData($process_data)
  {
    $this->process_data = $process_data;

    return $this;
  }

  /** Текст ошибки для капчи */
  public function getErrCaptcha()
  {
    return $this->err_captcha;
  }

  /** Текст ошибки для капчи */
  public function setErrCaptcha($err_captcha)
  {
    $this->err_captcha = $err_captcha;

    return $this;
  }

  /** Обработка данных */
  protected function process()
  {
    if ($this->process_data) {
      call_user_func_array($this->process_data, array($this));
    }
  }

  /** Проверка капчи */
  protected function checkCaptcha($data = null)
  {
    if (!$name = $this->getCaptchaName()) {
      return true;
    }

    if (!is_null($data)) {
      $v = isset($data[$name]) ? $data[$name] : false;
    } else {
      $v = $this->getRequest()->get($this->getName() ? $this->getName() . '[' . $name . ']' : $name, false, true);
    }

    if (empty($v)) {
      return false;
    }

    return $this->getRequest()->getSession()->get($name) == $v;
  }

  /** Получить все значения из $data или из Request */
  protected function getValuesFromRequest($data = null)
  {
    $out = array();
    foreach ($this->fields as $field => $el) {
      $out[$field] = $this->getValueFromRequest($el, $data);
    }

    return $out;
  }

  /** Получить значение из $data или из Request */
  protected function getValueFromRequest(Element $element, $data = null)
  {
    $field   = $element->getField();
    $is_file = $element instanceof Element\File;

    if (!is_null($data)) {
      $v = isset($data[$field]) ? $data[$field] : null;
    } else {
      $input_name = $element->getInputName();

      $v = $is_file
        ? $this->getRequest()->files->get($input_name, null, true)
        : $this->getRequest()->get($input_name, null, true);
    }

    if ($is_file) {
      return $v instanceof File ? $v : null;
    }

    return $v;
  }

  /**
   * Проверка данных ПОСЛЕ валидации
   *
   * @return array $data
   */
  protected function beforeValidation($data)
  {
    if ($this->before_validation) {
      $data = call_user_func_array($this->before_validation, array($data, $this));
    }

    return $data;
  }

  /**
   * Проверка данных ДО валидации
   *
   * @return array $data
   */
  protected function afterValidation($data)
  {
    if ($this->after_validation) {
      $data = call_user_func_array($this->after_validation, array($data, $this));
    }

    return $data;
  }

  protected function init()
  {

  }
}