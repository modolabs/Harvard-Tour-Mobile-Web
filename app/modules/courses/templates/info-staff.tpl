{foreach $staff as $fieldName=>$item}
	{if $item[$fieldName]['list']}
		{include file="findInclude:common/templates/navlist.tpl" navListHeading=$item[$fieldName]['head'] navlistItems=$item[$fieldName]['list'] subTitleNewline=$contactsSubTitleNewline}
	{else}
		{if $item}
			{include file="findInclude:common/templates/navlist.tpl" navListHeading=$item[$fieldName]['head'] navlistItems=$item subTitleNewline=$contactsSubTitleNewline}
		{/if}
	{/if}
{/foreach}