{capture name="customHeader" assign="customHeader"}
  <div id="welcomehead">
    <a id="startlink" href="{$startURL}">
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
<p>Today, you will explore Harvard Yard, home of America&rsquo;s oldest institution of higher education, and learn about Harvard&rsquo;s impact today on education, and on the world. Each tour stop includes one or more of the following topics:</p>
<dl>
  <dt>
    <img src="/modules/tour/images/lens-insideout-hdpi.png" alt="Inside/Out" width="24" height="24" border="0" />
    Inside/out:
  </dt>
  <dd>An insider&rsquo;s view of Harvard</dd>
  <dt>
    <img src="/modules/tour/images/lens-fastfacts-hdpi.png" alt="Fast Facts" width="24" height="24" border="0" />
    Fast facts:</dt>
  <dd>Interesting facts and trivia</dd>
  <dt>
    <img src="/modules/tour/images/lens-innovation-hdpi.png" alt="Innovation" width="24" height="24" border="0" />
    Innovation:
  </dt>
  <dd>Groundbreaking moments</dd>
  <dt>
    <img src="/modules/tour/images/lens-history-hdpi.png" alt="History" width="24" height="24" border="0" />
    History:
  </dt>
  <dd>Highlights and stories</dd>
</dl>
<p>Please note that many buildings are only open to Harvard University ID holders.</p> 

{include file="findInclude:common/templates/footer.tpl"}
