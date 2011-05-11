{capture name="customHeader" assign="customHeader"}
  <div id="welcomehead">
    <a id="startlink" href="start">
      <img id="logo" src="/modules/tour/images/logo-hdpi.png" alt="harvard yard tour" width="231" height="33" border="0" />
      <br/>
      <img id="begin" src="/modules/tour/images/begin@2x.png" alt="begin your tour" width="131" height="30" border="0" />
    </a>
  </div>
  <div id="hero">
    <img src="/modules/tour/images/hero-hdpi.jpg" alt="Photo of Harvard" width="100%" border="0" />
    {if $pagetype == 'compliant' && $platform == 'iphone'}
      <div id="download">
        <a href="">
          Download the free iPhone app
          <img src="/modules/tour/images/iphone4@2x.png" alt="iPhone" width="25" height="47" border="0" />
        </a>
      </div>
    {/if}
  </div>
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$customHeader}

<h1>Welcome to Harvard University.</h1>
<p>This self-paced tour gives you an insider’s view of Harvard Yard &ndash; the school's historic heart. Each tour stop includes information on one or more of the following topics:</p>
<dl>
  <dt>
    <img src="/modules/tour/images/lens-insideout-hdpi.png" alt="Inside/Out" width="24" height="24" border="0" />
    Inside/out:
  </dt>
  <dd>A look behind closed doors</dd>
  <dt>
    <img src="/modules/tour/images/lens-fastfacts-hdpi.png" alt="Fast Facts" width="24" height="24" border="0" />
    Fast facts:</dt>
  <dd>Interesting facts and tidbits</dd>
  <dt>
    <img src="/modules/tour/images/lens-innovation-hdpi.png" alt="Innovation" width="24" height="24" border="0" />
    Innovation:
  </dt>
  <dd>Groundbreaking firsts</dd>
  <dt>
    <img src="/modules/tour/images/lens-history-hdpi.png" alt="History" width="24" height="24" border="0" />
    History:
  </dt>
  <dd>Highlights and stories</dd>
</dl>
<p>Please note that many buildings are only open to Harvard University ID holders.</p> 

{include file="findInclude:common/templates/footer.tpl"}
