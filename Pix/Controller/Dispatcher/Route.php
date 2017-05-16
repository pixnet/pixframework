<?php

 /**
  * Pix_Controller_dispatcher_Route router dispatcher, you can custom route rules
  *
  * @package Controller
  * @copyright 2003-2012 PIXNET Digital Media Corporation
  * @license http://framework.pixnet.net/license BSD License
  */
class Pix_Controller_Dispatcher_Route extends Pix_Controller_Dispatcher
{
    private $_routes = [];

    public function dispatch($url)
    {
        $match_params = [];

        foreach($this->getRoutes() as $rule => $route) {
            $rule = preg_replace('/\/$/', '', $rule);
            $route_rule = '/^' . preg_replace(array('/:(\w+)/', '/\//'), array('(?<$1>\w+)', '\/'), $rule) . '/';
            if(preg_match($route_rule, $url, $matches)) {
                $match_params = $matches;
                $controllerName = ($route['controller']) ? $route['controller'] : null;
                $actionName = ($route['action']) ? $route['action'] : null;
            }
        }

        if ($match_params) {
            foreach($match_params as $k => $v) {
                if (is_int($k)) {
                    unset($match_params[$k]);
                }
            }
        } else {
            list(, $controllerName, $actionName) = explode(DIRECTORY_SEPARATOR, $url);
            list($actionName, $ext) = explode('.', $actionName);
        }

        $args = array();
        if ($ext) {
            $args['ext'] = $ext;
        }

        $actionName = $actionName ? $actionName : 'index';
        $controllerName = $controllerName ? $controllerName : 'index';


        if (!preg_match('/^([A-Za-z]{1,})$/' , $controllerName)) {
            return null;
        }
        if (!preg_match('/^([A-Za-z][A-Za-z0-9]*)$/' , $actionName)) {
            return array($controllerName, null);
        }

        $args = array_merge($args, $_GET, $_POST, $match_params);

        return array($controllerName, $actionName, $args);
    }

    public function getRoutes()
    {
        return $this->_routes;
    }

    public function setRoute($routes)
    {
        foreach($routes as $route_key => $route_value) {
            if (is_scalar($route_value)) {
                list(, $controllerName, $actionName) = explode(DIRECTORY_SEPARATOR, $route_value);
                list($actionName, $ext) = explode('.', $actionName);
                $this->_routes[$route_value] = array('controller' => $controllerName, 'action' => $actionName);
            } else {
                $this->_routes[$route_key] = $route_value;
            }
        }
    }
}
