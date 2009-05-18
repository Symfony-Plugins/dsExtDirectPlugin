<?php

/**
 * This file is part of the dsExtDirectPlugin
 *
 * @package   dsExtDirectPlugin
 * @author    Jesse Dhillon <http://deva0.net/contact>
 * @copyright Copyright (c) 2009, Jesse Dhillon
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   SVN: $Id$
 */

/**
 * dsExtDirectErrorHandler Error handler for PHP-errors (not Exceptions)
 *
 * @package   dsExtDirectPlugin
 * @author    Jesse Dhillon <http://deva0.net/contact>
 */

class dsExtDirectErrorHandler
{ 
  public static function handleError($errno, $errstr, $errfile, $errline, $errcontext = null) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline); 
    die();
  }
}
