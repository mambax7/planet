<?php
//
// ------------------------------------------------------------------------ //
// This program is free software; you can redistribute it and/or modify     //
// it under the terms of the GNU General Public License as published by     //
// the Free Software Foundation; either version 2 of the License, or        //
// (at your option) any later version.                                      //
//                                                                          //
// You may not change or alter any portion of this comment or credits       //
// of supporting developers from this source code or any supporting         //
// source code which is considered copyrighted (c) material of the          //
// original comment or credit authors.                                      //
//                                                                          //
// This program is distributed in the hope that it will be useful,          //
// but WITHOUT ANY WARRANTY; without even the implied warranty of           //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
// GNU General Public License for more details.                             //
//                                                                          //
// You should have received a copy of the GNU General Public License        //
// along with this program; if not, write to the Free Software              //
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
// ------------------------------------------------------------------------ //
// Author: phppp (D.J., infomax@gmail.com)                                  //
// URL: https://xoops.org                         //
// Project: Article Project                                                 //
// ------------------------------------------------------------------------ //

use XoopsModules\Planet;

// require_once __DIR__ . '/../class/Helper.php';
//require_once __DIR__ . '/../include/common.php';
$helper = Planet\Helper::getInstance();

$pathIcon32 = \Xmf\Module\Admin::menuIconPath('');
$pathModIcon32 = $helper->getModule()->getInfo('modicons32');

$adminmenu[] = [
    'title' => planet_constant('MI_ADMENU_HOME'),
    'link'  => 'admin/index.php',
    'icon'  => $pathIcon32 . '/home.png',
];

$adminmenu[] = [
    'title' => planet_constant('MI_ADMENU_INDEX'),
    'link'  => 'admin/main.php',
    'icon'  => $pathIcon32 . '/manage.png',
];

$adminmenu[] = [
    'title' => planet_constant('MI_ADMENU_CATEGORY'),
    'link'  => 'admin/admin.category.php',
    'icon'  => $pathIcon32 . '/category.png',
];

$adminmenu[] = [
    'title' => planet_constant('MI_ADMENU_BLOG'),
    'link'  => 'admin/admin.blog.php',
    'icon'  => $pathIcon32 . '/translations.png',
];

$adminmenu[] = [
    'title' => planet_constant('MI_ADMENU_ARTICLE'),
    'link'  => 'admin/admin.article.php',
    'icon'  => $pathIcon32 . '/content.png',
];

//$adminmenu[] = [
//'title' =>  planet_constant("MI_ADMENU_BLOCK"),
//'link' =>  "admin/admin.block.php",
//$adminmenu[$i]["icon"]  = $pathIcon32 . '/manage.png';
//];

$adminmenu[] = [
    'title' => planet_constant('MI_ADMENU_ABOUT'),
    'link'  => 'admin/about.php',
    'icon'  => $pathIcon32 . '/about.png',
];
