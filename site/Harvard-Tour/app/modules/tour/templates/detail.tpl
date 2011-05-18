{include file="findInclude:common/templates/header.tpl"}

{capture name="pageHeader" assign="pageHeader"}
  {include file="findInclude:modules/tour/templates/include/navHeader.tpl" navTitle=$stop['title'] nextURL=$nextURL prevURL=$prevURL}
{/capture}
{$tabBodies = array()}

{if in_array('info', $tabKeys)}
  {capture name="infoPane" assign="infoPane"}
    <img src="/modules/tour/images/content/337-main.jpg" alt="Photo" width="100%" class="hero" />
    <p class="caption">Description of Photo</p>
    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
    <p>Cuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
  {/capture}
  {$tabBodies['info'] = $infoPane}
{/if}


{if in_array('insideout', $tabKeys)}
  {capture name="insideoutPane" assign="insideoutPane"}
  {/capture}
  {$tabBodies['insideout'] = $insideoutPane}
{/if}


{if in_array('fastfacts', $tabKeys)}
  {capture name="fastfactsPane" assign="fastfactsPane"}
  {/capture}
  {$tabBodies['fastfacts'] = $fastfactsPane}
{/if}


{if in_array('innovation', $tabKeys)}
  {capture name="innovationPane" assign="innovationPane"}
  {/capture}
  {$tabBodies['innovation'] = $innovationPane}
{/if}


{if in_array('history', $tabKeys)}
  {capture name="historyPane" assign="historyPane"}
  {/capture}
  {$tabBodies['history'] = $historyPane}
{/if}


<a name="scrolldown"> </a>
{include file="findInclude:modules/tour/templates/include/tabs.tpl" tabBodies=$tabBodies pageheader=$pageHeader}

{include file="findInclude:common/templates/footer.tpl"}
