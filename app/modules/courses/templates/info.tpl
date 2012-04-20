{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}
<div class="bookmarkicon">
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
{capture assign="tabBody"}
{if $location}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}
{/if}
{if $description}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Description" navlistItems=$description accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/if}
{if $instructors}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Instructor(s)" navlistItems=$instructors accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/if}

{block name="detailsStart"}
{/block}
  {foreach $courseDetails as $sectionName=>$section}
    {block name="sectionStart"}
		<div class="nonfocal">
		<h3>{$sectionName}</h3>
		</div>
		<ul class="nav section_{$sectionName}">
    {/block}        
        {foreach $section as $key=>$item}
          {block name="detail"}
            <li class="detail_{$key}{if !$item['label']} nolabel{/if}">
              {if $item['url']}
                <a href="{$item['url']}" class="{$item['class']}">
              {/if}
                  {if $item['label']}<div class="label">{$item['label']}</div>{/if}
                  {if $item['title']}<div class="value">{$item['title']}</div>{/if}
              {if $item['url']}
                </a>
              {/if}
              
              
              {if $item['img']}
			      <img src="{$item['img']}" class="image" alt="{$item['title']}"{if $item['imgWidth']}
			        width="{$item['imgWidth']}"{/if}{if $item['imgHeight']}
			        height="{$item['imgHeight']}"{/if}{if $item['imgAlt']}
			        alt="{$item['imgAlt']}"{/if} />
    		  {/if}
              
              
            </li>
          {/block}
        {/foreach}    
    {block name="sectionEnd"}
      </ul>
    {/block} 
  {/foreach}
{block name="detailsEnd"}
{/block}

{if $links}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Links" navlistItems=$links subTitleNewline=true}
{/if}

{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}



{include file="findInclude:common/templates/footer.tpl"}
