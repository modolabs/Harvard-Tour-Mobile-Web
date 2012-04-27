<div class="bookmarkicon">
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
{block name="infoLocation"}
{if $location}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}
{/if}
{/block}

{foreach $courseDetails as $sectionName=>$section}
{include file="findInclude:common/templates/navlist.tpl" navListHeading=$sectionName navlistItems=$section accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/foreach}

{block name="links"}
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Links" navlistItems=$links subTitleNewline=true}
{/if}
{/block}
