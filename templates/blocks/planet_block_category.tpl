<!-- phppp (D.J.): http://xoopsforge.com; https://xoops.org.cn -->

<{foreach item=cat from=$block.categories}>
    <div>
        <a href="<{$xoops_url}>/modules/<{$block.dirname}>/index.php<{$smarty.const.URL_DELIMITER}>c<{$cat.id}>"><{$cat.title}></a>
        (<{$cat.blogs}>)
    </div>
<{/foreach}>
