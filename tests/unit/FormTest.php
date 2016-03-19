<?php

use SQRT\Form;
use Symfony\Component\HttpFoundation\Request;

class FormTest extends PHPUnit_Framework_TestCase
{
  function testAddElement()
  {
    $r = Request::create('/');
    $f = new Form($r);

    $arr = array(1 => 'One', 2 => 'Two');

    $el = $f->addCheckbox('is_active', 'Вкл');
    $this->assertInstanceOf('SQRT\Form\Element\Checkbox', $el, 'Чекбокс');

    $this->assertEquals($el, $f->field('is_active'));

    $el = $f->addRadio('type', 'Тип', $arr);
    $this->assertInstanceOf('SQRT\Form\Element\Radio', $el, 'Радиобатон');
    $this->assertEquals($arr, $el->getOptions(), 'Список опций');

    $el = $f->addSelect('status', 'Статус', $arr);
    $this->assertInstanceOf('SQRT\Form\Element\Select', $el, 'Селектбокс');
    $this->assertEquals($arr, $el->getOptions(), 'Список опций');

    $el = $f->addInput('name', 'Имя');
    $this->assertInstanceOf('SQRT\Form\Element\Input', $el, 'Инпут');

    $el = $f->addPassword('pass', 'Пароль');
    $this->assertInstanceOf('SQRT\Form\Element\Password', $el, 'Пароль');

    $el = $f->addTextarea('text', 'Текст');
    $this->assertInstanceOf('SQRT\Form\Element\Textarea', $el, 'Текстовое поле');

    $fields = $f->getFields();
    $this->assertCount(6, $fields, 'Всего 5 полей');
    $this->assertArrayHasKey('is_active', $fields, 'Ассоциативный массив по именам');
  }

  function testValidation()
  {
    $r   = Request::create('/', 'POST', array('name' => 123, 'age' => 3));
    $f   = new Form($r);
    $arr = array(1 => 'One', 2 => 'Two');

    $f->addCheckbox('is_active')
      ->setDefaultValue(1);
    $f->addInput('name')
      ->addFilter('!^[a-z]+$!i')
      ->setDefaultValue('hello');
    $f->addSelect('age')
      ->setOptions($arr)
      ->setIsRequired(true);

    $this->assertTrue($f->checkRequestHasValue('name'));
    $this->assertFalse($f->checkRequestHasValue('non_exists'));

    $this->assertFalse($f->getErrors(), 'До валидации ошибок нет');
    $this->assertFalse($f->getValues(), 'Валидных данных нет');

    $this->assertFalse($f->validate(), 'Валидация из Request');
    $this->assertFalse($f->isValid(), 'Есть ошибки');

    $exp = array('Поле "name" заполнено некорректно', 'Поле "age" заполнено некорректно');
    $this->assertEquals($exp, $f->getErrors(), 'Массив ошибок');

    $exp = 'Поле "name" заполнено некорректно<br />Поле "age" заполнено некорректно';
    $this->assertEquals($exp, $f->getErrors('<br />'), 'Ошибки строкой');

    $f->reset();

    $this->assertFalse($f->getErrors(), 'После сброса ошибок нет');

    $arr = array('name' => 'John', 'age' => 2, 'unused' => 123);
    $this->assertTrue($f->validate($arr), 'Валидация произвольных данных');

    $this->assertEquals(array('is_active' => false, 'name' => 'John', 'age' => 2), $f->getValues(), 'Чистые данные после валидации');
    $this->assertEquals('John', $f->getValue('name'), 'Получение значения по имени поля');
  }

  function testEmptyValWithFilters()
  {
    $r = Request::create('/');
    $f = new Form($r);
    $f->addInput('name')
      ->setIsRequired();
    $f->addInput('age')
      ->addFilter('!^[0-9]+$!');

    $this->assertTrue($f->validate(array('name' => 'John')), 'Форма валидна');
    $this->assertFalse($f->getErrors(), 'Ошибок нет');

    $arr = $f->getValues();
    $this->assertFalse($arr['age'], 'В результирующем массиве отсутствующее поле будет заполнено false');
  }

