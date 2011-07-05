{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead">
  {if $view == 'overview'}
    {block name="pageTitle"}
      <div id="pagetitle" class="overview"><h1>{if $newTour}Starting Point{else}Tour Overview{/if}</h1></div>
    {/block}
    <div id="viewtoggle">
      <span>map</span>
      <span class="spacer">|</span>
      <a class="active" href="{$listViewURL}">list</a>
      {if !$newTour}
        <a id="doneURL" class="active" href="{$doneURL}" onclick="return confirmStopChange();">return</a>
      {/if}
    </div>
  {else}
    {capture name="navTitle" assign="navTitle"}
      Walk to <span id="navstoptitle">{$stop['title']}</span>
    {/capture}
    {include file="findInclude:modules/tour/templates/include/navheader.tpl" navTitle=$navTitle nextURL=$nextURL prevURL=$prevURL confirmStopChange=true}
  {/if}
  
  <div id="nextstop" class="listrow">
    <div class="listthumb" onclick="zoomUpDown('zoomup')">
      <img src="/common/images/zoomicon-in.png" alt="" border="0" class="zoomicon" />
      <img id="zoomthumb" src="{$stop['thumbnail']}" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
    </div>
    <h2 id="stoptitle">{$stop['title']}</h2>
    {if $newTour}
      <div id="starthere">
        <a id="stoplink" href="{$stop['url']}">
          Start Here <img src="/common/images/arrow-right@2x.png" alt="Next" width="25" height="25" border="0" />
        </a>
      </div>
    {else}
      <p id="stopsubtitle">{$stop['subtitle']}</p>
    {/if}
  </div>
</div>
<div id="zoomup" onclick="zoomUpDown('zoomup')">
  <img src="/common/images/zoomicon-out.png" alt="" border="0" class="zoomout"/>
  <img id="zoomphoto" src="{$stop['photo']}" class="zoomphoto"/>
</div>
{include file="findInclude:modules/tour/templates/include/map.tpl"}
{include file="findInclude:common/templates/footer.tpl"}
