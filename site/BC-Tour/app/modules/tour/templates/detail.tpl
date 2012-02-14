{$tabEventAction = "Stop Detail"}
{if strlen($GOOGLE_ANALYTICS_ID)}
  {$onLoadBlocks[] = "_gaq.push(['_trackEvent', '{$tabEventAction}', '{$tabbedView['tabs'][$tabbedView['current']]['title']} Tab', '{$stop['title']}']);"}
{/if}
{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead" class="tabbed">
  {include file="findInclude:modules/tour/templates/include/navheader.tpl" navTitle=$stop['title'] nextURL=$nextURL prevURL=$prevURL}
  <a name="scrolldown"> </a>
  {if count($stop['lenses']) > 1}
    <ul id="tabs">
      {foreach $stop['lenses'] as $tabKey => $lensContents}
        {if isset($tabbedView['tabs'][$tabKey])}
          {$tabInfo = $tabbedView['tabs'][$tabKey]}
          <li{if $tabKey == $tabbedView['current']} class="active"{/if} id="{$tabKey}TourTab">
            <a href="javascript:void(0);" onclick="{if strlen($GOOGLE_ANALYTICS_ID)}_gaq.push(['_trackEvent', '{$tabEventAction}', '{$tabInfo['title']} Tab', '{$stop['title']}']); {/if}showTourTab('{$tabKey}', this);{$tabInfo['javascript']}">
              {block name="lensImage"}
                <img src="/modules/tour/images/lens-{$tabKey}@2x.png" alt="{$tabInfo['title']}" width="34" height="34" border="0" />
              {/block}
              <div class="tablabel">{$tabInfo['title']}</div>
            </a>
          </li>
        {/if}
      {/foreach}
    </ul>
  {/if}
</div>

<div id="tabbodies">
  {foreach $stop['lenses'] as $tabKey => $lensContents}
    {if isset($tabbedView['tabs'][$tabKey])}
      <div class="tabbody{if $tabKey == $tabbedView['current']} active{/if}" id="{$tabKey}TourTabbody">
        {foreach $lensContents as $lensContent}
          {if !is_array($lensContent)}
            {$lensContent}
          {else}
            <div class="slideshow">
              <div class="slides">
                {foreach $lensContent as $slide}
                  <div id="slide_{$tabKey}_{$slide@index}" class="slide{if $slide@first} active{/if}">{$slide}</div>
                {/foreach}
                <div class="slidenav">
                  <a id="slideprev_{$tabKey}" class="previous" onclick="previousSlide('{$tabKey}')">
                    <img src="/common/images/page-prev.png" alt="Previous" width="47" height="38" border="0" />
                  </a>
                  {foreach $lensContent as $slide}
                    <span id="slidedot_{$tabKey}_{$slide@index}" class="slidedot{if $slide@first} active{/if}">
                      <img class="inactive" src="/common/images/page-dot.png" height="14" width="14" />
                      <img class="active" src="/common/images/page-dot-current.png" height="14" width="14" />
                    </span>
                  {/foreach}
                  <a id="slidenext_{$tabKey}" class="next active" onclick="nextSlide('{$tabKey}')">
                    <img src="/common/images/page-next.png" alt="Next" width="47" height="38" border="0" />
                  </a>
                </div>
              </div>
            </div>
          {/if}
        {/foreach}
      </div>
    {/if}
  {/foreach}
</div>
<div class="clear"></div>

<div class="footnote">
  <h2>Legend:</h2>
  {include file="findInclude:modules/tour/templates/include/pagecontents.tpl" pageContents=$legend}
</div>

{* Remove stock showTab() because we are using a custom showTourTab function *}
{foreach $inlineJavascriptFooterBlocks as $i => $script}
  {if strncmp($script, "showTab", 7) == 0}
    {$inlineJavascriptFooterBlocks[$i] = ""}
  {/if}
{/foreach}

{include file="findInclude:common/templates/footer.tpl"}
