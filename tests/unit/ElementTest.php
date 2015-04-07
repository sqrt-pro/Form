<?php

use SQRT\Form;
use SQRT\Form\Element;
use SQRT\Form\Element\Input;
use SQRT\Form\Element\Checkbox;
use Symfony\Component\HttpFoundation\Request;

class ElementTest extends PHPUnit_Framework_TestCase
{
  function testValidation()
  {
    $el = new Input('age');
    $el->addFilter('is_numeric');

    $this->assertTrue($el->isValid(), 'Значения нет');

    $this->assertTrue($el->validate(123), 'Значение проходит валидацию');
    $this->assertEquals(123, $el->getValue(), 'Значение есть');
    $this->assertEquals(123, $el->getValue(true), 'Значение валидно');

    $this->assertFalse($el->validate('abc'), 'Значение не проходит валидацию');
    $this->assertEquals('abc', $el->getValue(), 'Невалидное значение');
    $this->assertEmpty($el->getValue(true), 'Валидного значения нет');

    $this->assertEquals(array('Поле "age" заполнено некорректно'), $el->getErrors(), 'Текст ошибок');
  }

  function testArrayValidation()
  {
    $el = new Checkbox('level');
    $el->setOptions(array(1 => 'One', 2 => 'Two', 3 => 'Three'));

    $this->assertTrue($el->validate(array(1, 3)), 'Массив содержит допустимые элементы');
    $this->assertEquals(array(1, 3), $el->getValue(true), 'Значение поля - массив');

    $this->assertTrue($el->validate(1), 'Одиночное значение');
    $this->assertEquals(array(1), $el->getValue(true), 'Значение поля - массив с одним значением');

    $this->assertTrue($el->validate(array(1, 4)), 'После валидации останется одно корректное значение');
    $this->assertEquals(array(1), $el->getValue(true), 'Значение поля - массив с одним значением');

    $this->assertFalse($el->validate(4), 'Неверное значение');
    $this->assertEquals(array(), $el->getValue(true), 'Пустой массив');

    $this->assertTrue($el->validate(array()), 'Пустой массив');

    $el->setMultipleChoiceAllowed(false);
    $this->assertFalse($el->validate(array(1, 3)), 'Массив содержит допустимые элементы, но запрещен мультивыбор');
    $this->assertFalse($el->getValue(true), 'Значения нет');

  }

  function testErrorsJoin()
  {
    $el = new Input('age');
    $el->addError('One');

    $this->assertEquals('One', $el->getErrors('<br />'), 'Объединение одной ошибки');

    $el->addError('Two');
    $el->addError('Three');

    $this->assertEquals('One<br />Two<br />Three', $el->getErrors('<br />'), 'Объединение нескольких ошибок');
  }

  function testRequiredField()
  {
    $el = new Input('age');
    $el->setIsRequired(true);

    $this->assertFalse($el->validate(''));
    $this->assertEquals(array('Обязательное поле "age" не заполнено'), $el->getErrors(), 'Текст ошибок без имени поля');

    $el = new Input('age', 'Возраст');
    $el->setIsRequired(true);
    $el->validate('');

    $this->assertEquals('Обязательное поле "Возраст" не заполнено', $el->getErrors(' '), 'Текст ошибок без имени поля');
  }

  function testDefaultValue()
  {
    $el = new Input('age');
    $el->addFilter('is_numeric');

    $this->assertEmpty($el->getValue(), 'Данных нет');

    $el->setDefaultValue(123);
    $this->assertEquals(123, $el->getValue(), 'Данных нет - значение по-умолчанию');

    $el->validate('');
    $this->assertEmpty($el->getValue(), 'Отправлено пустое значение, значение по-умолчанию стерто');

    $el->validate(321);
    $this->assertEquals(321, $el->getValue(), 'Новое значение');

    $el->reset();
    $this->assertEquals(123, $el->getValue(), 'Значение сброшено');

    $el->validate(321);
    $el->reset(true);
    $this->assertEmpty($el->getValue(), 'Сброшено значение по-умолчанию');
  }

  function testElementWithOptions()
  {
    $el = new Element\Select('type', array(1 => 'one', 2 => 'two'));
    $el->setPlaceholder('Тип');

    $this->assertFalse($el->validate(3), 'Значения нет в опциях');
    $this->assertTrue($el->validate(1), 'Значение проходит');

    $exp = '<select id="form-type" name="type">'
      . '<option value="">Тип</option>' . "\n"
      . '<option selected="selected" value="1">one</option>' . "\n"
      . '<option value="2">two</option>' . "\n"
      . '</select>';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Стандартный рендер в SELECT');

    $el = new Element\Select('type');
    $el->setIgnoreOptionsKeys(true);
    $el->setOptions(array(1 => 'one', 2 => 'two'));

    $this->assertTrue($el->validate('one'), 'Используется значение массива');
    $this->assertFalse($el->validate(1), 'Ключи массива не проходят');

    $exp = '<select id="form-type" name="type"><option value="one">one</option>' . "\n"
      . '<option value="two">two</option>' . "\n"
      . '</select>';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Стандартный рендер в SELECT без ключей');
  }

