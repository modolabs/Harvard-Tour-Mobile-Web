{foreach $groupedLocations as $group => $locations}
	{if $locations}
		{include file="findInclude:common/templates/navlist.tpl" navListHeading=$group navlistItems=$locations navlistID="locations" subTitleNewline=true}
	{/if}
{/foreach}
