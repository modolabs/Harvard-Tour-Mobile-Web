{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
<h2>{$key}</h2>
</div>
{block name="resources"}
{include file="findInclude:modules/courses/templates/include/resourcesList.tpl" resources=$resources}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
