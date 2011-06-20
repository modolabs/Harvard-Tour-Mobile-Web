{foreach $pageContents as $pageContent}
  {if is_array($pageContent)}
    {$firstItem = reset($pageContent)}
    {if isset($firstItem['url'])}
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$pageContent subTitleNewline=true}
    {elseif isset($firstItem['description'])}
      <div class="lens-legend">
        <p><strong><img src="/modules/tour/images/lens-info.png" alt="Info" width="24" height="24" border="0" />Info:</strong>
          General description of the stop</p>
        {foreach $pageContent as $item}
          <p><strong><img src="/modules/tour/images/lens-{$item['id']}.png" alt="{$item['name']}" width="24" height="24" border="0" />{$item['name']}:</strong>
          {$item['description']}</p>
        {/foreach}
      </div>
    {/if}
  {else}
    {$pageContent}
  {/if}
{/foreach}
