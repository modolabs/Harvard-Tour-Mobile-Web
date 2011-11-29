{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  {block name="title"}
    <h2>{$title}</h2>
  {/block}

</div>  
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}

{block name="events"}
{if count($events)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$events accessKey=false subTitleNewline=true}
{/if}
{/block}
  
{include file="findInclude:common/templates/footer.tpl"}
