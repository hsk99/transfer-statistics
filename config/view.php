<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\view\Raw;
use support\view\Twig;
use support\view\Blade;
use support\view\ThinkPHP;

return [
    'handler' => ThinkPHP::class,
    'options' => [
        'default_filter'     => '',
        'tpl_cache'          => false,
        'tpl_replace_string' => [
            '__STATIC__'  => '/static',
            '__IMAGES__'  => '/static/images',
            '__JS__'      => '/static/js',
            '__CSS__'     => '/static/css',
            '__PACKAGE__' => '/static/package',
        ]
    ]
];
