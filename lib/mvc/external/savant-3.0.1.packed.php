<?php  class Savant3_Error { public $code = null; public $info = array(); public $level = E_USER_ERROR; public $trace = null; public function __construct($conf = array()) { foreach ($conf as $key => $val) { $this->$key = $val; } if ($conf['trace'] === true) { $this->trace = debug_backtrace(); } } public function __toString() { ob_start(); echo get_class($this) . ': '; print_r(get_object_vars($this)); return ob_get_clean(); } }   class Savant3_Exception extends Exception { }   abstract class Savant3_Filter { protected $Savant = null; public function __construct($conf = null) { settype($conf, 'array'); foreach ($conf as $key => $val) { $this->$key = $val; } } public static function filter($text) { return $text; } }   abstract class Savant3_Plugin { protected $Savant = null; public function __construct($conf = null) { settype($conf, 'array'); foreach ($conf as $key => $val) { $this->$key = $val; } } }   class Savant3 { protected $__config = array( 'template_path' => array(), 'resource_path' => array(), 'error_text' => "\n\ntemplate error, examine fetch() result\n\n", 'exceptions' => false, 'autoload' => false, 'compiler' => null, 'filters' => array(), 'plugins' => array(), 'template' => null, 'plugin_conf' => array(), 'extract' => false, 'fetch' => null, 'escape' => array('htmlspecialchars'), ); public function __construct($config = null) { settype($config, 'array'); if (isset($config['template_path'])) { $this->setPath('template', $config['template_path']); } else { $this->setPath('template', null); } if (isset($config['resource_path'])) { $this->setPath('resource', $config['resource_path']); } else { $this->setPath('resource', null); } if (isset($config['error_text'])) { $this->setErrorText($config['error_text']); } if (isset($config['autoload'])) { $this->setAutoload($config['autoload']); } if (isset($config['extract'])) { $this->setExtract($config['extract']); } if (isset($config['exceptions'])) { $this->setExceptions($config['exceptions']); } if (isset($config['template'])) { $this->setTemplate($config['template']); } if (isset($config['escape'])) { $this->setEscape($config['escape']); } if (isset($config['plugin_conf']) && is_array($config['plugin_conf'])) { foreach ($config['plugin_conf'] as $name => $opts) { $this->setPluginConf($name, $opts); } } if (isset($config['filters'])) { $this->addFilters($config['filters']); } } public function __call($func, $args) { $plugin = $this->plugin($func); if ($this->isError($plugin)) { return $plugin; } switch (count($args)) { case 0: return $plugin->$func(); case 1: return $plugin->$func($args[0]); break; case 2: return $plugin->$func($args[0], $args[1]); break; case 3: return $plugin->$func($args[0], $args[1], $args[2]); break; default: return call_user_func_array(array($plugin, $func), $args); break; } } public function __toString() { return $this->getOutput(); } public function apiVersion() { return '@package_version@'; } public function plugin($name) { $plugins = $this->__config['plugins']; $autoload = $this->__config['autoload']; if (! array_key_exists($name, $plugins)) { $class = "Savant3_Plugin_$name"; if (! class_exists($class, $autoload)) { $file = "$class.php"; $result = $this->findFile('resource', $file); if (! $result) { return $this->error( 'ERR_PLUGIN', array('method' => $name) ); } else { include_once $result; } } $plugin_conf = $this->__config['plugin_conf']; if (! empty($plugin_conf[$name])) { $opts = $plugin_conf[$name]; } else { $opts = array(); } $opts['Savant'] = $this; $plugins[$name] = new $class($opts); } return $plugins[$name]; } public function getConfig($key = null) { if (is_null($key)) { return $this->__config; } elseif (empty($this->__config[$key])) { return null; } else { return $this->__config[$key]; } } public function setAutoload($flag) { $this->__config['autoload'] = (bool) $flag; } public function setCompiler($compiler) { $this->__config['compiler'] = $compiler; } public function setErrorText($text) { $this->__config['error_text'] = $text; } public function setExceptions($flag) { $this->__config['exceptions'] = (bool) $flag; } public function setExtract($flag) { $this->__config['extract'] = (bool) $flag; } public function setPluginConf($plugin, $config = null) { $this->__config['plugin_conf'][$plugin] = $config; } public function setTemplate($template) { $this->__config['template'] = $template; } public function setEscape() { $this->__config['escape'] = (array) @func_get_args(); } public function addEscape() { $args = (array) @func_get_args(); $this->__config['escape'] = array_merge( $this->__config['escape'], $args ); } public function getEscape() { return $this->__config['escape']; } public function escape($value) { if (func_num_args() == 1) { foreach ($this->__config['escape'] as $func) { if (is_string($func)) { $value = $func($value); } else { $value = call_user_func($func, $value); } } } else { $callbacks = func_get_args(); array_shift($callbacks); foreach ($callbacks as $func) { if (is_string($func)) { $value = $func($value); } else { $value = call_user_func($func, $value); } } } return $value; } public function eprint($value) { $num = func_num_args(); if ($num == 1) { echo $this->escape($value); } else { $args = func_get_args(); echo call_user_func_array( array($this, 'escape'), $args ); } } public function setPath($type, $path) { $this->__config[$type . '_path'] = array(); switch (strtolower($type)) { case 'template': $this->addPath($type, '.'); break; case 'resource': $this->addPath($type, dirname(__FILE__) . '/Savant3/resources/'); break; } $this->addPath($type, $path); } public function addPath($type, $path) { if (is_string($path) && ! strpos($path, '://')) { $path = explode(PATH_SEPARATOR, $path); $path = array_reverse($path); } else { settype($path, 'array'); } foreach ($path as $dir) { $dir = trim($dir); if (strpos($dir, '://') && substr($dir, -1) != '/') { $dir .= '/'; } elseif (substr($dir, -1) != DIRECTORY_SEPARATOR) { $dir .= DIRECTORY_SEPARATOR; } array_unshift( $this->__config[$type . '_path'], $dir ); } } protected function findFile($type, $file) { $set = $this->__config[$type . '_path']; foreach ($set as $path) { $fullname = $path . $file; if (strpos($path, '://') === false) { $path = realpath($path); $fullname = realpath($fullname); } if (file_exists($fullname) && is_readable($fullname) && substr($fullname, 0, strlen($path)) == $path) { return $fullname; } } return false; } public function assign() { $arg0 = @func_get_arg(0); $arg1 = 1 < func_num_args() ? @func_get_arg(1) : null; if (is_object($arg0)) { foreach (get_object_vars($arg0) as $key => $val) { if ($key != '__config') { $this->$key = $val; } } return true; } if (is_array($arg0)) { foreach ($arg0 as $key => $val) { if ($key != '__config') { $this->$key = $val; } } return true; } if (is_string($arg0) && func_num_args() > 1 && $arg0 != '__config') { $this->$arg0 = $arg1; return true; } return false; } public function assignRef($key, $val) { if ($key != '__config') { $this->$key = $val; return true; } else { return false; } } public function display($tpl = null) { echo $this->getOutput($tpl); } public function getOutput($tpl = null) { $output = $this->fetch($tpl); if ($this->isError($output)) { $text = $this->__config['error_text']; return $this->escape($text); } else { return $output; } } public function fetch($tpl = null) { if (is_null($tpl)) { $tpl = $this->__config['template']; } $result = $this->template($tpl); if (! $result || $this->isError($result)) { return $result; } else { $this->__config['fetch'] = $result; unset($result); unset($tpl); if ($this->__config['extract']) { extract(get_object_vars($this), EXTR_REFS); } ob_start(); if ($this->__config['filters']) { ob_start(); include $this->__config['fetch']; echo $this->applyFilters(ob_get_clean()); } else { include $this->__config['fetch']; } $this->__config['fetch'] = null; return ob_get_clean(); } } protected function template($tpl = null) { if (is_null($tpl)) { $tpl = $this->__config['template']; } $file = $this->findFile('template', $tpl); if (! $file) { return $this->error( 'ERR_TEMPLATE', array('template' => $tpl) ); } if ($this->__config['compiler']) { $result = call_user_func( array($this->__config['compiler'], 'compile'), $file ); } else { $result = $file; } if (! $result || $this->isError($result)) { return $this->error( 'ERR_COMPILER', array( 'template' => $tpl, 'compiler' => $result ) ); } else { return $result; } } public function setFilters() { $this->__config['filters'] = (array) @func_get_args(); } public function addFilters() { foreach ((array) @func_get_args() as $callback) { $this->__config['filters'][] = $callback; } } protected function applyFilters($buffer) { $autoload = $this->__config['autoload']; foreach ($this->__config['filters'] as $callback) { if (is_array($callback) && is_string($callback[0]) && substr($callback[0], 0, 15) == 'Savant3_Filter_' && ! class_exists($callback[0], $autoload)) { $file = $callback[0] . '.php'; $result = $this->findFile('resource', $file); if ($result) { include_once $result; } } $buffer = call_user_func($callback, $buffer); } return $buffer; } public function error($code, $info = array(), $level = E_USER_ERROR, $trace = true) { $autoload = $this->__config['autoload']; if ($this->__config['exceptions']) { if (! class_exists('Savant3_Exception', $autoload)) { } throw new Savant3_Exception($code); } $config = array( 'code' => $code, 'info' => (array) $info, 'level' => $level, 'trace' => $trace ); if (! class_exists('Savant3_Error', $autoload)) { } $err = new Savant3_Error($config); return $err; } public function isError($obj) { $autoload = $this->__config['autoload']; if (! is_object($obj)) { return false; } else { if (! class_exists('Savant3_Error', $autoload)) { } $is = $obj instanceof Savant3_Error; $sub = is_subclass_of($obj, 'Savant3_Error'); return ($is || $sub); } } } ?>