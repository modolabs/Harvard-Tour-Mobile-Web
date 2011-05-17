{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead">
  <div id="pagetitle" class="overview"><h1>Starting Point</h1></div>
  <div id="viewtoggle">
    {if $view != 'map'}<a href="{$mapViewURL}">{/if}map{if $view != 'map'}</a>{/if}
    &nbsp;|&nbsp;
    {if $view != 'list'}<a href="{$listViewURL}">{/if}list{if $view != 'list'}</a>{/if}
  </div>

  <div id="nextstop" class="listrow">
    <div class="listthumb">
      <img src="/common/images/zoomicon-in@2x.png" alt="" border="0" class="zoomicon" />
      <img id="zoomthumb" src="{$stop['thumbnail']['src']}" onclick="zoomUpDown('zoomup')" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
    </div>
    <h2 id="stoptitle">{$stop['title']}</h2>
    <div id="starthere">
      <a  id="stoplink" href="{$stop['url']}">
        Start Here <img src="/common/images/arrow-right@2x.png" alt="Next" width="25" height="25" border="0" />
      </a>
    </div>
  </div>
</div>
<div id="content" class="mapcontent">
  <img id="zoomup" src="{$stop['photo']['src']}" onclick="zoomUpDown('zoomup')" />
  {include file="findInclude:modules/tour/templates/include/map.tpl"}
</div>
{include file="findInclude:common/templates/footer.tpl"}
