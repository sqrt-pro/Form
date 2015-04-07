<?php

namespace SQRT\Form;

use SQRT\Exception as Ex;

class Exception extends Ex
{
  const VALUE_IS_NOT_FILE = 10;

  protected static $errors_arr = array(
    self::VALUE_IS_NOT_FILE => 'Значение не является объектом File'
  );
}