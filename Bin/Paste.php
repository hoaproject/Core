<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Ivan Enderlin. All rights reserved.
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

namespace Hoa\Core\Bin {

/**
 * Class \Hoa\Core\Bin\Paste.
 *
 * Paste something somewhere.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2015 Ivan Enderlin.
 * @license    New BSD License
 */

class Paste extends \Hoa\Console\Dispatcher\Kit {

    /**
     * Options description.
     *
     * @var \Hoa\Core\Bin\Paste array
     */
    protected $options = array(
        array('address', \Hoa\Console\GetOption::REQUIRED_ARGUMENT, 'a'),
        array('title',   \Hoa\Console\GetOption::REQUIRED_ARGUMENT, 't'),
        array('help',    \Hoa\Console\GetOption::NO_ARGUMENT,       'h'),
        array('help',    \Hoa\Console\GetOption::NO_ARGUMENT,       '?')
    );



    /**
     * The entry method.
     *
     * @access  public
     * @return  int
     */
    public function main ( ) {

        $address = 'paste.hoa-project.net:80';
        $title   = 'Untitled';

        while(false !== $c = $this->getOption($v)) switch($c) {

            case 'a':
                $address = $v;
              break;
            
            case 't':
                $title = $v;
              break;

            case 'h':
            case '?':
                return $this->usage();
              break;

            case '__ambiguous':
                $this->resolveOptionAmbiguity($v);
              break;
        }
        $input   = file_get_contents('php://stdin');
        $input   = http_build_query(array(
            'title' => $title,
            'content' => $input
            ));


        $context = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Host: ' . $address . "\r\n" .
                             'User-Agent: Hoa' . "\r\n" .
                             'Accept: */*' . "\r\n" .
                             'Content-type: application/x-www-form-urlencoded' . "\r\n" .
                             'Content-Type: text/plain' . "\r\n",
                'content' => $input
            )
        ));

        echo file_get_contents('http://' . $address, false, $context), "\n";

        return;
    }

    /**
     * The command usage.
     *
     * @access  public
     * @return  int
     */
    public function usage ( ) {

        echo 'Usage   : core:paste <options>', "\n",
             'Options :', "\n",
             $this->makeUsageOptionsList(array(
                 'a'    => 'Address to the paste server.',
                 't'    => 'Title for the paste',
                 'help' => 'This help.'
             )), "\n";

        return;
    }
}

}

__halt_compiler();
Paste something somewhere.
