<?php

/**
 * This file is part of the dsExtDirectPlugin
 *
 * @package   dsExtDirectPlugin
 * @author    Daniel Stevens <danhstevens@gmail.com>
 * @copyright Copyright (c) 2009, Daniel Stevens
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   SVN: $Id$
 */

/**
 * dsExtDirectController is the manager for the Ext.Direct interface
 *
 * @package    dsExtDirectPlugin
 * @author     Daniel Stevens <danhstevens@gmail.com>
 */

class dsExtDirectController extends sfWebController
{
  protected $resultAdapter = null;
  
  /**
   * Initializes this controller.
   *
   * @param sfContext $context A sfContext implementation instance
   */
  public function initialize($context)
  {
    parent::initialize($context);
    
    //Allow many forwards since a single request may constitute several forwards
    $this->maxForwards = 50;
  }
  
  /**
   * Gets the result adapter for the action
   *
   * @return dsAbstractResultAdapter
   */
  public function getResultAdapter()
  {
    if(is_null($this->resultAdapter))
    {
      $result = sfConfig::get(sprintf('mod_%s_%s_result', $this->context->getModuleName(), $this->context->getActionName()), array());
      $class = isset($result['class']) ? $result['class'] : 'dsPropertyResultAdapter';
      $param = isset($result['param']) ? $result['param'] : array();

      $adapter = new $class($param);
      
      $this->resultAdapter = $adapter instanceof dsAbstractResultAdapter ? $adapter : new dsPropertyResultAdapter();
    }
    
    return $this->resultAdapter;
  }
  
  public function getRenderMode()
  {
    return $this->getResultAdapter()->getRenderMode();
  }
  
  /**
   * Handles RPC requests & sends response
   */
  public function dispatch()
  {
    if (sfConfig::get('sf_logging_enabled'))
    {
      $this->context->getEventDispatcher()->notify(new sfEvent($this, 'application.log', array("Starting dsExtDirectRouter.")));
    }
    
    try
    {
      dsExtDirectRouter::handle();
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
    }
  }
  
  /**
   * Runs RPC actions
   *
   * @param mixed $cdata
   * 
   * @return stdClass Response Object
   */
  public function invokeRpcAction($cdata)
  {
    //Load API Specs
    $api = dsExtDirectApi::getInstance();
    
    // Response object
    $response = new stdClass();
    
    try
    {
      // Fetch action (in symfony: a 'module')
      $action = $cdata->action;
      if(isset($api[$action]))
      {
        $apiAction = $api[$action];
      }
      else 
      {
        throw new Exception('Call to undefined action: ' . $cdata->action);
      }
      
      // Fetch method (in symfony: an 'action')
      $method = $cdata->method;
      if(isset($apiAction['methods'][$method]))
      {
        $apiMethod = $apiAction['methods'][$method];
      }
      else
      {
        throw new Exception("Call to undefined method: $method on action: $action");
      }
      
      $response->type = 'rpc';
      $response->tid = isset($cdata->tid) ? $cdata->tid : null;
      $response->action = $action;
      $response->method = $method;
      
      //Populate request parameters
      if(!dsExtDirectRouter::isForm())
      {
        $this->context->getRequest()->getParameterHolder()->clear();
        
        if(isset($cdata->data) && is_array($cdata->data))
        {
          foreach ($cdata->data[0] as $key => $val)
          {
            $this->context->getRequest()->setParameter($key, $val);
          }
        }
      }
      
      //Call symfony action
      if (sfConfig::get('sf_logging_enabled'))
      {
        $this->context->getEventDispatcher()->notify(new sfEvent($this, 'application.log', array(sprintf('Forwarding to "%s/%s".', $action, $method))));
      }
      $this->forward($action, $method);
      
      //Get the action
      $actionInstance = $this->getActionStack()->getLastEntry()->getActionInstance();
      
      //Throw an exception if we've reached the 404 module
      if($actionInstance->getModuleName() == sfConfig::get('sf_error_404_module') && $actionInstance->getActionName() == sfConfig::get('sf_error_404_action'))
      {
        throw new sfError404Exception("Call to undefined method: $method on action: $action");
      }
      
      
      $response->result = $this->getResultAdapter()->getResult($actionInstance);
    }
    catch (Exception $e)
    {
      $response->type = 'exception';
      $response->message = $e->getMessage();
      $response->where = sfConfig::get('sf_debug') ? $e->getTraceAsString() : null;
    }
    
    return $response;
  }
  
  /**
   * Redirect not supported
   *
   * @see sfWebController
   */
  public function redirect($url, $delay = 0, $statusCode = 302)
  {
    
  }
}

?>