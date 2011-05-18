{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead"{if $view != 'map'} class="brief"{/if}>
  <div id="pagetitle" class="overview"><h1>{if $start}Starting Point{else}Tour Overview{/if}</h1></div>
  <div id="viewtoggle">
    {if $view != 'map'}<a href="{$mapViewURL}">{/if}map{if $view != 'map'}</a>{/if}
    &nbsp;|&nbsp;
    {if $view != 'list'}<a href="{$listViewURL}">{/if}list{if $view != 'list'}</a>{/if}
    {if !$start}
      &nbsp;&nbsp;<a href="{$doneURL}">done</a>
    {/if}
  </div>

{if $view == 'map'}
    <div id="nextstop" class="listrow">
      <div class="listthumb">
        <div class="thumbicons">
          {foreach $stop['lenses'] as $lens => $lensContents}
            {if $lens != 'info'}
              <img src="/modules/tour/images/lens-{$lens}.png" width="15" height="15" alt="" />
            {/if}
          {/foreach}
        </div>
        <img src="/common/images/zoomicon-in@2x.png" alt="" border="0" class="zoomicon" />
        <img id="zoomthumb" src="{$stop['thumbnail']}" onclick="zoomUpDown('zoomup')" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
      </div>
      <h2 id="stoptitle">{$stop['title']}</h2>
      <div id="starthere">
        <a  id="stoplink" href="{$stop['url']}">
          Start Here <img src="/common/images/arrow-right@2x.png" alt="Next" width="25" height="25" border="0" />
        </a>
      </div>
    </div>
  </div>
  <div id="content" class="overview">
    <img id="zoomup" src="{$stop['photo']}" onclick="zoomUpDown('zoomup')" />
    {include file="findInclude:modules/tour/templates/include/map.tpl"}
    <div id="helptext">Tap any pin to select it as your starting point</div>
  </div>
{else}
  </div>
  <div id="content" class="overview">
    {include file="findInclude:modules/tour/templates/include/list.tpl" stops=$stops}
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
