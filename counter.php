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
use Xmf\Request;

include __DIR__ . '/header.php';
$article_id = Request::getInt('article', 0, 'GET');//empty($_GET['article']) ? 0 : (int)$_GET['article'];
if (empty($article_id)) {
    return;
}
if (planetGetCookie('art_' . $article_id) > 0) {
    return;
}
$articleHandler = xoops_getModuleHandler('article', $xoopsModule->getVar('dirname'));
$article_obj    = $articleHandler->get($article_id);
$article_obj->setVar('art_views', $article_obj->getVar('art_views') + 1, true);
$articleHandler->insert($article_obj, true);
PlanetUtility::planetSetCookie('art_' . $article_id, time());

return;
