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

include __DIR__ . '/header.php';

/** @var Planet\Helper $helper */
$helper = Planet\Helper::getInstance();

$rate       = Request::getInt('rate', Request::getInt('rate', 0, 'POST'), 'GET'); //(int)(!empty($_POST['rate']) ? $_POST['rate'] : (!empty($_GET['rate']) ? $_GET['rate'] : 0));
$article_id = Request::getInt('article', Request::getInt('article', 0, 'POST'), 'GET'); //(int)(!empty($_POST['article']) ? $_POST['article'] : (!empty($_GET['article']) ? $_GET['article'] : 0));
$blog_id    = Request::getInt('blog', Request::getInt('blog', 0, 'POST'), 'GET'); //(int)(!empty($_POST['blog']) ? $_POST['blog'] : (!empty($_GET['blog']) ? $_GET['blog'] : 0));

if (empty($article_id) && empty($blog_id)) {
    redirect_header('javascript:history.go(-1);', 1, planet_constant('MD_INVALID'));
}

$articleHandler = xoops_getModuleHandler('article', $GLOBALS['moddirname']);
$blogHandler    = xoops_getModuleHandler('blog', $GLOBALS['moddirname']);
if (empty($helper->getConfig('anonymous_rate')) && !is_object($xoopsUser)) {
    $message = planet_constant('MD_NOACCESS');
} else {
    $uid = is_object($xoopsUser) ? $xoopsUser->getVar('uid') : 0;
    $ip  = PlanetUtility::planetGetIP();
    if ($article_id > 0) {
        $criteria = new \CriteriaCompo(new \Criteria('art_id', $article_id));
    } else {
        $criteria = new \CriteriaCompo(new \Criteria('blog_id', $blog_id));
    }
    if ($uid > 0) {
        $criteria->add(new \Criteria('rate_uid', $uid));
    } else {
        $criteria->add(new \Criteria('rate_ip', $ip));
        $criteria->add(new \Criteria('rate_time', time() - 24 * 3600, '>'));
    }
    $rateHandler = xoops_getModuleHandler('rate', $GLOBALS['moddirname']);
    if ($count = $rateHandler->getCount($criteria)) {
        $message = planet_constant('MD_ALREADYRATED');
    } else {
        $rate_obj = $rateHandler->create();
        if ($article_id > 0) {
            $rate_obj->setVar('art_id', $article_id);
        } else {
            $rate_obj->setVar('blog_id', $blog_id);
        }
        $rate_obj->setVar('rate_uid', $uid);
        $rate_obj->setVar('rate_ip', $ip);
        $rate_obj->setVar('rate_rating', $rate);
        $rate_obj->setVar('rate_time', time());
        if (!$rate_id = $rateHandler->insert($rate_obj, true)) {
            redirect_header('javascript:history.go(-1);', 1, planet_constant('MD_NOTSAVED'));
        }
        if ($article_id > 0) {
            $article_obj = $articleHandler->get($article_id);
            $article_obj->setVar('art_rating', $article_obj->getVar('art_rating') + $rate);
            $article_obj->setVar('art_rates', $article_obj->getVar('art_rates') + 1);
            $articleHandler->insert($article_obj, true);
        } else {
            $blog_obj = $blogHandler->get($blog_id);
            $blog_obj->setVar('blog_rating', $blog_obj->getVar('blog_rating') + $rate);
            $blog_obj->setVar('blog_rates', $blog_obj->getVar('blog_rates') + 1);
            $blogHandler->insert($blog_obj, true);
        }
        $message = planet_constant('MD_ACTIONDONE');
    }
}
if ($article_id > 0) {
    redirect_header(XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/view.article.php' . URL_DELIMITER . '' . $article_id, 2, $message);
} else {
    redirect_header(XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php' . URL_DELIMITER . 'b' . $blog_id, 2, $message);
}
include __DIR__ . '/footer.php';
