{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
  <h2>{$title}</h2>
  <p class="smallprint">{$date|date_format:"%a %b %e, %Y %l:%M %p"}</p>
  <p>{$message}</p>
  <pre class="smallprint">{$preformatted}</pre>
</div>

{include file="findInclude:common/templates/footer.tpl"}