  function testBeforeAfterValidation()
  {
    $r = Request::create('/');
    $f = new Form($r);
    $f->addInput('age');

    $f->setBeforeValidation(
      function ($data, Form $form) {
        if (!empty($data['wrong'])) {
          $form->addError('Никогда не случится, т.к. поступают "чистые" данные');
        }

        if ($data['age'] > 10) {
          $data['before'] = 1;
          $data['age'] += 10;
        } else {
          $form->addError('Возраст должен быть больше 10');
        }

        return $data;
      }
    );

    $f->setAfterValidation(
      function ($data, Form $form) {
        if ($data['age'] > 100) {
          $data['after'] = 1;
          $data['age'] += 100;
        } else {
          $form->addError('Возраст должен быть больше 100');
        }

        return $data;
      }
    );

    $this->assertFalse($f->validate(array('age' => 5, 'wrong' => 1)), 'Данные не проходят валидацию');
    $this->assertEquals('Возраст должен быть больше 10, Возраст должен быть больше 100', $f->getErrors(', '), 'Ошибки');

    $f->validate(array('age' => 123));

    $exp = array('age' => 233, 'before' => 1, 'after' => 1);
    $this->assertEquals($exp, $f->getValues(), 'Фильтры до и после могут изменять итоговые данные');
  }

  function testNotSent()
  {
    $r = Request::create('/');
    $f = new Form($r);
    $f->addInput('age')
      ->setIsRequired();

    $this->assertFalse($f->validate(), 'Данные не переданы - форма невалидна');
    $this->assertFalse($f->getErrors(), 'Ошибок нет');
  }

  function testProcess()
  {
    $r = Request::create('/');
    $f = new Form($r);
    $f->addInput('age')
      ->setIsRequired();
    $f->setProcessData(
      function(Form $form){
        $age = $form->getValue('age');
        if (empty($age) || $age < 10) {
          $form->addError('Ошибка при процессинге данных');
        }
      }
    );

    $f->validate(array());
    $exp = 'Обязательное поле "age" не заполнено';
    $this->assertEquals($exp, $f->getErrors(' '), 'Процессинг не сработал, т.к. форма невалидна');

    $this->assertFalse($f->validate(array('age' => 5)), 'Сработало условие при обработке данных');

    $exp = 'Ошибка при процессинге данных';
    $this->assertEquals($exp, $f->getErrors(' '), 'Процессинг добавил свою ошибку');
  }

  function testFile()
  {
    $r = Request::create('/');
    $f = new Form($r);
    $f->addFile('image', 'Изображение')
      ->setIsRequired();

    $f->validate(array('image' => ''));
    $this->assertEquals('Обязательное поле "Изображение" не заполнено', $f->getErrors(' '), 'Ошибка');

    $file = new \Symfony\Component\HttpFoundation\File\File(__FILE__);

    $this->assertTrue($f->validate(array('image' => $file)));
    $this->assertEquals($file, $f->getValue('image'), 'Файл в данных формы');
  }

  function testNamedFormDataFromRequest()
  {
    $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(__FILE__, 'test.php');
    $r = Request::create(
      '/',
      'GET',
      array('myform' => array('name' => 'John')),
      array(),
      array('myform' => array('image' => $file))
    );

    $f = new Form($r, 'myform');
    $f->addInput('name')
      ->setIsRequired();
    $f->addFile('image', 'Изображение');

    $f->validate();

    $this->assertTrue($f->checkRequestHasValue('name'));
    $this->assertTrue($f->checkRequestHasValue('image'));

    $this->assertEquals('John', $f->getValue('name'), 'Имя');
    $this->assertFalse($f->getErrors(' '), 'Ошибки');
    $this->assertEquals($file, $f->getValue('image'), 'Файл в данных формы с именем');

    $f->validate(array('name' => 'Hi'));

    $this->assertEquals('Hi', $f->getValue('name'), 'Данные переданы напрямую');
    $this->assertFalse($f->getValue('image'), 'Изображения нет');
  }

  function testCaptcha()
  {
    $r = Request::create('/');
    $f = new Form($r);
    $f->addInput('name');

    $this->assertFalse($f->isCaptchaEnabled());

    $f->enableCaptcha('heyho');

    $this->assertTrue($f->isCaptchaEnabled());
    $this->assertFalse($f->validate(array('name' => 'John')), 'Капча не указана');
    $this->assertEquals('Защитный код указан неверно', $f->getErrors(' '), 'Текст ошибок');

    $s = new \Symfony\Component\HttpFoundation\Session\Session(
      new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
    );
    $s->set('heyho', 'abcdef');
    $r->setSession($s);

    $this->assertTrue($f->validate(array('heyho' => 'abcdef')), 'Валидация с указанием капчи');
  }

  function testSetDefaultValues()
  {
    $r = Request::create('/');
    $f = new Form($r);
    $f->addInput('name');
    $f->addCheckbox('is_active')
      ->setDefaultValue(1);

    $this->assertEquals(1, $f->field('is_active')->getValue(), 'Значение по-умолчанию задано напрямую');

    $f->setDefaultValues(array('name' => 'John'));

    $this->assertEquals('John', $f->field('name')->getValue(), 'Значение по-умолчанию задано через форму');
    $this->assertFalse($f->field('is_active')->getValue(), 'Значение по-умолчанию было затерто');
  }
}