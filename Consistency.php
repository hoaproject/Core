<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2014, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Core\Consistency {

/**
 * Hard-preload.
 */
$path = __DIR__ . DIRECTORY_SEPARATOR;
define('PATH_EVENT',     $path . 'Event.php');
define('PATH_EXCEPTION', $path . 'Exception.php');
define('PATH_DATA',      $path . 'Data.php');

/**
 * Class Hoa\Core\Consistency.
 *
 * This class manages all classes, importations etc.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @author     Julien Bianchi <julien.bianchi@hoa-project.net>
 * @copyright  Copyright © 2007-2014 Ivan Enderlin, Julien Bianchi.
 * @license    New BSD License
 */

class Consistency {

    /**
     * One singleton by library family.
     *
     * @var \Hoa\Core\Consistency array
     */
    private static $_multiton = array();

    /**
     * Libraries to considere.
     *
     * @var \Hoa\Core\Consistency array
     */
    protected $_from          = null;

    /**
     * Library's roots to considere.
     *
     * @var \Hoa\Core\Consistency array
     */
    protected $_roots         = array();

    /**
     * Cache all imports.
     *
     * @var \Hoa\Core\Consistency array
     */
    protected static $_cache  = array();

    /**
     * Cache all classes informations: path, alias and imported.
     *
     * @var \Hoa\Core\Consistency array
     */
    protected static $_class  = array(
        // Hard-preload.
        'Hoa\Core\Event'     => array(
            'path'  => PATH_EVENT,
            'alias' => null
        ),
        'Hoa\Core\Exception' => array(
            'path'  => PATH_EXCEPTION,
            'alias' => null
        ),
        'Hoa\Core\Data'      => array(
            'path'  => PATH_DATA,
            'alias' => null
        )
    );

    /**
     * Cache all classes from the current library family.
     * It contains references to self:$_class.
     *
     * @var \Hoa\Core\Consistency array
     */
    protected $__class        = array();



    /**
     * Singleton to manage a library family.
     *
     * @access  public
     * @param   string  $from    Library family's name.
     * @return  void
     */
    private function __construct ( $from ) {

        $this->_from = preg_split('#\s*(,|or)\s*#', trim($from, '()'));
        $parameters  = \Hoa\Core::getInstance()->getParameters();
        $wildcard    = $parameters->getFormattedParameter('namespace.prefix.*');

        foreach($this->_from as $f)
            $this->setRoot(
                $parameters->getFormattedParameter('namespace.prefix.' . $f)
                ?: $wildcard,
                $f
            );

        return;
    }

    /**
     * Get the library family's singleton.
     *
     * @access  public
     * @param   string  $namespace    Library family's name.
     * @return  \Hoa\Core\Consistency
     */
    public static function from ( $namespace ) {

        if(!isset(static::$_multiton[$namespace]))
            static::$_multiton[$namespace] = new static($namespace);

        return static::$_multiton[$namespace];
    }

    /**
     * Import a class, an interface or a trait.
     *
     * @access  public
     * @param   string  $pattern    Pattern.
     * @param   bool    $load       Whether loading directly or not.
     * @return  \Hoa\Core\Consistency
     */
    public function import ( $pattern, $load = false ) {

        foreach($this->_from as $from)
            $this->_import($from . '.' . $pattern, $load);

        return $this;
    }

    /**
     * Iterate over each solution found by an import.
     *
     * @access  public
     * @param   string    $pattern     Pattern.
     * @param   callable  $callback    Callback (also disable cache).
     * @return  \Hoa\Core\Consistency
     */
    public function foreachImport ( $pattern, $callback ) {

        foreach($this->_from as $from)
            $this->_import($from . '.'. $pattern, false, $callback);

        return $this;
    }

    /**
     * Real import implementation.
     *
     * @access  protected
     * @param   string    $pattern     Pattern.
     * @param   bool      $load        Whether loading directly or not.
     * @param   callable  $callback    Callback.
     * @return  bool
     */
    protected function _import ( $pattern, $load, $callback = null ) {

        $parts = explode('.', $pattern);

        if(!isset($parts[1]))
            return false;

        if(false !== strpos($pattern, '~')) {

            $handle = null;

            foreach($parts as &$part) {

                if(null !== $handle && '*' !== $handle)
                    $part = str_replace('~', $handle, $part);

                $handle = $part;
            }
        }

        if(false !== strpos($pattern, '*')) {

            if('Hoa' !== $parts[0] && 'Hoathis' !== $parts[0])
                return false;

            $glob     = new \AppendIterator();
            $ds       = preg_quote(DS);
            $_pattern = '#' . $ds . $parts[0] . $ds . $parts[1] . $ds . '?$#i';

            foreach(resolve('hoa://Library/' . $parts[1], true, true) as $path)
                if(0 !== preg_match($_pattern, $path))
                    $glob->append(new \CallbackFilterIterator(
                        new \GlobIterator(
                            $path . DS . implode(DS, array_slice($parts, 2)) . '.php',
                            \GlobIterator::KEY_AS_PATHNAME
                          | \GlobIterator::CURRENT_AS_SELF
                          | \GlobIterator::SKIP_DOTS
                        ),
                        function ( $current, $key ) use ( $path, $parts ) {

                            $current->__hoa_pattern =
                                $parts[0] .
                                '.' .
                                $parts[1] .
                                '.' .
                                str_replace(
                                    DS,
                                    '.',
                                    substr($key, strlen($path) + 1, -4)
                                );

                            return true;
                        }
                    ));

            $out = true;

            foreach($glob as $filesystem)
                $out &= $this->_import($filesystem->__hoa_pattern, $load, $callback);

            return (bool) $out;
        }

        $classname = implode('\\', $parts);
        $imported  = array_key_exists($classname, static::$_class);

        if(false === $imported) {

            static::$_class[$classname] = array(
                'path'  => null,
                'alias' => null
            );

            $count = count($parts);

            if($parts[$count - 2] === $parts[$count - 1]) {

                $alias = implode('\\', array_slice($parts, 0, -1));

                static::$_class[$classname]['alias'] = $alias;
                static::$_class[$alias]              = $classname;
                $this->__class[$alias]               = &static::$_class[$alias];
            }
        }

        $this->__class[$classname] = &static::$_class[$classname];

        if(   true  === $load
           && false === static::entityExists($classname, false)) {

            spl_autoload_call($classname);

            if(   null !== $callback
               && true === static::entityExists($classname, false))
                $callback($classname);

            return true;
        }

        if(null !== $callback)
            $callback($classname);

        return true;
    }

    /**
     * Autoloader.
     *
     * @access  public
     * @param   string  $classname    Classname.
     * @return  bool
     */
    public static function autoload ( $classname ) {

        if(false === strpos($classname, '\\'))
            if(false === strpos($classname, '_'))
                return false;
            else
                $classname = str_replace('_', '\\', $classname);

        $classname = ltrim($classname, '\\');

        // Hard-preload.
        if(   'Hoa\Core' === substr($classname, 0, 8)
           &&      false !== ($pos = strpos($classname, '\\', 10))
           &&    'Bin\\' !== substr($classname, 9, 4)) {

            require static::$_class[substr($classname, 0, $pos)]['path'];

            return true;
        }

        $head = substr($classname, 0, strpos($classname, '\\'));

        if(false === array_key_exists($classname, static::$_class)) {

            $_classname = str_replace('\\', '.', $classname);
            $out = from($head)->_import($_classname, true);

            if(false === static::entityExists($classname))
                $out = from($head)->_import($_classname . '.~', true);

            return $out;
        }
        elseif(is_string($original = static::$_class[$classname])) {

            spl_autoload_call($original);

            return true;
        }

        $roots             = from($head)->getRoot();
        $classpath         = str_replace('\\', DS, $classname) . '.php';
        $classpathExtended = str_replace(
            '\\',
            DS,
            $classname . substr($classname, strrpos('\\', $classname, 1))
        ) . '.php';

        $gotcha = false;

        foreach($roots as $vendor => $_roots)
            foreach($_roots as $root)
                if(   true === file_exists($path = $root . $classpath)
                   || true === file_exists($path = $root . $classpathExtended)) {

                    $gotcha = true;
                    require $path;
                    static::$_class[$classname]['path'] = $path;

                    break 2;
                }

        return $gotcha;
    }

    /**
     * Dynamic new, i.e. a native factory (import + load + instance).
     *
     * @access  public
     * @param   string  $classname    Classname.
     * @param   array   $arguments    Constructor's arguments.
     * @return  object
     * @throw   \Hoa\Core\Exception
     */
    public static function dnew ( $classname, Array $arguments = array() ) {

        $classname = ltrim($classname, '\\');

        if(!class_exists($classname, false)) {

            $head = substr($classname, 0, $pos = strpos($classname, '\\'));
            $tail = str_replace('\\', '.', substr($classname, $pos + 1));
            $from = from($head);

            foreach(array($tail, $tail . '.~') as $_tail)
                foreach($from->getFroms() as $_from) {

                    $break = false;
                    $from->_import(
                        $_from . '.' . $_tail,
                        true,
                        function ( $_classname ) use ( &$break, &$classname ) {

                            $classname = $_classname;
                            $break     = true;
                        }
                    );

                    if(true === $break)
                        break 2;
                }
        }

        $class = new \ReflectionClass($classname);

        if(empty($arguments) || false === $class->hasMethod('__construct'))
            return $class->newInstance();

        return $class->newInstanceArgs($arguments);
    }

    /**
     * Set the root of the current library family.
     *
     * @access  public
     * @param   bool    $root    Root.
     * @param   string  $from    Library family's name (if null, first family
     *                           will be choosen).
     * @return  \Hoa\Core\Consistency
     */
    public function setRoot ( $root, $from = null ) {

        if(null === $from)
            $from = $this->_from[0];

        $this->_roots[$from] = explode(';', $root);

        return $this;
    }

    /**
     * Get roots of the current library family.
     *
     * @access  public
     * @return  array
     */
    public function getRoot ( ) {

        return $this->_roots;
    }

    /**
     * Get froms.
     *
     * @access  public
     * @return  array
     */
    public function getFroms ( ) {

        return $this->_from;
    }

    /**
     * Get imported classes from the current library family.
     *
     * @access  public
     * @return  array
     */
    public function getImportedClasses ( ) {

        return $this->__class;
    }

    /**
     * Get imported classes from all library families.
     *
     * @access  public
     * @return  array
     */
    public static function getAllImportedClasses ( ) {

        return static::$_class;
    }

    /**
     * Get the shortest name for an entity.
     *
     * @access  public
     * @param   string  $entityName    Entity name.
     * @return  string
     */
    public static function getEntityShortestName ( $entityName ) {

        $parts = explode('\\', $entityName);
        $count = count($parts);

        if($parts[$count - 2] === $parts[$count - 1])
            return implode('\\', array_slice($parts, 0, -1));

        return $entityName;
    }

    /**
     * Check if an entity exists (class, interface, trait…).
     *
     * @access  public
     * @param   string  $entityName    Entity name.
     * @param   bool    $autoloader    Run autoloader if necessary.
     * @return  bool
     */
    public static function entityExists ( $entityName, $autoloader = false ) {

        return    class_exists($entityName, $autoloader)
               || interface_exists($entityName, false)
               || trait_exists($entityName, false);
    }

    /**
     * Declare a flex entity (for nested library).
     *
     * @access  public
     * @param   string  $entityName    Entity name.
     * @return  bool
     */
    public static function flexEntity ( $entityName ) {

        return class_alias(
            $entityName,
            static::getEntityShortestName($entityName)
        );
    }

    /**
     * Whether a word is reserved or not.
     *
     * @access  public
     * @param   string  $word    Word.
     * @return  void
     */
    public static function isKeyword ( $word ) {

        static $_list = array(
            // PHP keywords.
            '__halt_compiler', 'abstract',     'and',           'array',
            'as',              'break',        'callable',      'case',
            'catch',           'class',        'clone',         'const',
            'continue',        'declare',      'default',       'die',
            'do',              'echo',         'else',          'elseif',
            'empty',           'enddeclare',   'endfor',        'endforeach',
            'endif',           'endswitch',    'endwhile',      'eval',
            'exit',            'extends',      'final',         'for',
            'foreach',         'function',     'global',        'goto',
            'if',              'implements',   'include',       'include_once',
            'instanceof',      'insteadof',    'interface',     'isset',
            'list',            'namespace',    'new',           'or',
            'print',           'private',      'protected',     'public',
            'require',         'require_once', 'return',        'static',
            'switch',          'throw',        'trait',         'try',
            'unset',           'use',          'var',           'while',
            'xor',             'yield',
            // Compile-time constants.
            '__class__',       '__dir__',      '__file__',      '__function__',
            '__line__',        '__method__',   '__namespace__', '__trait__'
        );

        return in_array(strtolower($word), $_list);
    }

    /**
     * Whether an ID is a valid PHP identifier.
     *
     * @access  public
     * @param   string  $id    ID.
     * @return  bool
     */
    public static function isIdentifier ( $id ) {

        return 0 !== preg_match(
            '#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#',
            $id
        );
    }
}

/**
 * Class Hoa\Core\Consistency\Xcallable.
 *
 * Build a callable object, i.e. function, class::method, object->method or
 * closure, they all have the same behaviour. This callable is an extension of
 * native PHP callable (aka callback) to integrate Hoa's structures.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2014 Ivan Enderlin.
 * @license    New BSD License
 */

class Xcallable {

    /**
     * Callback, with the PHP format.
     *
     * @var \Hoa\Core\Consistency\Xcallable mixed
     */
    protected $_callback = null;

    /**
     * Callable hash.
     *
     * @var \Hoa\Core\Consistency\Xcallable string
     */
    protected $_hash     = null;



    /**
     * Build a callback.
     * Accepted forms:
     *     • 'function';
     *     • 'class::method';
     *     • 'class', 'method';
     *     • $object, 'method';
     *     • $object, '';
     *     • function ( … ) { … };
     *     • array('class', 'method');
     *     • array($object, 'method').
     *
     * @access  public
     * @param   mixed   $call    First callable part.
     * @param   mixed   $able    Second callable part (if needed).
     * @return  mixed
     */
    public function __construct ( $call, $able = '' ) {

        if(null === $call)
            return null;

        if($call instanceof \Closure) {

            $this->_callback = $call;

            return;
        }

        if(!is_string($able))
            throw new \Hoa\Core\Exception(
                'Bad callback form.', 0);

        if('' === $able)
            if(is_string($call)) {

                if(false === strpos($call, '::')) {

                    if(!function_exists($call))
                        throw new \Hoa\Core\Exception(
                            'Bad callback form.', 1);

                    $this->_callback = $call;

                    return;
                }

                list($call, $able) = explode('::', $call);
            }
            elseif(is_object($call)) {

                if($call instanceof \Hoa\Stream\IStream\Out)
                    $able = null;
                elseif(method_exists($call, '__invoke'))
                    $able = '__invoke';
                else
                    throw new \Hoa\Core\Exception(
                        'Bad callback form.', 2);
            }
            elseif(is_array($call) && isset($call[0])) {

                if(!isset($call[1]))
                    return $this->__construct($call[0]);

                return $this->__construct($call[0], $call[1]);
            }
            else
                throw new \Hoa\Core\Exception(
                    'Bad callback form.', 3);

        $this->_callback = array($call, $able);

        return;
    }

    /**
     * Call the callable.
     *
     * @access  public
     * @param   ...
     * @return  mixed
     */
    public function __invoke ( ) {

        $arguments = func_get_args();
        $valid     = $this->getValidCallback($arguments);

        return call_user_func_array($valid, $arguments);
    }

    /**
     * Distribute arguments according to an array.
     *
     * @access  public
     * @param   array  $arguments    Arguments.
     * @return  mixed
     */
    public function distributeArguments ( Array $arguments ) {

        return call_user_func_array(array($this, '__invoke'), $arguments);
    }

    /**
     * Get a valid callback in the PHP meaning.
     *
     * @access  public
     * @param   array   &$arguments    Arguments (could determine method on an
     *                                 object if not precised).
     * @return  mixed
     */
    public function getValidCallback ( Array &$arguments = array() ) {

        $callback = $this->_callback;
        $head     = null;

        if(isset($arguments[0]))
            $head = &$arguments[0];

        // If method is undetermined, we find it (we understand event bucket and
        // stream).
        if(   null !== $head
           && is_array($callback)
           && null === $callback[1]) {

            if($head instanceof \Hoa\Core\Event\Bucket)
                $head = $head->getData();

            switch($type = gettype($head)) {

                case 'string':
                    if(1 === strlen($head))
                        $method = 'writeCharacter';
                    else
                        $method = 'writeString';
                  break;

                case 'boolean':
                case 'integer':
                case 'array':
                    $method = 'write' . ucfirst($type);
                  break;

                case 'double':
                    $method = 'writeFloat';
                  break;

                default:
                    $method = 'writeAll';
                    $head   = $head . "\n";
            }

            $callback[1] = $method;
        }

        return $callback;
    }

    /**
     * Get hash.
     * Will produce:
     *     * function#…;
     *     * class#…::…;
     *     * object(…)#…::…;
     *     * closure(…).
     *
     * @access  public
     * @return  string
     */
    public function getHash ( ) {

        if(null !== $this->_hash)
            return $this->_hash;

        $_ = &$this->_callback;

        if(is_string($_))
            return $this->_hash = 'function#' . $_;

        if(is_array($_))
            return $this->_hash =
                       (is_object($_[0])
                           ? 'object(' . spl_object_hash($_[0]) . ')' .
                             '#' . get_class($_[0])
                           : 'class#' . $_[0]) .
                       '::' .
                       (null !== $_[1]
                           ? $_[1]
                           : '???');

        return $this->_hash = 'closure(' . spl_object_hash($_) . ')';
    }

    /**
     * Get appropriated reflection instance.
     *
     * @access  public
     * @param   ...
     * @return  \Reflector
     */
    public function getReflection ( ) {

        $arguments = func_get_args();
        $valid     = $this->getValidCallback($arguments);

        if(is_string($valid))
            return new \ReflectionFunction($valid);

        if($valid instanceof \Closure)
            return new \ReflectionFunction($valid);

        if(is_array($valid)) {

            if(is_string($valid[0]))
                return new \ReflectionMethod($valid[0], $valid[1]);

            $object = new \ReflectionObject($valid[0]);

            if(null === $valid[1])
                return $object;

            return $object->getMethod($valid[1]);
        }
    }

    /**
     * Return the hash.
     *
     * @access  public
     * @return  string
     */
    public function __toString ( ) {

        return $this->getHash();
    }
}

}

