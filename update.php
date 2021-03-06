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
use XoopsModules\Planet;
/** @var Planet\Helper $helper */
$helper = Planet\Helper::getInstance();

include __DIR__ . '/header.php';

$blog_id     = Request::getInt('blog', Request::getInt('blog', 0, 'POST'), 'GET'); //(int)(!empty($_POST['blog']) ? $_POST['blog'] : (!empty($_GET['blog']) ? $_GET['blog'] : 0));
$blogHandler = xoops_getModuleHandler('blog', $GLOBALS['moddirname']);
if ($blog_id > 0) {
    $blog  = $blogHandler->get($blog_id);
    $count = $blogHandler->do_update($blog);
    redirect_header(XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php?blog=' . $blog_id, 2, sprintf(planet_constant('MD_UPDATED'), (int)$count));
}
if (planetGetCookie('upd') + 30 * 60 > time()) {
    return;
}
PlanetUtility::planetSetCookie('upd', time());
$start = 0;
@include XOOPS_CACHE_PATH . '/' . $xoopsModule->getVar('dirname') . '_update.php';
$criteria = new \Criteria('blog_status', 0, '>');
$criteria->setSort('blog_id');
$criteria->setStart($start);
$criteria->setLimit($helper->getConfig('blogs_perupdate'));
$blogs = $blogHandler->getAll($criteria);
foreach (array_keys($blogs) as $id) {
    $blogHandler->do_update($blogs[$id]);
}
$start += count($blogs);
if (count($blogs) < $helper->getConfig('blogs_perupdate')) {
    $start = 0;
}
$fp = fopen(XOOPS_CACHE_PATH . '/' . $xoopsModule->getVar('dirname') . '_update.php', 'w');
if (!$fp) {
    return;
}
fwrite($fp, "<?php\n \$start=" . (int)$start . ";\n?>");
fclose($fp);
