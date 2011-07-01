{include file="findInclude:common/templates/header.tpl"}

{$tabBodies = array()}

{capture name="runningPane" assign="runningPane"}
  {block name="runningPane"}
    {foreach $runningRoutes as $section}
      <h3>{$section['heading']}</h3>
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true}
    {/foreach}
  {/block}
{/capture}
{$tabBodies['running'] = $runningPane}

{capture name="offlinePane" assign="offlinePane"}
  {block name="offlinePane"}
    {foreach $offlineRoutes as $section}
      <h3>{$section['heading']}</h3>
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true}
    {/foreach}
  {/block}
{/capture}
{$tabBodies['offline'] = $offlinePane}

{capture name="newsPane" assign="newsPane"}
  {block name="newsPane"}
    {foreach $news as $section}
      <h3>{$section['heading']}</h3>
      {foreach $section['items'] as $index => $item}
        {$section['items'][$index]['subtitle'] = $item['date']|date_format:"%a %b %e, %Y"}
      {/foreach}
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true subTitleNewline=true}
    {/foreach}
  {/block}
{/capture}
{$tabBodies['news'] = $newsPane}

{capture name="infoPane" assign="infoPane"}
  {block name="infoPane"}
    {foreach $infosections as $section}
      <h3>{$section['heading']}</h3>
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true subTitleNewline=true}
    {/foreach}
  {/block}
{/capture}
{$tabBodies['info'] = $infoPane}

{block name="tabView"}
	<a name="scrolldown"></a>		
  <div class="nonfocal">
	  {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies smallTabs=true}
	</div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
