{capture name="customHeader" assign="customHeader"}
  <div id="welcomehead">
    <img src="/modules/tour/images/logo.png" alt="harvard yard tour" width="300" height="30" border="0" />
  </div>
  <div id="hero">
    <img src="/modules/tour/images/hero-hdpi.jpg" alt="Photo of Harvard" width="100%" border="0" />
    {if $pagetype == 'compliant' && $platform == 'iphone'}
      <div id="download">
        <span>
          Free iPhone app coming soon!
          <img src="/modules/tour/images/iphone4@2x.png" alt="iPhone" width="25" height="47" border="0" />
        </span>
      </div>
    {/if}
  </div>
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$customHeader}

<p>Coming Soon: Mobile Tour of Harvard Yard</p>

<p>Welcome to Harvard University! Tour Harvard Yard with any web-enabled smartphone to learn about life at Harvard today as well as the University’s 375-year history.</p>

<p>This self-guided tour features text descriptions at each stop as well as audio, video and images — including pictures from the University archives and exclusive inside views of Harvard buildings today. Each of the sixteen stops offers a general description, as well as additional information in up to four categories:</p>

<div class="lens-legend">
  <p><strong><img src="/harvard-tour/modules/tour/images/lens-info.png" alt="Info" width="24" height="24" border="0" />Info:</strong> 
    General description of the stop</p>

  <p><strong><img src="/harvard-tour/modules/tour/images/lens-insideout.png" alt="Inside/out" width="24" height="24" border="0" />Inside/out:</strong> An insider&#039;s view of Harvard</p>

  <p><strong><img src="/harvard-tour/modules/tour/images/lens-fastfacts.png" alt="Fast facts" width="24" height="24" border="0" />Fast facts:</strong> Interesting facts and trivia</p>

  <p><strong><img src="/harvard-tour/modules/tour/images/lens-innovation.png" alt="Innovation" width="24" height="24" border="0" />Innovation:</strong> Groundbreaking moments</p>

  <p><strong><img src="/harvard-tour/modules/tour/images/lens-history.png" alt="History" width="24" height="24" border="0" />History:</strong> Highlights and stories</p>
</div>

<p>Access the tour using any web-enabled smartphone, or download the native iPhone app for free in the iTunes App Store. Questions? Email <a href="mailto:digitalcomms@harvard.edu">digitalcomms@harvard.edu</a></p>

<p>Once again, welcome to Harvard. Be sure to stop by the <a href=http://www.harvard.edu/visitors/>Information Center in Holyoke Center</a> for more information on your visit to our campus.</p>

{include file="findInclude:common/templates/footer.tpl"}
