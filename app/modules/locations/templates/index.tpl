{include file="findInclude:common/templates/header.tpl"}

{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description|escape}
  </p>
{/if}
{foreach $groupedLocations as $group => $locations}
	{if $locations}
		{include file="findInclude:common/templates/navlist.tpl" navListHeading=$group navlistItems=$locations navlistID="locations" subTitleNewline=true}
	{/if}
{/foreach}
{include file="findInclude:common/templates/footer.tpl"}
