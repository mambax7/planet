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

$op      = Request::getString('op', Request::getString('op', '', 'POST'), 'GET');//!empty($_POST['op']) ? $_POST['op'] : (!empty($_GET['op']) ? $_GET['op'] : '');
$blog_id = Request::getArray('blog', Request::getArray('blog', [], 'POST'), 'GET');//!empty($_POST['blog']) ? $_POST['blog'] : (!empty($_GET['blog']) ? $_GET['blog'] : 0);
$blog_id = is_array($blog_id) ? array_map('intval', $blog_id) : (int)$blog_id;

if (empty($helper->getConfig('newblog_submit')) && (!is_object($xoopsUser) || !$xoopsUser->isAdmin())) {
    redirect_header('index.php', 2, _NOPERM);
}

if ('save' === $op && !empty(Request::getString('fetch', '', 'POST'))) {//!empty($_POST['fetch'])) {
    $op = 'edit';
}

if ('save' === $op && !$GLOBALS['xoopsSecurity']->check()) {
    redirect_header('javascript:history.go(-1);', 1, planet_constant('MD_INVALID') . ': security check failed');
}
include XOOPS_ROOT_PATH . '/header.php';
include XOOPS_ROOT_PATH . '/modules/' . $xoopsModule->getVar('dirname') . '/include/vars.php';

$blogHandler     = xoops_getModuleHandler('blog', $GLOBALS['moddirname']);
$categoryHandler = xoops_getModuleHandler('category', $GLOBALS['moddirname']);

switch ($op) {
    /* save a single blog */
    case 'save':

        if ($blog_id) {
            $blog_obj = $blogHandler->get($blog_id);
            if ($xoopsUser->isAdmin()) {
                $blog_obj->setVar('blog_status', Request::getInt('blog_status', 0, 'POST'));// @$_POST['blog_status']);
            }
        } else {
            if ($blog_exists = $blogHandler->getCount(new \Criteria('blog_feed', $myts->addSlashes(trim(Request::getText('blog_feed', '', 'POST')))))  //$_POST['blog_feed']))))
            ) {
                redirect_header('index.php', 2, planet_constant('MD_BLOGEXISTS'));
            }

            $blog_obj = $blogHandler->create();
            $blog_obj->setVar('blog_submitter', is_object($xoopsUser) ? $xoopsUser->getVar('uid') : PlanetUtility::planetGetIP(true));

            switch ($helper->getConfig('newblog_submit')) {
                case 2:
                    if (!is_object($xoopsUser)) {
                        $status = 0;
                    } else {
                        $status = 1;
                    }
                    break;
                case 0:
                case 3:
                    $status = 1;
                    break;
                case 1:
                default:
                    if (!is_object($xoopsUser) || !$xoopsUser->isAdmin()) {
                        $status = 0;
                    } else {
                        $status = 1;
                    }
                    break;
            }

            $blog_obj->setVar('blog_status', $status);
        }

        $blog_obj->setVar('blog_title', Request::getString('blog_title', '', 'POST'));//$_POST['blog_title']);
        $blog_obj->setVar('blog_desc', Request::getString('blog_desc', '', 'POST'));//$_POST['blog_desc']);
        $blog_obj->setVar('blog_image', Request::getString('blog_image', '', 'POST'));//$_POST['blog_image']);
        $blog_obj->setVar('blog_feed', Request::getText('blog_feed', '', 'POST'));//$_POST['blog_feed']);
        $blog_obj->setVar('blog_link', Request::getString('blog_link', '', 'POST'));//$_POST['blog_link']);
        $blog_obj->setVar('blog_language', Request::getString('blog_language', '', 'POST'));//$_POST['blog_language']);
        $blog_obj->setVar('blog_charset', Request::getString('blog_charset', '', 'POST'));//$_POST['blog_charset']);
        $blog_obj->setVar('blog_trackback', Request::getString('blog_trackback', '', 'POST'));//$_POST['blog_trackback']);
        if ($blog_obj->isNew()) {
            $blog_obj->setVar('blog_submitter', is_object($xoopsUser) ? $xoopsUser->getVar('uid') : PlanetUtility::planetGetIP(true));
        }

        if (!$blogHandler->insert($blog_obj)) {
        } elseif (0 !== count(Request::getArray('categories', [], 'POST'))) {
            $blog_id = $blog_obj->getVar('blog_id');
            if (in_array(0, $_POST['categories'])) {
                $_POST['categories'] = [];
            }
            $blogHandler->setCategories($blog_id, Request::getString('andor', '', 'POST'));//$_POST['categories']);
        }
        $message = planet_constant('MD_DBUPDATED');
        redirect_header('index.php' . URL_DELIMITER . 'b' . $blog_id, 2, $message);

    /* edit a single blog */
    // no break
    case 'edit':
    default:
        if (!empty(Request::getString('fetch', '', 'POST'))) {
            $blog_obj = $blogHandler->fetch(Request::getText('blog_feed', '', 'POST'));
            $blog_obj->setVar('blog_id', $blog_id);
        } else {
            $blog_obj = $blogHandler->get($blog_id);
        }
        $categories = Request::getArray('categories', [], 'POST');//isset($_POST['categories']) ? $_POST['categories'] : array();
        if (in_array('-1', $categories)) {
            $categories = [];
        }
        if (empty($categories) && $blog_id > 0) {
            $crit       = new \Criteria('bc.blog_id', $blog_id);
            $categories = array_keys($categoryHandler->getByBlog($crit));
        }
        if (empty($categories)) {
            $categories = [0 => _NONE];
        }

        echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _EDIT . '</legend>';
        echo '<br>';
        if (empty($blog_id) && $blog_obj->getVar('blog_feed')) {
            $criteria  = new \Criteria('blog_feed', $blog_obj->getVar('blog_feed'));
            $blogs_obj = $blogHandler->getList($criteria);
            if (count($blogs_obj) > 0) {
                echo '<div class="errorMsg">' . planet_constant('MD_BLOGEXISTS');
                foreach (array_keys($blogs_obj) as $bid) {
                    echo '<br><a href="' . XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php' . URL_DELIMITER . 'b' . $bid . '" target="_blank">' . $blogs_obj[$bid] . '</a>';
                }
                echo '</div>';
                unset($blogs_obj, $criteria);
            }
        }
        include XOOPS_ROOT_PATH . '/modules/' . $GLOBALS['moddirname'] . '/include/form.blog.php';
        echo '</fieldset>';
        break;
}

include XOOPS_ROOT_PATH . '/footer.php';
