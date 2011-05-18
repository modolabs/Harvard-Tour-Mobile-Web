{include file="findInclude:common/templates/header.tpl"}

{capture name="pageHeader" assign="pageHeader"}
  {include file="findInclude:modules/tour/templates/include/navHeader.tpl" navTitle=$stop['title'] nextURL=$nextURL prevURL=$prevURL}
{/capture}

{$tabBodies = array()}
{foreach $tabKeys as $tabKey}
  {capture name="pane" assign="pane"}
    {foreach $stop['lenses'][$tabKey] as $lensContent}
      {$lensContent}
    {/foreach}
  {/capture}
  {$tabBodies[$tabKey] = $pane}
{/foreach}

<a name="scrolldown"> </a>
{include file="findInclude:modules/tour/templates/include/tabs.tpl" tabBodies=$tabBodies pageheader=$pageHeader}

{include file="findInclude:common/templates/footer.tpl"}