  function testRenderInputNameAndId()
  {
    $r = Request::create('/');
    $f = new \SQRT\Form($r);
    $el = new Input('age', 'Возраст', $f);

    $this->assertEquals('age', $el->getInputName(), 'Name для поля для формы без имени');
    $this->assertEquals('form-age', $el->getInputId(), 'ID для поля для формы без имени');
    $this->assertEquals(
      '<input class="one" id="form-age" name="age" type="text" />',
      $el->render('one')->toHTML(),
      'Генерация INPUT'
    );

    $f->setName('profile');

    $this->assertEquals('profile[age]', $el->getInputName(), 'Name для поля именованой формы');
    $this->assertEquals('form-profile-age', $el->getInputId(), 'ID для поля именованой формы');
    $this->assertEquals(
      '<input id="form-profile-age" name="profile[age]" type="text" />',
      $el->render()->toHTML(),
      'Генерация INPUT для именованой формы'
    );

    $this->assertEquals(
      '<input id="ololo" name="profile[age]" type="text" />',
      $el->render(array('id' => 'ololo'))->toHTML(),
      'Генерация INPUT с переопределенными параметрами'
    );
  }

  function testAttrRender()
  {
    $el = new Input('name');
    $el->setAttr('one');

    $exp = '<input class="one" id="form-name" name="name" type="text" />';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Атрибуты по-умолчанию');

    $exp = '<input class="two" id="form-name" name="name" type="text" />';
    $this->assertEquals($exp, $el->render('two')->toHTML(), 'Переопределяем атрибуты при рендере');
  }

  function testPlaceholder()
  {
    $el = new Input('name', 'Имя');
    $el->setPlaceholder('Укажите полное имя');

    $exp = '<input id="form-name" name="name" placeholder="Укажите полное имя" type="text" />';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Плейсхолдер для инпута');
  }

  function testPasswordRender()
  {
    $el = new Element\Password('pass');
    $el->validate('1234');

    $exp = '<input id="form-pass" name="pass" type="password" />';
    $this->assertEquals($exp, $el->render()->toHTML(), 'По-умолчанию в пароле значения не отображаются');

    $el->enableShowValue();
    $el->setPlaceholder('Пароль');

    $exp = '<input id="form-pass" name="pass" placeholder="Пароль" type="password" value="1234" />';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Отображение значения включено');
  }

  function testTextareaRender()
  {
    $el = new Element\Textarea('text');
    $el->validate('Ололо');
    $el->setPlaceholder('Текст');

    $exp = '<textarea id="form-text" name="text" placeholder="Текст">Ололо</textarea>';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Рендер textarea');
  }

  function testRadioRender()
  {
    $el = new Element\Radio('age', array('10', '20', '30'));
    $el->setDefaultValue(20);
    $el->setIgnoreOptionsKeys(true);

    $exp = '<label><input name="age" type="radio" value="10" /> 10</label>' . "\n"
      . '<label><input checked="checked" name="age" type="radio" value="20" /> 20</label>' . "\n"
      . '<label><input name="age" type="radio" value="30" /> 30</label>' . "\n";
    $this->assertEquals($exp, $el->render()->toHTML(), 'Рендер Radio');
  }

  function testCheckboxRender()
  {
    $el = new Element\Checkbox('is_active');
    $el->validate(1);

    $exp = '<input checked="checked" name="is_active" type="checkbox" value="1" />';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Одиночный checkbox');

    $el->setInputValue('yes');
    $exp = '<input name="is_active" type="checkbox" value="yes" />';
    $this->assertEquals($exp, $el->render()->toHTML(), 'Произвольное значение value');
  }

  function testCheckboxGroupRender()
  {
    $el = new Element\Checkbox('age');
    $el->setOptions(array(10 => 'Десять', 20 => 'Двадцать', 30 => 'Тридцать'));
    $el->setDefaultValue(array(10, 30));

    $exp = '<label><input checked="checked" name="age[]" type="checkbox" value="10" /> Десять</label>' . "\n"
      . '<label><input name="age[]" type="checkbox" value="20" /> Двадцать</label>' . "\n"
      . '<label><input checked="checked" name="age[]" type="checkbox" value="30" /> Тридцать</label>' . "\n";
    $this->assertEquals($exp, $el->render()->toHTML(), 'Список checkbox');
  }

  function testFileRender()
  {
    $el = new Element\File('image');
    $exp = '<input class="one" id="form-image" name="image" type="file" />';
    $this->assertEquals($exp, $el->render('one')->toHTML(), 'Рендер input[type=file]');
  }

  function testFile()
  {
    $uf = new \Symfony\Component\HttpFoundation\File\UploadedFile(__FILE__, 'hello.txt');

    $el = new Element\File('image', 'Изображение');
    $el->setIsRequired(true);

    $this->assertFalse($el->validate(null), 'Файл обязателен для загрузки');
    $this->assertEquals('Обязательное поле "Изображение" не заполнено', $el->getErrors(' '), 'Текст Ошибки');
    $this->assertEmpty($el->getValue(), 'Файл не загружен');

    $el->validate($uf);
    $this->assertEquals($uf, $el->getValue(), 'Значение поля - загруженный файл');
    $this->assertEquals('txt', $el->getExtension(), 'Расширение загруженного файла');
    $this->assertEquals('hello.txt', $el->getFilename(), 'Расширение загруженного файла');

    $f = new \Symfony\Component\HttpFoundation\File\File(__FILE__);
    $el->validate($f);

    $this->assertEquals('php', $el->getExtension(), 'Расширение файла');
    $this->assertEquals('ElementTest.php', $el->getFilename(), 'Расширение загруженного файла');
  }
}