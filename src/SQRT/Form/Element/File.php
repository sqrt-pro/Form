<?php

namespace SQRT\Form\Element;

use SQRT\Form\Element;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @method \Symfony\Component\HttpFoundation\File\File|UploadedFile getValue()
 */
class File extends Element
{
  public function render($attr = null)
  {
    return new \SQRT\Tag\Input($this->getInputName(), $this->getValue(), $this->prepareAttr($attr), 'file');
  }

  /** Расширение файла, если он загружен */
  public function getExtension()
  {
    $f = $this->getValue();

    if ($f) {
      return $f instanceof UploadedFile ? $f->getClientOriginalExtension() : $f->getExtension();
    }

    return false;
  }

  /**
   * Обычный файл копируется, загруженный - move_uploaded_file
   *
   * @return \Symfony\Component\HttpFoundation\File\File
   */
  public function copy($destination)
  {
    $f = $this->getValue();

    if ($f) {
      $arr = pathinfo($destination);
      if ($f instanceof UploadedFile) {
        return $f->move($arr['dirname'], $arr['basename']);
      } else {
        if (!@copy($f->getRealPath(), $destination)) {
          $error = error_get_last();

          throw new FileException(
            sprintf(
              'Could not move the file "%s" to "%s" (%s)',
              $f->getPathname(),
              $destination,
              strip_tags($error['message'])
            )
          );
        }

        return new \Symfony\Component\HttpFoundation\File\File($destination);
      }
    }

    return false;
  }

  public function validate(\Symfony\Component\HttpFoundation\File\File $value = null)
  {
    return parent::validate($value);
  }
}