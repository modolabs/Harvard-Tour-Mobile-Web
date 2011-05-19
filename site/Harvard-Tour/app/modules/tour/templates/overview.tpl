{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead"{if $view != 'map'} class="brief"{/if}>
  <div id="pagetitle" class="overview"><h1>{if $start}Starting Point{else}Tour Overview{/if}</h1></div>
  <div id="viewtoggle">
    {if $view != 'map'}<a class="active" href="{$mapViewURL}">{else}<span>{/if}map{if $view != 'map'}</a>{else}</span>{/if} 
    <span class="spacer">|</span> 
    {if $view != 'list'}<a class="active" href="{$listViewURL}">{else}<span>{/if}list{if $view != 'list'}</a>{else}</span>{/if}
    {if !$start}<a id="doneURL" class="active" href="{$doneURL}">done</a>{/if}
  </div>

{if $view == 'map'}
    <div id="nextstop" class="listrow">
      <div class="listthumb">
        <img src="/common/images/zoomicon-in@2x.png" alt="" border="0" class="zoomicon" />
        <img id="zoomthumb" src="{$stop['thumbnail']}" onclick="zoomUpDown('zoomup')" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
      </div>
      {if !$start}
        <div class="listicons">
          {foreach $stop['lenses'] as $lens => $lensContents}
            {if $lens != 'info'}
              <img src="/modules/tour/images/lens-{$lens}.png" width="20" height="20" alt="" />
            {/if}
          {/foreach}
        </div>
      {/if}
      <h2 id="stoptitle">{$stop['title']}</h2>
      {if $start}
        <div id="starthere">
          <a  id="stoplink" href="{$stop['url']}">
            Start Here <img src="/common/images/arrow-right@2x.png" alt="Next" width="25" height="25" border="0" />
          </a>
        </div>
      {else}
        <p id="subtitleEllipsis">{$stop['subtitle']}</p>
      {/if}
    </div>
  </div>
  <img id="zoomup" src="{$stop['photo']}" onclick="zoomUpDown('zoomup')" />
  {include file="findInclude:modules/tour/templates/include/map.tpl" tappable=true}
{else}
  </div>
  <div id="content">
    {include file="findInclude:modules/tour/templates/include/list.tpl" stops=$stops}
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
