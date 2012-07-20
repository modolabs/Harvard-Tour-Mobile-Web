{if $tabInfoDetails}
{foreach $tabInfoDetails as $section=>$sectionData}
{include file="findInclude:common/templates/navlist.tpl" navListHeading=$sectionData.heading navlistItems=$sectionData.items subTitleNewline=$sectionData.subTitleNewline}
{/foreach}
{else}
<p>{"NO_INFO"|getLocalizedString}</p>
{/if}