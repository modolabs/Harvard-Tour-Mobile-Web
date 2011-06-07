{foreach $pageContents as $pageContent}
  {if is_array($pageContent)}
    {$firstItem = reset($pageContent)}
    {if isset($firstItem['url'])}
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$pageContent subTitleNewline=true}
    {elseif isset($firstItem['description'])}
      <dl>
        {foreach $pageContent as $item}
          <dt><img src="/modules/tour/images/lens-{$item['id']}-hdpi.png" alt="{$item['name']}" width="24" height="24" border="0" />{$item['name']}:</dt>
          <dd>{$item['description']}</dd>
        {/foreach}
      </dl>
    {/if}
  {else}
    {$pageContent}
  {/if}
{/foreach}
