<!-- phppp (D.J.): http://xoopsforge.com; https://xoops.org.cn -->
<div id="top"></div>
<div class="itemHead">
    <h2>
<span class="itemTitle">
<{if $category}>
    <{php}>echo planet_constant("MD_CATEGORY")<{/php}>: <{$category.title}>
<{elseif $user}>
    <{php}>echo planet_constant("MD_BOOKMARKS")<{/php}>:
    <a href="<{$xoops_url}>/userinfo.php?uid=<{$user.uid}>"><{$user.name}></a>
    (<{$user.marks}>)
<{elseif $blog}>
    <{php}>echo planet_constant("MD_BLOG")<{/php}>: <{$blog.title}>
<{else}>
    <{$pagetitle}>
<{/if}>
</span>
    </h2>
</div>

<{if $blog}>
    <div class="itemInfo">
        <{if $blog.image}>
            <div class="image"><img src="<{$blog.image}>"></div><{/if}>
        <{if $blog.desc}><{$blog.desc}> | <{/if}>
        <a href="<{$blog.link}>" target="_blank"><{php}>echo planet_constant("MD_URL")<{/php}></a> |
        <a href="<{$blog.feed}>" target="_blank"><{php}>echo planet_constant("MD_FEED")<{/php}></a>
        <{if $user_level gt 1}>| <a
            href="<{$xoops_url}>/modules/<{$xoops_dirname}>/action.blog.php?blog=<{$blog.id}>"><{$smarty.const._EDIT}></a><{/if}>
        <br>
        <{php}>echo planet_constant("MD_LASTUPDATE")<{/php}>: <{$blog.time}>
        (<a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/update.php?blog=<{$blog.id}>"><{php}>echo planet_constant("MD_UPDATE")<{/php}></a>)
        <br>
        <{if $blog.marks OR $user_level gt 0}>
            <{php}>echo planet_constant("MD_BOOKMARKS")<{/php}>: <{$blog.marks}> <{if $user_level gt 0}>(
            <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/action.bookmark.php?blog=<{$blog.id}>"><{php}>echo planet_constant("MD_BOOKMARK")<{/php}></a>
            )<{/if}>
        <{/if}>
        <{if $blog.rates OR $canrate}>
            | <{php}>echo planet_constant("MD_RATE")<{/php}>:
            <{if $blog.rates}>
                <{$blog.star}>/<{$blog.rates}>
            <{/if}>
            <{if $canrate}> (
                <{section name=rate loop=6 max=5 step=-1}>
                    <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/action.rate.php?blog=<{$blog.id}>&amp;rate=<{$smarty.section.rate.index}>"><{$smarty.section.rate.index}></a>
                <{/section}>
                )<{/if}>
        <{/if}>
    </div>
<{/if}>

<br style="clear:both;">

<{foreach item=article from=$articles}>
    <div class="itemHead">
<span class="itemTitle">
<a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/view.article.php<{$smarty.const.URL_DELIMITER}><{$article.id}>/b<{$blog.id}>"
   title="<{$article.title}>"><{$article.title}></a>
</span>
    </div>
    <{if $is_list eq 0}>
        <div class="itemInfo">
            <{if $article.author}><span class="itemPoster"><{$article.author}></span> | <{/if}>
            <span class="itemPostDate"><{$article.time}></span>
            <{if $article.views}> | <{$article.views}> <{$smarty.const._READS}><{/if}>
            <{if $article.rates}> | <{$article.star}>/<{$article.rates}> <{/if}>
        </div>
        <{if $article.content}>
            <div class="itemBody"><p class="itemText"><{$article.content}></p></div>
        <{/if}>
        <div class="itemFoot">
            <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/index.php<{$smarty.const.URL_DELIMITER}>b<{$article.blog.id}>"
               title="<{$article.blog.title}>"><{$article.blog.title}></a> |
            <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/view.article.php<{$smarty.const.URL_DELIMITER}><{$article.id}>#comments"
               title="<{php}>echo planet_constant(" MD_COMMENTS")<{/php}>
            "><{php}>echo planet_constant("MD_COMMENTS")<{/php}> (<{$article.comments}>)</a> |
            <a href="#top" title="Top">T</a> : <a href="#page_footer" title="Bottom">B</a>
        </div>
        <br style="clear:both;">
    <{/if}>
<{/foreach}>

<{if $pagenav}>
    <div id="pagenav">
        <{$pagenav}>
    </div>
    <br style="clear:both;">
<{/if}>

<div id="page_footer">
    <div style="text-align: left; float: left;">
        <{php}>echo planet_constant("MD_SORT")<{/php}>: <{$link_sort}>
        <br>
        <{$link_switch}> |
        <{$link_blogs}>
        <{if $link_index}> | <{$link_index}><{/if}>
        <{if $link_bookmark}> | <{$link_bookmark}><{/if}>
    </div>
    <div style="text-align: right; float: right;">
        <{if $link_submit}><{$link_submit}><br><{/if}>
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/xml.php<{$smarty.const.URL_DELIMITER}>rss/c<{$category.id}>/u<{$user.uid}>/b<{$blog.id}>"
           target="api"><{php}>echo planet_constant("MD_RSS")<{/php}></a>
        |
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/xml.php<{$smarty.const.URL_DELIMITER}>rdf/c<{$category.id}>/u<{$user.uid}>/b<{$blog.id}>"
           target="api"><{php}>echo planet_constant("MD_RDF")<{/php}></a>
        |
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/xml.php<{$smarty.const.URL_DELIMITER}>atom/c<{$category.id}>/u<{$user.uid}>/b<{$blog.id}>"
           target="api"><{php}>echo planet_constant("MD_ATOM")<{/php}></a>
        <br><a href="https://xoops.org" target="_blank" title="Powered by Xoops Planet v<{$version}>"><img
                    src="<{$xoops_url}>/modules/planet/assets/images/planet.png"
                    alt="Powered by Xoops Planet v<{$version}>"
                    title="Powered by Xoops Planet v<{$version}>"></a>
    </div>
</div>
<br style="clear:both;">

<{if $xoops_notification}>
    <{include file='db:system_notification_select.tpl'}>
<{/if}>

<{if $do_pseudocron}>
    <img src="<{$xoops_url}>/modules/<{$xoops_dirname}>/update.php" alt="" width="1px" height="1px">
<{/if}>
