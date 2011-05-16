<div id="pagetitle"><h1>{$navTitle}</h1></div>
<div id="sidenav">
  {if $prevURL}
    <a id="previous" href="{$prevURL}">
      <img src="/common/images/arrow-left@2x.png" alt="Previous" width="50" height="50" border="0" />
    </a>
  {/if}
  {if $nextURL}
    <a id="next" href="{$nextURL}">
      <img src="/common/images/arrow-right@2x.png" alt="Next" width="50" height="50" border="0" />
    </a>
  {/if}
</div>
