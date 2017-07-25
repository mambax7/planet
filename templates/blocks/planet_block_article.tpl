<!-- phppp (D.J.): http://xoopsforge.com; https://xoops.org.cn -->
<{foreach item=article from=$block.articles}>
    <div>
        <span><a href="<{$xoops_url}>/modules/<{$block.dirname}>/view.article.php<{$smarty.const.URL_DELIMITER}><{$article.art_id}>"><strong><{$article.art_title}></strong></a></span>
        <{if $article.disp}> (<{$article.disp}>)<{/if}>
    </div>
    <div style="margin-bottom:5px;">
        <span><{$article.time}></span>
    </div>
    <{if $article.summary}>
        <div><{$article.summary}></div><{/if}>
<{/foreach}>
