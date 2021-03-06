<?php
/**
 * FPDF creator framework for XOOPS
 *
 * Supporting multi-byte languages as well as utf-8 charset
 *
 * @copyright   XOOPS Project (https://xoops.org)
 * @license     http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author      Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since       1.00
 * @package     frameworks
 */

//TODO needs to be refactored for TCPDF

//ob_start();

/**
 * If no pdf_data is set, build it from the module
 *
 * <ul>The data fields to be built:
 *      <li>title</li>
 *      <li>subtitle (optional)</li>
 *      <li>subsubtitle (optional)</li>
 *      <li>date</li>
 *      <li>author</li>
 *      <li>content</li>
 *      <li>filename</li>
 * </ul>
 */

use Xmf\Request;

include __DIR__ . '/header.php';
global $pdf_data;
if (!empty($_POST['pdf_data'])) {
    $pdf_data = unserialize(base64_decode(Request::getText('pdf_data', '', 'POST')));
} elseif (!empty($pdf_data)) {
} else {
    error_reporting(0);
    include __DIR__ . '/header.php';
    error_reporting(0);

    if (PlanetUtility::planetParseArguments($args_num, $args, $args_str)) {
        $args['article'] = @$args_num[0];
    }

    $article_id = (int)(empty($_GET['article']) ? @$args['article'] : $_GET['article']);

    $articleHandler = xoops_getModuleHandler('article', $GLOBALS['moddirname']);
    $article_obj    = $articleHandler->get($article_id);

    $article_data = [];

    // title
    $article_data['title'] = $article_obj->getVar('art_title');

    $article_data['author'] = $article_obj->getVar('art_author');

    // source
    $article_data['source'] = $article_obj->getVar('art_link');

    // publish time
    $article_data['time'] = $article_obj->getTime();

    // summary
    $article_data['summary'] = $article_obj->getSummary();

    // text of page
    $article_data['text'] = $article_obj->getVar('art_content');

    // Build the pdf_data array
    $pdf_data['title']   = $article_data['title'];
    $pdf_data['author']  = $article_data['author'];
    $pdf_data['date']    = $article_data['time'];
    $pdf_data['content'] = '';
    if ($article_data['summary']) {
        $pdf_data['content'] .= planet_constant('MD_SUMMARY') . ': ' . $article_data['summary'] . '<br><br>';
    }
    $pdf_data['content'] .= $article_data['text'] . '<br>';
    $pdf_data['url']     = XOOPS_URL . '/modules/' . $GLOBALS['artdirname'] . '/view.article.php' . URL_DELIMITER . $article_obj->getVar('art_id');
}
$pdf_data['filename'] = preg_replace("/[^0-9a-z\-_\.]/i", '', $pdf_data['title']);

require_once XOOPS_ROOT_PATH . '/class/libraries/vendor/tecnickcom/tcpdf/tcpdf.php';

error_reporting(0);
ob_end_clean();

//$pdf = new xoopsPDF($xoopsConfig['language']);
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, _CHARSET, false);
$pdf->initialize();
$pdf->output($pdf_data, _CHARSET);
