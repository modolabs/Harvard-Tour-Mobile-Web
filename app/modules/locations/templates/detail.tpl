{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
  {block name="title"}
    <h2>
      {include file="findInclude:common/templates/listItem.tpl" item=$title}
    </h2>
  {/block}

  {block name="events"}
    {if count($events)}
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$events accessKey=false}
    {/if}
  {/block}
  
  <p class="legend">
    {include file="findInclude:common/templates/listItem.tpl" item=$mapLink}
  </p>

  <p class="legend">
    {include file="findInclude:common/templates/listItem.tpl" item=$nextDetail}
  </p>

</div>

{include file="findInclude:common/templates/footer.tpl"}
