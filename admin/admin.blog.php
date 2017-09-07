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

require_once __DIR__ . '/admin_header.php';
require_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

xoops_cp_header();
$adminObject = \Xmf\Module\Admin::getInstance();
$adminObject->displayNavigation(basename(__FILE__));
/*
 * To restore basic parameters in case cloned modules are installed
 * reported by programfan
 *
 * This is a tricky fix for incomplete solution of module cone
 * it is expected to have a better solution in article 1.0
 */
require XOOPS_ROOT_PATH . '/modules/' . $xoopsModule->getVar('dirname') . '/include/vars.php';
//planet_adminmenu(2);

$op          = Request::getString('op', Request::getString('op', '', 'POST'), 'GET');//!empty($_POST['op']) ? $_POST['op'] : (!empty($_GET['op']) ? $_GET['op'] : '');
$blog_id     = Request::getArray('blog', Request::getArray('blog', [], 'POST'), 'GET');//!empty($_POST['blog']) ? $_POST['blog'] : (!empty($_GET['blog']) ? $_GET['blog'] : 0);
$blog_id     = is_array($blog_id) ? array_map('intval', $blog_id) : (int)$blog_id;
$category_id = Request::getInt('category', Request::getInt('category', 0, 'POST'), 'GET');//(int)(!empty($_POST['category']) ? $_POST['category'] : (!empty($_GET['category']) ? $_GET['category'] : 0));
$start       = Request::getInt('start', Request::getInt('start', 0, 'POST'), 'GET');//(int)(!empty($_POST['start']) ? $_POST['start'] : (!empty($_GET['start']) ? $_GET['start'] : 0));

$blogHandler     = xoops_getModuleHandler('blog', $GLOBALS['moddirname']);
$categoryHandler = xoops_getModuleHandler('category', $GLOBALS['moddirname']);

if ('save' === $op && !empty(Request::getString('fetch', '', 'POST'))) {
    $op = 'edit';
}

