{capture name="customHeader" assign="customHeader"}
  <div id="welcomehead">
    {if $resumeURL}
      <a class="startlink logostartlink" href="{$resumeURL}">
        <img src="/modules/tour/images/logo.png" alt="Tour of the Heights" width="300" height="30" border="0" />
        <br/>
        <img class="resume" src="/modules/tour/images/resume.png" alt="resume your tour" width="300" height="30" border="0" />
      </a>
      <a class="startlink" href="{$startURL}">
        <img class="startover" src="/modules/tour/images/startover.png" alt="start a new tour" width="300" height="30" border="0" />
      </a>
      
    {else}
      <a class="startlink logostartlink" href="{$startURL}">
        <img src="/modules/tour/images/logo.png" alt="Tour of the Heights" width="300" height="30" border="0" />
        <br/>
        <img class="begin" src="/modules/tour/images/begin.png" alt="begin your tour" width="300" height="30" border="0" />
      </a>
    {/if}
  </div>
  <div id="hero">
    <img src="/modules/tour/images/hero-hdpi.jpg" alt="Photo of BC" width="100%" border="0" />
    {if $pagetype == 'compliant' && $platform == 'iphone'}
      <div id="download">
        <a href="http://itunes.apple.com/us/app/harvard-yard-tour/id449660709">
          Download the free iPhone app
          <img src="/modules/tour/images/iphone4@2x.png" alt="iPhone" width="25" height="47" border="0" />
        </a>
      </div>
    {/if}
  </div>
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$customHeader}

{include file="findInclude:modules/tour/templates/include/pagecontents.tpl" pageContents=$contents}

<div id="welcomefooter">
    {if $resumeURL}
      <a class="startlink" href="{$resumeURL}">
        <img class="resume" src="/modules/tour/images/resume2.png" alt="resume your tour" width="300" height="30" border="0" />
      </a>
      <a class="startlink" href="{$startURL}">
        <img class="startover" src="/modules/tour/images/startover2.png" alt="start a new tour" width="300" height="30" border="0" />
      </a>
    {else}
      <a class="startlink" href="{$startURL}">
        <img class="begin" src="/modules/tour/images/begin2.png" alt="begin your tour" width="300" height="30" border="0" />
      </a>
    {/if}
</div>

{include file="findInclude:common/templates/footer.tpl"}
