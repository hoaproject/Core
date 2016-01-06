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
 * \Hoa\Core\Parameter
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Parameter.php';

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
