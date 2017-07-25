<?php
/*
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright      {@link https://xoops.org/ XOOPS Project}
 * @license        {@link http://www.gnu.org/licenses/gpl-2.0.html GNU GPL 2 or later}
 * @package
 * @since
 * @author         XOOPS Development Team
 */

// defined('XOOPS_ROOT_PATH') || exit('XOOPS root path not defined');

include __DIR__ . '/vars.php';
//mod_loadFunctions('', $GLOBALS['moddirname']);

PlanetUtility::planetParseFunction('
function [VAR_PREFIX]_notify_iteminfo($category, $item_id)
{
    planet_define_url_delimiter();
    $item_id = (int)($item_id);

    switch ($category) {
    case "blog":
        $blogHandler = xoops_getModuleHandler("blog", $GLOBALS["moddirname"]);
        $blog_obj = $blogHandler->get($item_id);
        if (!is_object($blog_obj)) {
            redirect_header(XOOPS_URL."/modules/".$GLOBALS["moddirname"]."/index.php", 2, planet_constant("MD_NOACCESS"));

        }
        $item["name"] = $blog_obj->getVar("blog_title");
        $item["url"] = XOOPS_URL . "/modules/" . $GLOBALS["moddirname"] . "/index.php".URL_DELIMITER."b" . $item_id;
        break;
    case "article":
        $articleHandler = xoops_getModuleHandler("article", $GLOBALS["moddirname"]);
        $article_obj = $articleHandler->get($item_id);
        if (!is_object($article_obj)) {
            redirect_header(XOOPS_URL."/modules/".$GLOBALS["moddirname"]."/index.php", 2, planet_constant("MD_NOACCESS"));

        }
        $item["name"] = $article_obj->getVar("art_title");
        $item["url"] = XOOPS_URL . "/modules/" . $GLOBALS["moddirname"] . "/view.article.php".URL_DELIMITER."" . $item_id;
        break;
    case "global":
    default:
        $item["name"] = "";
        $item["url"] = "";
        break;
    }

    return $item;
}
');
