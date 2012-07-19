{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal"><h2>{$courseTitle}</h2></div>

{include file="findInclude:common/templates/navList.tpl" navlistItems=$courseDetails}

{if $sectionList}
<h3 class="nonfocal">{"SECTIONS_TITLE"|getLocalizedString}</h3>
{include file="findInclude:common/templates/navList.tpl" navlistItems=$sectionList}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
