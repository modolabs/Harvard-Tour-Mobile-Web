{foreach $courseDetails as $fieldName=>$item}
	{if $item['list']}
		{include file="findInclude:common/templates/navlist.tpl" navListHeading=$item[$fieldName]['head'] navlistItems=$item['list'] subTitleNewline=$contactsSubTitleNewline}
	{else}
		{if $item}
			{include file="findInclude:common/templates/navlist.tpl" navListHeading=$item[$fieldName]['head'] navlistItems=$item subTitleNewline=$contactsSubTitleNewline}
		{/if}
	{/if}
{/foreach}