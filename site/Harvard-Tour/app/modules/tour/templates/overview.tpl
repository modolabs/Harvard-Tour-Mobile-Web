{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead">
  {include file="findInclude:modules/tour/templates/include/navHeader.tpl" navTitle="Walk to first stop" nextURL="detail"}

  <div id="nextstop" class="listrow">
    <div class="listthumb">
      <img src="/common/images/zoomicon-in@2x.png" alt="" border="0" class="zoomicon" /></a>
      <img src="/modules/tour/images/content/sever-thumb.jpg" onclick="zoomUpDown('zoomup')" alt="Approach photo" width="75" height="50" border="0" class="listphoto" /></a>
    </div>
    <div class="listicons">
      <img src="/modules/tour/images/lens-insideout.png" width="20" height="20" alt="" />
      <img src="/modules/tour/images/lens-fastfacts.png" width="20" h	eight="20" alt="" />
      <img src="/modules/tour/images/lens-innovation.png" width="20" height="20" alt="" />
      <img src="/modules/tour/images/lens-history.png" width="20" height="20" alt="" />
    </div>
    <h2>Sever Hall</h2>
    <p>Designed by H. H. Richardson; site of the Whispering Arch</p>
  </div>
</div>
<div id="content">
  <img id="zoomup" src="/modules/tour/images/content/sever-est.jpg" onclick="zoomUpDown('zoomup')" />
  {include file="findInclude:modules/tour/templates/include/map.tpl"}
</div>
{include file="findInclude:common/templates/footer.tpl"}