switch ($op) {
    /* save a single blog */
    case 'save':

        if ($blog_id) {
            $blog_obj = $blogHandler->get($blog_id);
        } else {
            if ($blog_exists = $blogHandler->getCount(new Criteria('blog_feed', Request::getString('blog_feed', '', 'POST')))) {
                redirect_header('admin.blog.php', 2, planet_constant('AM_BLOGEXISTS'));
            }
            $blog_obj = $blogHandler->create();
            $blog_obj->setVar('blog_submitter', $xoopsUser->getVar('uid'));
        }

        $blog_obj->setVar('blog_title', Request::getString('blog_title', '', 'POST')); //$_POST['blog_title']);
        $blog_obj->setVar('blog_desc', Request::getString('blog_desc', '', 'POST')); //$_POST['blog_desc']);
        $blog_obj->setVar('blog_image', Request::getString('blog_image', '', 'POST')); //$_POST['blog_image']);
        $blog_obj->setVar('blog_feed', Request::getString('blog_feed', '', 'POST')); //$_POST['blog_feed']);
        $blog_obj->setVar('blog_link', Request::getString('blog_link', '', 'POST')); //$_POST['blog_link']);
        $blog_obj->setVar('blog_language', Request::getString('blog_language', '', 'POST')); //$_POST['blog_language']);
        $blog_obj->setVar('blog_charset', Request::getString('blog_charset', '', 'POST')); //$_POST['blog_charset']);
        $blog_obj->setVar('blog_trackback', Request::getString('blog_trackback', '', 'POST')); //$_POST['blog_trackback']);
        $blog_obj->setVar('blog_status', Request::getInt('blog_status', 0, 'POST')); //$_POST['blog_status']);

        if (!$blogHandler->insert($blog_obj)) {
        } elseif (!empty(Request::getArray('categories', [], 'POST'))) {
            $blog_id = $blog_obj->getVar('blog_id');
            if (in_array(0, Request::getArray('categories', [], 'POST'))) {
                $_POST['categories'] = [];
            }
            $blogHandler->setCategories($blog_id, Request::getArray('categories', [], 'POST'));
        }
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php', 2, $message);

    /* fetch and add a list of blogs to a category */
    // no break
    case 'add':
        $links = PlanetUtility::planetParseLinks(Request::getArray('links', [], 'POST'));
        $blogs = [];
        foreach ($links as $link) {
            if ($blog_exist = $blogHandler->getCount(new Criteria('blog_feed', $link['url']))) {
                continue;
            }
            $blog_obj = $blogHandler->fetch($link['url']);
            if (!empty($link['title'])) {
                $blog_obj->setVar('blog_title', $link['title']);
            }
            $blogHandler->insert($blog_obj);
            $blogs[] = $blog_obj->getVar('blog_id');
            unset($blog_obj);
        }
        if (!empty(Request::getArray('categories', [], 'POST'))) {
            $categoryHandler = xoops_getModuleHandler('category', $GLOBALS['moddirname']);
            foreach (Request::getArray('categories', [], 'POST') as $cat_id) {
                $categoryHandler->addBlogs($cat_id, $blogs);
            }
        }
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php', 2, $message);

    /* update a list of blogs */
    // no break
    case 'update':
        foreach ($blog_id as $bid) {
            $blog_obj = $blogHandler->fetch($bid);
            if (!$blogHandler->insert($blog_obj)) {
            }
            unset($blog_obj);
        }
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php?category=' . $category_id . '&amp;start=' . $start, 2, $message);

    /* add a list of blogs to a category */
    // no break
    case 'register':
        if (!empty(Request::getArray('category_dest', [], 'POST'))) {
            if (!is_array($blog_id)) {
                $blog_id = [$blog_id];
            }
            $categoryHandler = xoops_getModuleHandler('category', $GLOBALS['moddirname']);
            $categoryHandler->addBlogs(Request::getArray('category_dest', [], 'POST'), $blog_id);
        }
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php?category=' . Request::getArray('category_dest', [], 'POST') . '&amp;start=' . $start, 2, $message);

    /* remove a list of blogs from a category */
    // no break
    case 'remove':
        if (!is_array($blog_id)) {
            $blog_id = [$blog_id];
        }
        if (!empty($category_id)) {
            $categoryHandler = xoops_getModuleHandler('category', $GLOBALS['moddirname']);
            $categoryHandler->removeBlogs($category_id, $blog_id);
        }
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php?category=' . $category_id . '&amp;start=' . $start, 2, $message);

    /* delete a single blog or a list blogs */
    // no break
    case 'del':
        if (!is_array($blog_id)) {
            $blog_id = [$blog_id];
        }
        foreach ($blog_id as $bid) {
            $blog_obj = $blogHandler->get($bid);
            if (!$blogHandler->delete($blog_obj, true)) {
            }
            unset($blog_obj);
        }
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php?category=' . $category_id . '&amp;start=' . $start, 2, $message);

    /* empty a single blog or a list blogs */
    // no break
    case 'empty':
        if (!is_array($blog_id)) {
            $blog_id = [$blog_id];
        }
        foreach ($blog_id as $bid) {
            $blog_obj = $blogHandler->get($bid);
            if (!$blogHandler->do_empty($blog_obj)) {
            }
        }
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php?category=' . $category_id . '&amp;start=' . $start, 2, $message);

    /* approve a single blog or a list blogs */
    // no break
    case 'approve':
        if (!is_array($blog_id)) {
            $blog_id = [$blog_id];
        }
        $criteria = new Criteria('blog_id', '(' . implode(',', $blog_id) . ')', 'IN');
        $blogHandler->updateAll('blog_status', 1, $criteria, true);
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php?category=' . $category_id . '&amp;start=' . $start, 2, $message);

    /* mark a single blog or a list blogs as featured */
    // no break
    case 'feature':
        if (!is_array($blog_id)) {
            $blog_id = [$blog_id];
        }
        $criteria = new Criteria('blog_id', '(' . implode(',', $blog_id) . ')', 'IN');
        $blogHandler->updateAll('blog_status', 2, $criteria, true);
        $message = planet_constant('AM_DBUPDATED');
        redirect_header('admin.blog.php?category=' . $category_id . '&amp;start=' . $start, 2, $message);

    /* edit a single blog */
    // no break
    case 'edit':
        if (!empty(Request::getString('fetch', '', 'POST'))) {
            $blog_obj = $blogHandler->fetch(Request::getString('blog_feed', '', 'POST'));
            $blog_obj->setVar('blog_id', $blog_id);
        } else {
            $blog_obj = $blogHandler->get($blog_id);
        }
        $categories = Request::getArray('categories', [], 'POST');
        if (empty($categories) && $blog_id > 0) {
            $crit       = new Criteria('bc.blog_id', $blog_id);
            $categories = array_keys($categoryHandler->getByBlog($crit));
        }
        if (empty($categories)) {
            $categories = [0 => _NONE];
        }

        echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _EDIT . '</legend>';
        echo '<br>';
        if (empty($blog_id) && $blog_obj->getVar('blog_feed')) {
            $criteria  = new Criteria('blog_feed', $blog_obj->getVar('blog_feed'));
            $blogs_obj = $blogHandler->getList($criteria);
            if (count($blogs_obj) > 0) {
                echo "<div class=\"errorMsg\">" . planet_constant('AM_BLOGEXISTS');
                foreach (array_keys($blogs_obj) as $bid) {
                    echo "<br><a href=\"" . XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php' . URL_DELIMITER . 'b' . $bid . "\" target=\"_blank\">" . $blogs_obj[$bid] . '</a>';
                }
                echo '</div>';
            }
            unset($blogs_obj, $criteria);
        }
        include XOOPS_ROOT_PATH . '/modules/' . $GLOBALS['moddirname'] . '/include/form.blog.php';
        echo '</fieldset>';
        break;

    default:

        $crit = new Criteria('1', 1);
        $crit->setSort('cat_order');
        $crit->setOrder('ASC');
        $categories = $categoryHandler->getList($crit);

        // Display category option form
        $opform    = new XoopsSimpleForm('', 'opform', 'admin.blog.php', 'get');
        $op_select = new XoopsFormSelect('', 'category', $category_id);
        $op_select->setExtra('onchange="document.forms.opform.submit()"');
        $options = [
            '0'  => _ALL,
            '-1' => planet_constant('MD_ACTIVE'),
            '-2' => planet_constant('MD_FEATURED'),
            '-3' => planet_constant('MD_PENDING')
        ];
        foreach (array_keys($categories) as $key) {
            $options[$key] = $categories[$key];
        }
        $op_select->addOptionArray($options);
        $opform->addElement($op_select);
        $opform->display();

        if ($category_id > 0) {
            $criteria = new CriteriaCompo(new Criteria('b.blog_status', 0, '>'));
            $criteria->add(new Criteria('bc.cat_id', $category_id));
            $blog_count = $blogHandler->getCountByCategory($criteria);
            $criteria->setStart($start);
            $criteria->setLimit($xoopsModuleConfig['list_perpage']);
            $blog_objs = $blogHandler->getByCategory($criteria);
        } else {
            /* All active blogs */
            if ($category_id == 0) {
                $criteria = new Criteria('1', 1);
                $criteria->setStart($start);
                $criteria->setLimit($xoopsModuleConfig['list_perpage']);
                /* Active blogs */
            } elseif ($category_id == -1) {
                $criteria = new Criteria('blog_status', 1);
                $criteria->setStart($start);
                $criteria->setLimit($xoopsModuleConfig['list_perpage']);
                /* Featured blogs */
            } elseif ($category_id == -2) {
                $criteria = new Criteria('blog_status', 2);
                $criteria->setStart($start);
                $criteria->setLimit($xoopsModuleConfig['list_perpage']);
                /* Pending blogs */
            } else {
                $criteria = new Criteria('blog_status', 0);
                $criteria->setStart($start);
                $criteria->setLimit($xoopsModuleConfig['list_perpage']);
            }
            $blog_count = $blogHandler->getCount($criteria);
            $blog_objs  = $blogHandler->getAll($criteria);
        }

        echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . planet_constant('AM_LIST') . '</legend>';
        echo "<br style=\"clear:both\">";

        echo "<form name='list' id='list' method='post' action='" . xoops_getenv('PHP_SELF') . "'>";
        echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' class='outer'>";
        echo "<tr align='center'>";
        echo "<th class='bg3' width='5%'><input name='blog_check' id='blog_check' value='1' type='checkbox'  onclick=\"xoopsCheckAll('list', 'blog_check');\"></td>";
        echo "<th class='bg3'>" . planet_constant('AM_TITLE') . '</td>';
        echo "<th class='bg3' width='5%'>" . planet_constant('AM_STATUS') . '</td>';
        echo "<th class='bg3' width='40%'>" . planet_constant('AM_FEED') . '</td>';
        //        echo "<th class='bg3' width='5%'>" . _EDIT . "</td>";
        //        echo "<th class='bg3' width='5%'>" . _DELETE . "</td>";
        echo "<th class='bg3' width='10%'>" . planet_constant('AM_ACTIONS') . '</td>';
        echo '</tr>';

        $status = [
            '0' => planet_constant('MD_PENDING'),
            '1' => planet_constant('MD_ACTIVE'),
            '2' => planet_constant('MD_FEATURED')
        ];
        foreach (array_keys($blog_objs) as $bid) {
            echo "<tr class='odd' align='left'>";
            echo "<td align='center'><input name='blog[]' value='" . $bid . "' type='checkbox'></td>";
            echo "<td><a href='" . XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php' . URL_DELIMITER . 'b' . $bid . "'>" . $blog_objs[$bid]->getVar('blog_title') . '</a></td>';
            echo "<td align='center'>" . $status[$blog_objs[$bid]->getVar('blog_status')] . '</td>';
            echo '<td>' . $blog_objs[$bid]->getVar('blog_feed') . '</td>';
            echo "<td align='center'><a href='admin.blog.php?op=edit&amp;blog=" . $bid . "' title='" . _EDIT . "'><img src='" . $pathIcon16 . "/edit.png '" . "alt='" . _EDIT . " title='" . _EDIT . " </a>
                      <a href='admin.blog.php?op=del&amp;blog=" . $bid . "' title='" . _DELETE . "'><img src='" . $pathIcon16 . "/delete.png '" . " alt='" . _EDIT . " title='" . _DELETE . " </a>&nbsp;
                      <a href='admin.blog.php?op=empty&amp;blog=" . $bid . "' title='" . planet_constant('MD_EMPTY_BLOG') . "'><img src='" . $pathIcon16 . "/empty.png '" . " alt='" . _EDIT . " title='" . planet_constant('MD_EMPTY_BLOG') . '</a></td>';

            echo '</tr>';
        }
        echo "<tr class='even' align='center'>";
        echo "<td colspan='7'>";
        echo "<select name='op' onChange='if (this.options[this.selectedIndex].value==\"register\") {setVisible(\"catdiv\");} else {setHidden(\"catdiv\");}'>";
        echo "<option value=''>" . _SELECT . '</option>';
        echo "<option value='del'>" . _DELETE . '</option>';
        echo "<option value='empty'>" . planet_constant('MD_EMPTY_BLOG') . '</option>';
        echo "<option value='register'>" . planet_constant('AM_REGISTER') . '</option>';
        if ($category_id > 0) {
            echo "<option value='remove'>" . planet_constant('AM_REMOVE') . '</option>';
        }
        echo "<option value='approve'>" . planet_constant('AM_APPROVE') . '</option>';
        echo "<option value='feature'>" . planet_constant('AM_FEATURE') . '</option>';
        echo "<option value='update'>" . planet_constant('AM_UPDATE') . '</option>';

        echo "<option value='pending'>" . planet_constant('AM_PENDING') . '</option>';
        echo "<option value='active'>" . planet_constant('AM_ACTIVE') . '</option>';

        echo '</select>';
        echo "<div id='catdiv' style='visibility:hidden;display:inline;'>";
        echo "<select name='category_dest'>";
        echo "<option value=''>" . _SELECT . '</option>';
        foreach ($categories as $cid => $name) {
            echo "<option value='" . $cid . "'>" . $name . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo "<input name='start' value='" . $start . "' type='hidden'>";
        echo "<input name='category' value='" . $category_id . "' type='hidden'>";
        echo "<input name='submit' value='" . _SUBMIT . "' type='submit'>";
        echo "<input name='' value='" . _CANCEL . "' type='reset'>";
        echo '</td>';
        echo '</tr>';
        if ($blog_count > $xoopsModuleConfig['list_perpage']) {
            include XOOPS_ROOT_PATH . '/class/pagenav.php';
            $nav     = new XoopsPageNav($blog_count, $xoopsModuleConfig['list_perpage'], $start, 'start', 'category=' . $category_id);
            $pagenav = $nav->renderNav(4);
            echo "<tr align='right'><td colspan='6'>" . $pagenav . '</td></tr>';
        }
        echo '</table></form>';
        echo "</fieldset><br style='clear:both;'>";

        if (empty($start) && empty($category_id)) {
            $form = new XoopsThemeForm(_ADD, 'edit', xoops_getenv('PHP_SELF'), 'post', true);
            $form->addElement(new XoopsFormText(planet_constant('AM_FEED'), 'blog_feed', 50, 255), true);
            $form->addElement(new XoopsFormHidden('op', 'edit'));
            $button_tray = new XoopsFormElementTray('', '');
            $butt_save   = new XoopsFormButton('', 'fetch', _SUBMIT, 'submit');
            $button_tray->addElement($butt_save);
            $butt_cancel = new XoopsFormButton('', '', _CANCEL, 'reset');
            $button_tray->addElement($butt_cancel);
            $form->addElement($button_tray);

            $form_add = new XoopsThemeForm(_ADD, 'add', xoops_getenv('PHP_SELF'), 'post', true);
            $form_add->addElement(new XoopsFormTextArea(planet_constant('AM_FEED'), 'links'));
            $form_add->addElement(new XoopsFormHidden('op', 'add'));
            $button_tray = new XoopsFormElementTray('', '');
            $butt_save   = new XoopsFormButton('', 'submit', _SUBMIT, 'submit');
            $button_tray->addElement($butt_save);
            $butt_cancel = new XoopsFormButton('', '', _CANCEL, 'reset');
            $button_tray->addElement($butt_cancel);
            $form_add->addElement($button_tray);

            echo "<fieldset><legend style='font-weight: bold; color: #900;'>" . _ADD . '</legend>';
            echo '<br>';
            $form->display();
            $form_add->display();
            echo '</fieldset>';
        }
        break;
}

xoops_cp_footer();
