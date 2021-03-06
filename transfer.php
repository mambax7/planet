<?php
/**
 * Transfer handler for XOOPS
 *
 * This is intended to handle content intercommunication between modules as well as components
 * There might need to be a more explicit name for the handle since it is always confusing
 *
 * @copyright   XOOPS Project (https://xoops.org)
 * @license     http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author      Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since       3.00
 * @package     Frameworks::transfer ; module::planet
 */

use Xmf\Request;

include __DIR__ . '/header.php';

if (PlanetUtility::planetParseArguments($args_num, $args, $args_str)) {
    $args['article'] = @$args_num[0];
    $args['op']      = @$args_str[0];
}

$article_id = Request::getInt('article', Request::getInt('article', @$args['article'], 'POST'), 'GET');//(int)(empty($_GET['article']) ? (empty($_POST['article']) ? @$args['article'] : $_POST['article']) : $_GET['article']);

$op = Request::getString('op', Request::getString('op', @$args['op'], 'POST'), 'GET');//empty($_GET['op']) ? (empty($_POST['op']) ? @$args['op'] : $_POST['op']) : $_GET['op'];
$op = strtolower(trim($op));

if (empty($article_id)) {
    if (empty(Request::getUrl('HTTP_REFERER', '', 'SERVER'))) {
        //$_SERVER['HTTP_REFERER']))

        include XOOPS_ROOT_PATH . '/header.php';
        xoops_error(_NOPERM);
        $xoopsOption['output_type'] = 'plain';
        include XOOPS_ROOT_PATH . '/footer.php';
        exit();
    } else {
        $ref_parser = parse_url(Request::getUrl('HTTP_REFERER', '', 'SERVER')); //$_SERVER['HTTP_REFERER']);
        $uri_parser = parse_url(Request::getUrl('REQUEST_URI', '', 'SERVER'));// $_SERVER['REQUEST_URI']);
        if ((!empty($ref_parser['host']) && !empty($uri_parser['host']) && $uri_parser['host'] != $ref_parser['host'])
            || ($ref_parser['path'] != $uri_parser['path'])) {
            include XOOPS_ROOT_PATH . '/header.php';
            include XOOPS_ROOT_PATH . '/modules/' . $xoopsModule->getVar('dirname') . '/include/vars.php';
            xoops_confirm([], 'javascript: window.close();', sprintf(planet_constant('MD_TRANSFER_DONE'), ''), _CLOSE, Request::getUrl('HTTP_REFERER', '', 'SERVER'));
            $xoopsOption['output_type'] = 'plain';
            include XOOPS_ROOT_PATH . '/footer.php';
            exit();
        } else {
            include XOOPS_ROOT_PATH . '/header.php';
            xoops_error(_NOPERM);
            $xoopsOption['output_type'] = 'plain';
            include XOOPS_ROOT_PATH . '/footer.php';
            exit();
        }
    }
}

$articleHandler = xoops_getModuleHandler('article', $GLOBALS['moddirname']);
$article_obj    = $articleHandler->get($article_id);

// Display option form
if (empty($op)) {
    $module_variables .= "<input type=\"hidden\" name=\"article\" id=\"article\" value=\"{$article_id}\">";
    include XOOPS_ROOT_PATH . '/Frameworks/transfer/option.transfer.php';
    exit();
} else {
    $data           = [];
    $data['id']     = $article_id;
    $data['title']  = $article_obj->getVar('art_title');
    $data['time']   = $article_obj->getTime('l');
    $data['image']  = '';
    $data['source'] = $article_obj->getVar('art_link');
    $data['url']    = XOOPS_URL . '/modules/' . $GLOBALS['moddirname'] . '/view.article.php' . URL_DELIMITER . '' . $article_obj->getVar('art_id');
    $data['author'] = $article_obj->getVar('art_author');

    switch ($op) {

        // Use title
        case 'bookmark':
            break;

        case 'print':
        case 'pdf':
            ${"{$op}_data"}            =& $data;
            ${"{$op}_data"}['date']    = $pdf_data['time'];
            ${"{$op}_data"}['content'] = $article_obj->getVar('art_content');
            break;

        case 'newbb':
        default:
            $data['content'] = $article_obj->getSummary();
            break;
    }
    include XOOPS_ROOT_PATH . '/Frameworks/transfer/action.transfer.php';
    exit();
}
