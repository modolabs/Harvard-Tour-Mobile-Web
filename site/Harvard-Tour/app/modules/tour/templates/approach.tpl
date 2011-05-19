{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead">
  {include file="findInclude:modules/tour/templates/include/navHeader.tpl" navTitle="Walk to {$stop['title']}" nextURL=$nextURL prevURL=$prevURL}

  <div id="nextstop" class="listrow">
    <div class="listthumb">
      <img src="/common/images/zoomicon-in@2x.png" alt="" border="0" class="zoomicon" />
      <img src="{$stop['thumbnail']}" onclick="zoomUpDown('zoomup')" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
    </div>
    <div class="listicons">
      {foreach $stop['lenses'] as $lens => $lensContents}
        {if $lens != 'info'}
          <img src="/modules/tour/images/lens-{$lens}.png" width="20" height="20" alt="" />
        {/if}
      {/foreach}
    </div>
    <h2>{$stop['title']}</h2>
    <p id="subtitleEllipsis">{$stop['subtitle']}</p>
  </div>
</div>
<img id="zoomup" src="{$stop['photo']}" onclick="zoomUpDown('zoomup')" />
{include file="findInclude:modules/tour/templates/include/map.tpl" tappable=false}

{include file="findInclude:common/templates/footer.tpl"}
