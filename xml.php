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

ob_start();
include __DIR__ . '/header.php';

if (PlanetUtility::planetParseArguments($args_num, $args, $args_str)) {
    $args['article'] = @$args_num[0];
    $args['type']    = @$args_str[0];
}

/* Specified Article */
$article_id = Request::getInt('article', @$args['article'], 'GET'); //(int)(empty($_GET['article']) ? @$args['article'] : $_GET['article']);
/* Specified Category */
$category_id = Request::getInt('category', @$args['category'], 'GET'); //(int)(empty($_GET['category']) ? @$args['category'] : $_GET['category']);
/* Specified Blog */
$blog_id = Request::getInt('blog', @$args['blog'], 'GET'); //(int)(empty($_GET['blog']) ? @$args['blog'] : $_GET['blog']);
/* Specified Bookmar(Favorite) UID */
$uid = Request::getInt('uid', @$args['uid'], 'GET'); //(int)(empty($_GET['uid']) ? @$args['uid'] : $_GET['uid']);

$type = Request::getString('type', Request::getString('op', @$args['type'], 'POST'), 'GET'); // empty($_GET['type']) ? (empty($_GET['op']) ? @$args['type'] : $_GET['op']) : $_GET['type'];
$type = strtoupper($type);

$valid_format = ['RSS0.91', 'RSS1.0', 'RSS2.0', 'PIE0.1', 'MBOX', 'OPML', 'ATOM', 'ATOM0.3', 'HTML', 'JS'];
if ('RDF' === $type) {
    $type = 'RSS1.0';
}
if ('RSS' === $type) {
    $type = 'RSS0.91';
}
if (empty($type) || !in_array($type, $valid_format)) {
    PlanetUtility::planetRespondToTrackback(1, planet_constant('MD_INVALID'));
    exit();
}

$categoryHandler = xoops_getModuleHandler('category', $GLOBALS['moddirname']);
$blogHandler     = xoops_getModuleHandler('blog', $GLOBALS['moddirname']);
$articleHandler  = xoops_getModuleHandler('article', $GLOBALS['moddirname']);
$bookmarkHandler = xoops_getModuleHandler('bookmark', $GLOBALS['moddirname']);

if (!empty($article_id)) {
    $article_obj = $articleHandler->get($article_id);
    if (!$article_obj->getVar('art_id')) {
        PlanetUtility::planetRespondToTrackback(1, planet_constant('MD_EXPIRED'));
        exit();
    }
    $source = 'article';
} elseif (!empty($blog_id)) {
    $blog_obj = $blogHandler->get($blog_id);
    if (!$blog_obj->getVar('blog_id')) {
        PlanetUtility::planetRespondToTrackback(1, planet_constant('MD_INVALID'));
        exit();
    }
    $source = 'blog';
} elseif (!empty($category_id)) {
    $source       = 'category';
    $category_obj = $categoryHandler->get($category_id);
    if (!$category_obj->getVar('cat_id')) {
        PlanetUtility::planetRespondToTrackback(1, planet_constant('MD_INVALID'));
        exit();
    }
} elseif (!empty($uid)) {
    $source = 'bookmark';
} else {
    $source = '';
}

