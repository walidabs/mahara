{include file="header.tpl"}
{if $canedit}
<div class="btn-top-right btn-group btn-group-top">
    <a class="btn btn-secondary addpost" href="{$WWWROOT}artefact/blog/post.php?blog={$blog->get('id')}">
        <span class="icon icon-plus left" role="presentation" aria-hidden="true"></span>
        {str section="artefact.blog" tag="addpost"}
    </a>
    {if !$blog->get('locked')}
    <a class="btn btn-secondary settings" href="{$WWWROOT}artefact/blog/settings/index.php?id={$blog->get('id')}">
        <span class="icon icon-cogs left" role="presentation" aria-hidden="true"></span>
        {str section="artefact.blog" tag="settings"}
    </a>
    {/if}
</div>
{/if}
<div id="myblogs" class="myblogs view-container">
    <div id="blogdescription">
        {clean_html($blog->get('description'))|safe}
    </div>
    {if $blog->get('tags')}
    <div class="tags">
        <strong>{str tag=tags}:</strong> {list_tags owner=$blog->get('owner') tags=$blog->get('tags')}
    </div>
    {/if}

    {if $posts.count > 0}
    <div id="postlist" class="postlist list-group list-group-lite list-group-top-border">
        {$posts.tablerows|safe}
    </div>
    <div id="blogpost_page_container" class="d-none">{$posts.pagination|safe}</div>
    <script>
    jQuery(function($) {literal}{{/literal}
        {$posts.pagination_js|safe}
        $('#blogpost_page_container').removeClass('d-none');
        {literal}}{/literal});
    </script>
    {else}
    <p class="no-results">
        {str tag=nopostsyet section=artefact.blog} {if !$blog->get('locked')}<a href="{$WWWROOT}artefact/blog/post.php?blog={$blog->get('id')}">{str tag=addone section=mahara}</a>{/if}
    </p>
    {/if}

    {if $enablemultipleblogstext}
    <p class="alert alert-default">
        {str tag=enablemultipleblogstext section=artefact.blog arg1=$WWWROOT}</p>
    {/if}

    {if $hiddenblogsnotification}
    <p class="lead text-center">
        {str tag=hiddenblogsnotification section=artefact.blog arg1=$WWWROOT}</p>
    {/if}
</div>
{include file="footer.tpl"}