namespace {

/**
 * Alias of function_exists().
 *
 * @access  public
 * @param   string  $name    Name.
 * @return  bool
 */
function ƒ ( $name ) {

    return function_exists($name);
}

/**
 * Implement a fake trait_exists function.
 *
 * @access  public
 * @param   string  $traitname    Traitname.
 * @param   bool    $autoload     Autoload.
 * @return  bool
 */
if(!ƒ('trait_exists')) {
function trait_exists ( $traitname, $autoload = true ) {

    if(true == $autoload)
        class_exists($traitname, true);

    return false;
}}

/**
 * Alias for \Hoa\Core\Consistency::from().
 *
 * @access  public
 * @param   string  $namespace    Library family's name.
 * @return  \Hoa\Core\Consistency
 */
if(!ƒ('from')) {
function from ( $namespace ) {

    return \Hoa\Core\Consistency::from($namespace);
}}

/**
 * Alias of \Hoa\Core\Consistency::dnew().
 *
 * @access  public
 * @param   string  $classname    Classname.
 * @param   array   $arguments    Constructor's arguments.
 * @return  object
 */
if(!ƒ('dnew')) {
function dnew ( $classname, Array $arguments = array() ) {

    return \Hoa\Core\Consistency::dnew($classname, $arguments);
}}

/**
 * Alias of \Hoa\Core\Consistency\Xcallable.
 *
 * @access  public
 * @param   mixed   $call    First callable part.
 * @param   mixed   $able    Second callable part (if needed).
 * @return  mixed
 */
if(!ƒ('xcallable')) {
function xcallable ( $call, $able = '' ) {

    if($call instanceof \Hoa\Core\Consistency\Xcallable)
        return $call;

    return new \Hoa\Core\Consistency\Xcallable($call, $able);
}}

/**
 * Curry.
 * Example:
 *     $c = curry('str_replace', …, …, 'foobar');
 *     var_dump($c('foo', 'baz')); // bazbar
 *     $c = curry('str_replace', 'foo', 'baz', …);
 *     var_dump($c('foobarbaz')); // bazbarbaz
 * Nested curries also work:
 *     $c1 = curry('str_replace', …, …, 'foobar');
 *     $c2 = curry($c1, 'foo', …);
 *     var_dump($c2('baz')); // bazbar
 * Obviously, as the first argument is a callable, we can combine this with
 * \Hoa\Core\Consistency\Xcallable ;-).
 * The “…” character is the HORIZONTAL ELLIPSIS Unicode character (Unicode:
 * 2026, UTF-8: E2 80 A6).
 *
 * @access  public
 * @param   mixed  $callable    Callable (two parts).
 * @param   ...    ...          Arguments.
 * @return  \Closure
 */
if(!ƒ('curry')) {
function curry ( $callable ) {

    $arguments = func_get_args();
    array_shift($arguments);
    $ii        = array_keys($arguments, …, true);

    return function ( ) use ( $callable, $arguments, $ii ) {

        return call_user_func_array(
            $callable,
            array_replace($arguments, array_combine($ii, func_get_args()))
        );
    };
}}

/**
 * Same as curry() but where all arguments are references.
 *
 * @access  public
 * @param   mixed  &$callable    Callable (two parts).
 * @param   ...    &...          Arguments.
 * @return  \Closure
 */
if(!ƒ('curry_ref')) {
function curry_ref ( &$callable, &$a = null, &$b = null, &$c = null, &$d = null,
                                 &$e = null, &$f = null, &$g = null, &$h = null,
                                 &$i = null, &$j = null, &$k = null, &$l = null,
                                 &$m = null, &$n = null, &$o = null, &$p = null,
                                 &$q = null, &$r = null, &$s = null, &$t = null,
                                 &$u = null, &$v = null, &$w = null, &$x = null,
                                 &$y = null, &$z = null ) {

    $arguments = array();

    for($i = 0, $max = func_num_args() - 1; $i < $max; ++$i)
        $arguments[] = &${chr(97 + $i)};

    $ii = array_keys($arguments, …, true);

    return function ( ) use ( &$callable, &$arguments, $ii ) {

        return call_user_func_array(
            $callable,
            array_replace($arguments, array_combine($ii, func_get_args()))
        );
    };
}}

/**
 * Make the alias automatically (because it's not imported with the import()
 * function).
 */
class_alias('Hoa\Core\Consistency\Consistency', 'Hoa\Core\Consistency');

/**
 * Set autoloader.
 */
spl_autoload_register('\Hoa\Core\Consistency::autoload');

}
