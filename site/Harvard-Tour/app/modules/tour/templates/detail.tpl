{include file="findInclude:common/templates/header.tpl"}

{capture name="pageHeader" assign="pageHeader"}
  {include file="findInclude:modules/tour/templates/include/navheader.tpl" navTitle=$stop['title'] nextURL=$nextURL prevURL=$prevURL}
{/capture}

{$tabBodies = array()}
{foreach $tabKeys as $tabKey}
  {capture name="pane" assign="pane"}
    {foreach $stop['lenses'][$tabKey] as $lensContent}
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
	              <div id="slidedot_{$tabKey}_{$slide@index}" class="slidedot{if $slide@first} active{/if}"></div>
	            {/foreach}
	            <a id="slidenext_{$tabKey}" class="next active" onclick="nextSlide('{$tabKey}')">
	              <img src="/common/images/page-next.png" alt="Next" width="47" height="38" border="0" />
	            </a>
	          </div>
          </div>
        </div>
      {/if}
    {/foreach}
  {/capture}
  {$tabBodies[$tabKey] = $pane}
{/foreach}

<a name="scrolldown"> </a>
{include file="findInclude:modules/tour/templates/include/tabs.tpl" tabBodies=$tabBodies pageheader=$pageHeader}

<div class="lens-legend footnote"><h2>Legend:</h2><p><strong><img src="/device/compliant-iphone/modules/tour/images/lens-info.png" alt="Info" width="24" height="24" border="0">Info:</strong>General description of the stop</p><p><strong><img src="/device/compliant-iphone/modules/tour/images/lens-insideout.png" alt="Inside/out" width="24" height="24" border="0">Inside/out:</strong>An insider's view of Harvard</p><p><strong><img src="/device/compliant-iphone/modules/tour/images/lens-fastfacts.png" alt="Fast facts" width="24" height="24" border="0">Fast facts:</strong>Interesting facts and trivia</p><p><strong><img src="/device/compliant-iphone/modules/tour/images/lens-innovation.png" alt="Innovation" width="24" height="24" border="0">Innovation:</strong>Groundbreaking moments</p><p><strong><img src="/device/compliant-iphone/modules/tour/images/lens-history.png" alt="History" width="24" height="24" border="0">History:</strong>Highlights and stories</p></div>

{include file="findInclude:common/templates/footer.tpl"}
