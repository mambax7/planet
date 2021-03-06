<!-- phppp (D.J.): http://xoopsforge.com; https://xoops.org.cn -->

<div class="itemHead" style="text-align:center;">
    <h2><span class="itemTitle">
<{if $category}>
    <{php}>echo planet_constant("MD_CATEGORY")<{/php}>: <{$category.title}> (<{$count_blog}>)
<{elseif $user}>
    <{php}>echo planet_constant("MD_BOOKMARKS")<{/php}>:
    <a href="<{$xoops_url}>/userinfo.php?uid=<{$user.uid}>"><{$user.name}></a>
    (<{$count_blog}>)
<{else}>
    <{$pagetitle}>
<{/if}>
</span></h2>
</div>

<br style="clear:both;">

<{foreach item=blog from=$blogs}>
    <div class="itemHead" style="margin-top: 2px;">
<span class="itemTitle">
<a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/index.php<{$smarty.const.URL_DELIMITER}>b<{$blog.id}>"
   title="<{$blog.title}>"><{$blog.title}></a>
</span>
        <{if $user_level gt 1}>
            | <a
            href="<{$xoops_url}>/modules/<{$xoops_dirname}>/action.blog.php?blog=<{$blog.id}>"><{$smarty.const._EDIT}></a><{/if}>
    </div>
    <{if $is_list eq 0}>
        <div class="itemBody">
            <{if $blog.image}>
                <div class="image"><img src="<{$blog.image}>"></div><{/if}>
            <{if $blog.desc}><{$blog.desc}><br><{/if}>
            <{php}>echo planet_constant("MD_LASTUPDATE")<{/php}>: <{$blog.time}> (<a
                    href="<{$xoops_url}>/modules/<{$xoops_dirname}>/update.php?blog=<{$blog.id}>"><{php}>echo planet_constant("MD_UPDATE")<{/php}></a>)
            <br>
            <a href="<{$blog.feed}>" target="_blank"><{php}>echo planet_constant("MD_FEED")<{/php}></a>
            <{if $blog.link}>
                |
                <a href="<{$blog.link}>" target="_blank"><{php}>echo planet_constant("MD_URL")<{/php}></a>
            <{/if}>
            <{if $blog.rates OR $canrate}>
                | <{php}>echo planet_constant("MD_RATE")<{/php}>:
                <{if $blog.rates}><{$blog.star}>/<{$blog.rates}><{/if}>
                <{if $canrate}> ( <{section name=rate loop=6 max=5 step=-1}><a
                    href="<{$xoops_url}>/modules/<{$xoops_dirname}>/action.rate.php?blog=<{$blog.id}>&amp;rate=<{$smarty.section.rate.index}>"><{$smarty.section.rate.index}></a> <{/section}>   )<{/if}>
            <{/if}>
            <{if $blog.marks OR $user_level gt 0}>
                | <{php}>echo planet_constant("MD_BOOKMARKS")<{/php}>: <{$blog.marks}> <{if $user_level gt 0}>(
                <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/action.bookmark.php?blog=<{$blog.id}>"><{php}>echo planet_constant("MD_BOOKMARK")<{/php}></a>
                )<{/if}>
            <{/if}>
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
    <div style="text-align: left; float: left; margin-top: 5px;">
        <{php}>echo planet_constant("MD_SORT")<{/php}>: <{$link_sort}>
        <br>
        <{$link_home}> |
        <{if $link_articles}><{$link_articles}> | <{/if}>
        <{if $link_index}><{$link_index}> | <{/if}>
        <{$link_switch}>
        <{if $link_bookmark}> | <{$link_bookmark}><{/if}>
    </div>
    <div style="text-align: right; float: right;">
        <{if $link_submit}><{$link_submit}><{/if}>
    </div>
    <br style="clear:both;">
</div>

<{if $xoops_notification}>
    <{include file='db:system_notification_select.tpl'}>
<{/if}>
