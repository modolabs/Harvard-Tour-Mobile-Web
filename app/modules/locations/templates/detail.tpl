{include file="findInclude:common/templates/header.tpl"}

{capture name="sideNav" assign="sideNav"}
{if $prevURL || $nextURL}
  <div class="{block name='sideNavClass'}sidenav2{/block}">
    {if $prevURL && $prev}
      <a href="{$prevURL}" class="sidenav-prev">
        {block name="prevPrefix"}{/block}
        {if $linkDateFormat}
          {$prev|date_format:$linkDateFormat}
        {else}
          {$prev}
        {/if}
      </a>{block name="sidenavSpacer"} {/block}
    {/if}
    {if $nextURL && $next}
      <a href="{$nextURL}" class="sidenav-next">
        {if $linkDateFormat}
          {$next|date_format:$linkDateFormat}
        {else}
          {$next}
        {/if}
        {block name="nextSuffix"}{/block}
      </a>
    {/if}
  </div>
{/if}
{/capture}

{capture name="fullTitle" assign="fullTitle"}
  {$title}{if $current || $isToday}: 
    {block name="date"}
      {if $isToday}
        Today
      {else}
        {$current|date_format:$titleDateFormat}
      {/if}
    {/block}
  {/if}
{/capture}

<div class="nonfocal">
  {block name="title"}
    <h2>{$fullTitle}</h2>
    <p>{$description}</p>
  {/block}
</div>
{$sideNav}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}

{block name="events"}
{if count($events)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$events accessKey=false subTitleNewline=true}
{/if}
{/block}

{$sideNav}

{include file="findInclude:common/templates/footer.tpl"}
