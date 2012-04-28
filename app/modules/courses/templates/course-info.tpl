<div class="bookmarkicon">
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
{foreach $courseDetails as $fieldName=>$item}
	{if $item[$fieldName]['list']}
		{include file="findInclude:common/templates/navlist.tpl" navListHeading=$item[$fieldName]['head'] navlistItems=$item[$fieldName]['list'] subTitleNewline=$contactsSubTitleNewline}
	{else}
		{if $item}
			{include file="findInclude:common/templates/navlist.tpl" navListHeading=$item[$fieldName]['head'] navlistItems=$item subTitleNewline=$contactsSubTitleNewline}
		{/if}
	{/if}
{/foreach}
{block name="links"}
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Links" navlistItems=$links subTitleNewline=true}
{/if}
{/block}
