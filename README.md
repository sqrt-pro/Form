# Form

[![Build Status](https://travis-ci.org/sqrt-pro/Form.svg?branch=master)](https://travis-ci.org/sqrt-pro/Form)
[![Coverage Status](https://coveralls.io/repos/sqrt-pro/Form/badge.svg?branch=master)](https://coveralls.io/r/sqrt-pro/Form?branch=master)
[![Latest Stable Version](https://poser.pugx.org/sqrt-pro/form/version.svg)](https://packagist.org/packages/sqrt-pro/form)
[![License](https://poser.pugx.org/sqrt-pro/form/license.svg)](https://packagist.org/packages/sqrt-pro/form)

Компонент Form позволяет проверять данные, приходящие от пользователя, отображать компоненты формы, а также выполнять
пост-обработку этих данных.

Работа с формой начинается с формирования полей, из которых она состоит, а также их настройки. Например:

~~~ php

$f = new Form($request);
$f->addInput('name', 'Имя')
  ->addFilter('!^[a-z]+$!i');
$f->addCheckbox('is_active', 'Вкл');
$f->addSelect('status', 'Статус', array('new' => 'Новый', 'old' => 'Старый'));
$f->addFile('image', 'Изображение')
  ->setIsRequired();
    
~~~
    
Каждый из элементов формы - самостоятельный объект, имеющий свой набор параметров. Методы `add*` возвращают объект 
созданного элемента, соответственно можно сразу указать необходимые свойства и фильтры. 

## Элементы формы:

* Input
* Password
* Checkbox
* Radio
* Select
* Textarea
* File

При желании, можно создавать свои элементы формы, наследующие класс `SQRT\Form\Element` и добавлять их в форму через метод `$f->add()`.
    
После этого можно получить доступ ко всем полям формы с помощью `$f->getFields()`, или выборочно `$f->field('name')`;

Каждый из элементов реализует метод `render()`, который возвращает объект `Tag` с соответствующим полю отображением.
    
## Валидация

### Настройка формы

Для проверки данных существуют следующие возможности, настраиваемые для каждого из элементов формы:

* `setIsRequired()` - поле обязательно для заполнения
* `addFilter($filter)` - Фильтрация данных с помощью регулярного выражения, callable или массива допустимых опций.

Если нужна более сложная логика, можно добавить проверки до и после валидации, с помощью добавления соответствующих 
callable-объектов в методах `setBeforeValidation` и `setAfterValidation`:

~~~ php

$f->setBeforeValidation(
  function ($data, Form $form) {
    if ($data['status'] == 'new' && $data['age'] > 10) {
      $form->addError('Возраст новых участников должен быть меньше 10');
    }
    
    $data['is_active'] = 1;

    return $data;
  }
);

~~~
    
В функцию передается массив данных, соответствующий списку полей и объект формы. 
Функция обязательно должна вернуть массив с данными, при этом можно их изменять перед следующим этапом валидации.

### Результаты валидации

После создания формы можно проверить данные поступившие от пользователя: `$f->validate($data = null)`.

При создании объекта формы в него передается объект `Request`, из которого по-умолчанию форма получает данные пользователя.
При желании, можно передать данные напрямую в метод валидации.

Если данные не проходят валидацию, можно получить список ошибок формы с помощью `$f->getErrors()`, или проверить состояние 
формы с помощью `$f->isValid()`.

После валидации можно либо просто забрать "чистые" данные с помощью `$f->getValues()` или `$f->getValue('name')`, либо
добавить обработчик в саму форму:

~~~ php
$f->setProcessData(
  function(Form $form){
    $data = $form->getValues();
    
    try {
        // Действия с данными    
    } catch (\Exception $e) {
        $form->addError($e->getMessage());
    }
  }
);
~~~

Обработчик будет вызван только в случае успешной валидации.

### Наследование формы

Если форма наследуется, для настройки полей переопределяется метод `init()`, чтобы не дублировать логику конструктора.
 
Обработчики до\после валидации, а также процессинг данных также можно переопределить при наследовании:

* `beforeValidation($data)`
* `afterValidation($data)`
* `process()`
    
## Работа с файлами

По-умолчанию форма забирает данные из Request, если необходимо передать данные в метод `validate($data)` напрямую,
файлы передаются в виде объектов `\Symfony\Component\HttpFoundation\File\File`.

Для удобства работы с файлами, поле формы имеет следующие методы, различающие объекты `File` и `UploadedFile`:

~~~ php
$f->field('image')->getExtension(); // Расширение загруженного файла
$f->field('image')->copy($destination); // Скопировать или переместить (move_uploaded_file) файл
~~~

## Капча (Captcha)

Для включения проверки капчи в форме нужно вызвать метод `$f->enableCaptcha($name = 'captcha')`. 
Параметр $name указывает имя переменной в сессии и имя поля в форме. Можно отключить капчу передав `$name` равным `false`.

Скрипт, отображающий капчу пользователю, должен записать в сессию текущее значение капчи. 
В форме всегда используется сессия из переданного `Request`.

Текст сообщения о неверно указанной капче можно изменить с помощью `$f->setErrCaptcha($err_captcha)`.