$xml_charset = 'UTF-8';
require_once XOOPS_ROOT_PATH . '/class/template.php';
$tpl = new XoopsTpl();
$tpl->xoops_setCaching(2);
$tpl->xoops_setCacheTime(3600);
$xoopsCachedTemplateId = md5($xoopsModule->getVar('mid') . ',' . $article_id . ',' . $category_id . ',' . $blog_id . ',' . $uid . ',' . $type);
if (!$tpl->is_cached('db:system_dummy.tpl', $xoopsCachedTemplateId)) {
    $criteria = new CriteriaCompo();
    $criteria->setLimit($xoopsModuleConfig['articles_perpage']);
    $articles_obj = [];
    switch ($source) {
        case 'article':
            $pagetitle = planet_constant('MD_ARTICLE');
            $rssdesc   = planet_constant('MD_XMLDESC_ARTICLE');

            $articles_obj[$article_id] = $article_obj;

            $xml_link = XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/view.article.php' . URL_DELIMITER . '' . $article_obj->getVar('art_id');
            break;

        case 'category':
            $pagetitle = planet_constant('MD_CATEGORY');
            $rssdesc   = sprintf(planet_constant('MD_XMLDESC_CATEGORY'), $category_obj->getVar('cat_title'));

            $criteria->add(new Criteria('bc.cat_id', $category_id));
            $articles_obj = $articleHandler->getByCategory($criteria);

            $xml_link = XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php' . URL_DELIMITER . 'c' . $category_id;
            break;

        case 'blog':
            $pagetitle = planet_constant('MD_BLOG');
            $rssdesc   = sprintf(planet_constant('MD_XMLDESC_BLOG'), $blog_obj->getVar('blog_title'));

            $criteria->add(new Criteria('blog_id', $blog_id));
            $articles_obj = $articleHandler->getAll($criteria);

            $xml_link = XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php' . URL_DELIMITER . 'b' . $blog_id;
            break;

        case 'bookmark':
            $author_name = XoopsUser::getUnameFromId($uid);
            $pagetitle   = planet_constant('MD_BOOKMARKS');
            $rssdesc     = sprintf(planet_constant('MD_XMLDESC_BOOKMARK'), $author_name);

            $criteria->add(new Criteria('bm.bm_uid', $uid));
            $articles_obj = $articleHandler->getByBookmark($criteria);

            $xml_link = XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php' . URL_DELIMITER . 'u' . $uid;

            break;

        default:
            $pagetitle = planet_constant('MD_INDEX');
            $rssdesc   = planet_constant('MD_XMLDESC_INDEX');

            $articles_obj = $articleHandler->getAll($criteria);

            $xml_link = XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/index.php';
            break;
    }
    $items = [];
    foreach (array_keys($articles_obj) as $id) {
        $content = $articles_obj[$id]->getVar('art_content');
        $content .= '<br>' . planet_constant('MD_SOURCE') . ': ' . $articles_obj[$id]->getVar('art_link') . ' ' . $articles_obj[$id]->getVar('art_author');
        $items[] = [
            'title'                     => $articles_obj[$id]->getVar('art_title'),
            'link'                      => XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/view.article.php' . URL_DELIMITER . '' . $articles_obj[$id]->getVar('art_id'),
            'description'               => $content,
            'descriptionHtmlSyndicated' => true,
            'date'                      => $articles_obj[$id]->getTime('rss'),
            'source'                    => XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/',
            'author'                    => $articles_obj[$id]->getVar('art_author')
        ];
    }
    unset($articles_obj, $criteria);

    $xmlHandler = xoops_getModuleHandler('xml', $GLOBALS['moddirname']);
    $xml        = $xmlHandler->create($type);
    $xml->setVar('encoding', $xml_charset);
    $xml->setVar('title', $xoopsConfig['sitename'] . ' :: ' . $pagetitle, 'UTF-8', $xml_charset, true);
    $xml->setVar('description', $rssdesc, true);
    $xml->setVar('descriptionHtmlSyndicated', true);
    $xml->setVar('link', $xml_link);
    $xml->setVar('syndicationURL', XOOPS_URL . '/' . xoops_getenv('PHP_SELF'), 'post', true);
    $xml->setVar('webmaster', checkEmail($xoopsConfig['adminmail'], true));
    $xml->setVar('editor', checkEmail($xoopsConfig['adminmail'], true));
    $xml->setVar('category', $xoopsModule->getVar('name'), true);
    $xml->setVar('generator', $xoopsModule->getInfo('version'));
    $xml->setVar('language', _LANGCODE);

    $dimention = @getimagesize(XOOPS_ROOT_PATH . '/modules/' . $GLOBALS['moddirname'] . '/' . $xoopsModule->getInfo('image'));
    $image     = [
        'width'       => $dimention[0],
        'height'      => $dimention[1],
        'title'       => $xoopsConfig['sitename'] . ' :: ' . $pagetitle,
        'url'         => XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/' . $xoopsModule->getInfo('image'),
        'link'        => XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/',
        'description' => $rssdesc
    ];
    $xml->setImage($image);

    /*
    $item = array(
        "title" => $datatitle,
        "link" => $dataurl,
        "description" => $datadesc,
        "descriptionHtmlSyndicated" => true,
        "date" => $datadate,
        "source" => $datasource,
        "author" => $dataauthor
        );
    */
    $xml->addItems($items);

    $dummy_content = $xmlHandler->display($xml, XOOPS_CACHE_PATH . '/' . $GLOBALS['moddirname'] . '.xml.tmp');

    $tpl->assign_by_ref('dummy_content', $dummy_content);
}
//$content = ob_get_contents();
ob_end_clean();
header('Content-Type:text/xml; charset=' . $xml_charset);
$tpl->display('db:system_dummy.tpl', $xoopsCachedTemplateId);
