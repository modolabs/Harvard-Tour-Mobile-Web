{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead">
  {include file="findInclude:modules/tour/templates/include/navHeader.tpl" navTitle="Walk to {$stop['title']}" nextURL=$nextURL prevURL=$prevURL}

  <div id="nextstop" class="listrow">
    <div class="listthumb">
      <img src="/common/images/zoomicon-in@2x.png" alt="" border="0" class="zoomicon" />
      <img src="/modules/tour/images/content/sever-thumb.jpg" onclick="zoomUpDown('zoomup')" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
    </div>
    <div class="listicons">
      <img src="/modules/tour/images/lens-insideout.png" width="20" height="20" alt="" />
      <img src="/modules/tour/images/lens-fastfacts.png" width="20" h	eight="20" alt="" />
      <img src="/modules/tour/images/lens-innovation.png" width="20" height="20" alt="" />
      <img src="/modules/tour/images/lens-history.png" width="20" height="20" alt="" />
    </div>
    <h2>{$stop['title']}</h2>
    <p>{$stop['subtitle']}</p>
  </div>
</div>
<div id="content" class="mapcontent">
  <img id="zoomup" src="{$stop['photo']['src']}" onclick="zoomUpDown('zoomup')" />
  {include file="findInclude:modules/tour/templates/include/map.tpl"}
</div>

{include file="findInclude:common/templates/footer.tpl"}
