{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal"><h2>{$itemName}</h2>
<p class="smallprint">{$uploadDate}</p>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}

<p>{$description}</p>

{include file="findInclude:common/templates/footer.tpl"}
