<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Hoa community. All rights reserved.
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

namespace Hoa\Core {

/**
 * Check if Hoa was well-included.
 */
!(
    !defined('HOA') and define('HOA', true)
)
and
    exit('Hoa main file (Core.php) must be included once.');

(
    !defined('PHP_VERSION_ID') or PHP_VERSION_ID < 50400
)
and
    exit('Hoa needs at least PHP5.4 to work; you have ' . phpversion() . '.');

/**
 * \Hoa\Core\Parameter
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Parameter.php';

/**
 * \Hoa\Core\Protocol
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Protocol.php';

/**
 * \Hoa\Core\Data.
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Data.php';

/**
 * Class \Hoa\Core.
 *
 * \Hoa\Core is the base of all libraries.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Core implements Parameter\Parameterizable
{
    /**
     * Tree of components, starts by the root.
     *
     * @var \Hoa\Core\Protocol\Root
     */
    private static $_root     = null;

    /**
     * Parameters.
     *
     * @var \Hoa\Core\Parameter
     */
    protected $_parameters    = null;

    /**
     * Singleton.
     *
     * @var \Hoa\Core
     */
    private static $_instance = null;



    /**
     * Singleton.
     *
     * @return  void
     */
    private function __construct()
    {
        if (false !== $wl = ini_get('suhosin.executor.include.whitelist')) {
            if (false === in_array('hoa', explode(',', $wl))) {
                throw new Exception(
                    'The URL scheme hoa:// is not authorized by Suhosin. ' .
                    'You must add this to your php.ini or suhosin.ini: ' .
                    'suhosin.executor.include.whitelist="%s", thanks :-).',
                    0,
                    implode(
                        ',',
                        array_merge(
                            preg_split('#,#', $wl, -1, PREG_SPLIT_NO_EMPTY),
                            ['hoa']
                        )
                    )
                );
            }
        }

        if (true === function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        if (true === function_exists('mb_regex_encoding')) {
            mb_regex_encoding('UTF-8');
        }

        return;
    }

    /**
     * Singleton.
     *
     * @return  \Hoa\Core
     */
    public static function getInstance()
    {
        if (null === static::$_instance) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * Initialize the core.
     *
     * @param   array   $parameters    Parameters of \Hoa\Core.
     * @return  \Hoa\Core
     */
    public function initialize(Array $parameters = [])
    {
        $root = dirname(dirname(__DIR__));
        $cwd  =
            'cli' === PHP_SAPI
                ? dirname(realpath($_SERVER['argv'][0]))
                : getcwd();
        $this->_parameters = new Parameter\Parameter(
            $this,
            [
                'root' => $root,
                'cwd'  => $cwd
            ],
            [
                'root.hoa'         => '(:root:)',
                'root.application' => '(:cwd:h:)',
                'root.data'        => '(:%root.application:h:)' . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR,

                'protocol.Application'            => '(:%root.application:)' . DIRECTORY_SEPARATOR,
                'protocol.Application/Public'     => 'Public' . DIRECTORY_SEPARATOR,
                'protocol.Data'                   => '(:%root.data:)',
                'protocol.Data/Etc'               => 'Etc' . DIRECTORY_SEPARATOR,
                'protocol.Data/Etc/Configuration' => 'Configuration' . DIRECTORY_SEPARATOR,
                'protocol.Data/Etc/Locale'        => 'Locale' . DIRECTORY_SEPARATOR,
                'protocol.Data/Library'           => 'Library' . DIRECTORY_SEPARATOR . 'Hoathis' . DIRECTORY_SEPARATOR . ';' .
                                                     'Library' . DIRECTORY_SEPARATOR . 'Hoa' . DIRECTORY_SEPARATOR,
                'protocol.Data/Lost+found'        => 'Lost+found' . DIRECTORY_SEPARATOR,
                'protocol.Data/Temporary'         => 'Temporary' . DIRECTORY_SEPARATOR,
                'protocol.Data/Variable'          => 'Variable' . DIRECTORY_SEPARATOR,
                'protocol.Data/Variable/Cache'    => 'Cache' . DIRECTORY_SEPARATOR,
                'protocol.Data/Variable/Database' => 'Database' . DIRECTORY_SEPARATOR,
                'protocol.Data/Variable/Log'      => 'Log' . DIRECTORY_SEPARATOR,
                'protocol.Data/Variable/Private'  => 'Private' . DIRECTORY_SEPARATOR,
                'protocol.Data/Variable/Run'      => 'Run' . DIRECTORY_SEPARATOR,
                'protocol.Data/Variable/Test'     => 'Test' . DIRECTORY_SEPARATOR,
                'protocol.Library'                => '(:%protocol.Data:)Library' . DIRECTORY_SEPARATOR . 'Hoathis' . DIRECTORY_SEPARATOR . ';' .
                                                     '(:%protocol.Data:)Library' . DIRECTORY_SEPARATOR . 'Hoa' . DIRECTORY_SEPARATOR . ';' .
                                                     '(:%root.hoa:)' . DIRECTORY_SEPARATOR . 'Hoathis' . DIRECTORY_SEPARATOR . ';' .
                                                     '(:%root.hoa:)' . DIRECTORY_SEPARATOR . 'Hoa' . DIRECTORY_SEPARATOR,

                'namespace.prefix.*'           => '(:%protocol.Data:)Library' . DIRECTORY_SEPARATOR . ';' . '(:%root.hoa:)' . DIRECTORY_SEPARATOR,
                'namespace.prefix.Application' => '(:%root.application:h:)' . DIRECTORY_SEPARATOR,
            ]
        );

        $this->_parameters->setKeyword('root', $root);
        $this->_parameters->setKeyword('cwd',  $cwd);
        $this->_parameters->setParameters($parameters);
        $this->setProtocol();

        return $this;
    }

    /**
     * Get parameters.
     *
     * @return  \Hoa\Core\Parameter
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Set protocol according to the current parameter.
     *
     * @param   string  $path     Path (e.g. hoa://Data/Temporary).
     * @param   string  $reach    Reach value.
     * @return  void
     */
    public function setProtocol($path = null, $reach = null)
    {
        $root = static::getProtocol();

        if (null === $path && null === $reach) {
            if (!isset($root['Library'])) {
                static::$_root = null;
                $root          = static::getProtocol();
            }

            $protocol = $this->getParameters()->unlinearizeBranche('protocol');

            foreach ($protocol as $components => $reach) {
                $parts  = explode('/', trim($components, '/'));
                $last   = array_pop($parts);
                $handle = $root;

                foreach ($parts as $part) {
                    $handle = $handle[$part];
                }

                if ('Library' === $last) {
                    $handle[] = new Protocol\Library($last, $reach);
                } else {
                    $handle[] = new Protocol\Generic($last, $reach);
                }
            }

            return;
        }

        if ('hoa://' === substr($path, 0, 6)) {
            $path = substr($path, 6);
        }

        $path   = trim($path, '/');
        $parts  = explode('/', $path);
        $handle = $root;

        foreach ($parts as $part) {
            $handle = $handle[$part];
        }

        $handle->setReach($reach);
        $root->clearCache();
        $this->getParameters()->setParameter('protocol.' . $path, $reach);

        return;
    }

    /**
     * Get protocol's root.
     *
     * @return  \Hoa\Core\Protocol\Root
     */
    public static function getProtocol()
    {
        if (null === static::$_root) {
            static::$_root = new Protocol\Root();
        }

        return static::$_root;
    }

    /**
     * Return the copyright and license of Hoa.
     *
     * @return  string
     */
    public static function ©()
    {
        return
            'Copyright © 2007-2015 Ivan Enderlin. All rights reserved.' . "\n" .
            'New BSD License.';
    }
}

}

namespace {

/**
 * Alias.
 */
class_alias('Hoa\Core\Core', 'Hoa\Core');

/**
 * Then, initialize Hoa.
 */
Hoa\Core::getInstance()->initialize();

}
